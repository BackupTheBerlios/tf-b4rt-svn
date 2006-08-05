#!/usr/bin/perl
################################################################################
# $Id$
# $Date$
# $Revision$
################################################################################
#                                                                              #
# LICENSE                                                                      #
#                                                                              #
# This program is free software; you can redistribute it and/or                #
# modify it under the terms of the GNU General Public License (GPL)            #
# as published by the Free Software Foundation; either version 2               #
# of the License, or (at your option) any later version.                       #
#                                                                              #
# This program is distributed in the hope that it will be useful,              #
# but WITHOUT ANY WARRANTY; without even the implied warranty of               #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                 #
# GNU General Public License for more details.                                 #
#                                                                              #
# To read the license please visit http://www.gnu.org/copyleft/gpl.html        #
#                                                                              #
#                                                                              #
################################################################################
package Fluxd;
use strict;
use warnings;
use IO::Socket::UNIX;
use IO::Select;
use Symbol qw(delete_package);
use POSIX qw(setsid);
################################################################################

#------------------------------------------------------------------------------#
# Internal Variables                                                           #
#------------------------------------------------------------------------------#
my ( $DB_TYPE, $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS );
my $BIN_PHP = "/usr/bin/php";
my $BIN_FLUXCLI = "fluxcli.php";
my $PATH_DOCROOT = "/var/www/";
my $PATH_TORRENT_DIR = ".torrents";
my $PATH_DATA_DIR = "fluxd";
my $PATH_SOCKET = "fluxd.sock";
my $ERROR_LOG = "fluxd-error.log";
my $LOG = "fluxd.log";
my $PID_FILE = "fluxd.pid";
my $PATH_QUEUE_FILE = "fluxd.queue";
my ( $MAX_SYS, $MAX_USER, $PATH_PHP, $LOGLEVEL );
my $SERVER;
my $Select = new IO::Select();
my ( $VERSION, $DIR, $PROG, $EXTENSION );
my $start_time = time();

#------------------------------------------------------------------------------#
# Class reference variables                                                    #
#------------------------------------------------------------------------------#
use vars qw( $Qmgr $Fluxinet $Watch $Clientmaint $Trigger $fluxDB);

#------------------------------------------------------------------------------#
# Main                                                                         #
#------------------------------------------------------------------------------#

# flush the buffer
$| = 1;

# Intialize
Initialize();

# Verify that we have been started in a valid way
ProcessArguments();

# Daemonise the script
&Daemonize;

# Read config and load modules
Config();

use vars qw( $loop );
$loop = 0;
# Here we go! The main loop!
while ( 1 ) {
	CheckConnections();
	$Qmgr->Main if(defined $Qmgr);
	$Fluxinet->Main if(defined $Fluxinet);
	$Watch->Main if(defined $Watch);
	$Clientmaint->Main if(defined $Clientmaint);
	$Trigger->Main if(defined $Trigger);
}

#------------------------------------------------------------------------------#
# Sub: ProcessRequest                                                          #
# Arguments: Command                                                           #
# Returns: String info on command success/failure                              #
#------------------------------------------------------------------------------#
sub ProcessRequest {
	my @array = ();
	my $temp = shift;
	@array = split (/ /, $temp);
	@_ = @array;
	my $return;

	SWITCH: {
		$_ = shift;

		# Actual fluxd subroutine calls
		/^die/ && do {
			$return = StopServer();
			last SWITCH;
		};
		/^status/ && do {
			$return = Status();
			last SWITCH;
		};
		/^check/ && do {
			$return = Check();
			last SWITCH;
		};
		/^set/ && do {
			$return = Set(shift, shift);
			last SWITCH;
		};

		# fluxcli.php calls
		/^start|^stop|^inject|^wipe|^delete|^reset|^\w+-all|^torrents|^netstat/ && do {
			$return = Fluxcli($_, shift, shift);
			last SWITCH;
		};

		# Package calls
		if (exists &Qmgr::Main) {
			/^count-jobs/ && do {
				$return = Qmgr::CountJobs();
				last SWITCH;
			};
			/^count-queue/ && do {
				$return = Qmgr::CountQueue();
				last SWITCH;
			};
			/^list-queue/ && do {
				$return = Qmgr::ListQueue();
				last SWITCH;
			};
		}
		if (exists &Watch::SetWatch) {
			/^watch/ && do {
				$return = Watch::SetWatch(shift, shift);
				last SWITCH;
			};
		}
		if (exists &Trigger::SetTrigger) {
			/^trigger/ && do {
				$return = Trigger::SetTrigger(shift, shift);
				last SWITCH;
			};
		}

		# Default case.
		$return = PrintUsage();
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: Fluxcli                                                                 #
# Arguments: Command [Arg1, [Arg2]]                                            #
# Returns: Info string                                                         #
#------------------------------------------------------------------------------#
sub Fluxcli {
	my $Command = shift;
	my $Arg1 = shift;
	my $Arg2 = shift;
	my $return;

	if ($Command =~/^torrents|^netstat|^\w+-all|^repair/) {
		if ( (defined $Arg1) || (defined $Arg2) ) {
			$return = PrintUsage();
			next;
		} else {
			$return = `$PATH_PHP $BIN_FLUXCLI $Command`;
			next;
		}
	}
	if ($Command =~/^start|^stop|^reset|^delete|^wipe|^xfer/) {
		if ( (!(defined $Arg1)) || (defined $Arg2) ) {
			$return = PrintUsage();
			next;
		} else {
			$return = `$PATH_PHP $BIN_FLUXCLI $Command $Arg1`;
			next;
		}
	}
	if ($Command =~/^inject/) {
		if ( (!(defined $Arg1)) || (!(defined $Arg2)) ) {
			$return = PrintUsage();
			next;
		} else {
			$return = `$PATH_PHP $BIN_FLUXCLI $Command $Arg1 $Arg2`;
			next;
		}
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: Check                                                                   #
# Arguments: Null                                                              #
# Returns: info on system requirements                                         #
#------------------------------------------------------------------------------#
sub Check {
	print "Checking requirements...\n";
	my $return = 0;
	# check modules
	print "1. modules\n";
	my @mods = ('IO::Socket::UNIX', 'IO::Select', 'Symbol', 'POSIX', 'DBI');
	foreach my $mod (@mods) {
		if (eval "require $mod")  {
			$return = 1;
			print " - ".$mod."\n";
			next;
		} else {
			print "Fatal Error : cant load module ".$mod."\n";
			# Turn on Autoflush;
			$| = 1;
			print "Should we try to install the module with CPAN ? (y|n) ";
			my $answer = "";
			chomp($answer=<STDIN>);
			$answer = lc($answer);
			if ($answer eq "y") {
				exec('perl -MCPAN -e "install '.$mod.'"');
			}
			exit;
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: Status                                                                  #
# Arguments: Null                                                              #
# Returns: Server information page                                             #
#------------------------------------------------------------------------------#
sub Status {
	my $retval = "";
	$retval .= "Fluxd has been up since $start_time\n";
	return $retval;
}

#------------------------------------------------------------------------------#
# Sub: Set                                                                     #
# Arguments: Variable, [Value]                                                 #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Set {
	my $variable = shift;
	my $value = shift;
	my $return;

	if ($variable =~/::/) {
		# setting/getting package variable
		my @pair = split(/::/, $variable);
		next if ($pair[0] !~/Qmgr|Fluxinet|Trigger|Watch|Clientmaint/);
		SWITCH: {
			$_ = $pair[0];
			/Qmgr/ && do {
				$return = $Qmgr->Set($pair[1], $value) if (defined $Qmgr);
				last SWITCH;
			};
			/Fluxinet/ && do {
				$return = $Fluxinet->Set($pair[1], $value) if(defined $Fluxinet);
				last SWITCH;
			};
			/Trigger/ && do {
				$return = $Trigger->Set($pair[1], $value) if(defined $Trigger);
				last SWITCH;
			};
			/Watch/ && do {
				$return = $Watch->Set($pair[1], $value) if(defined $Watch);
				last SWITCH;
			};
			/Clientmaint/ && do {
				$return = $Clientmaint->Set($pair[1], $value) if(defined $Clientmaint);
				last SWITCH;
			};
			$return = "Unknown package\n";
		}
	} else {
		# setting/getting internal variable
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: StopServer                                                              #
# Arguments: Null                                                              #
# Returns: Info string                                                         #
#------------------------------------------------------------------------------#
sub StopServer {
	print "Shutting down!\n";
	unlink($PATH_SOCKET);
	exit;
}

#------------------------------------------------------------------------------#
# Sub: ProcessArguments                                                        #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub ProcessArguments {
	my $temp = shift @ARGV;

	# first arg may be operation.
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	# help
	if ($temp =~ /.*(help|-h).*/) {
		PrintUsage();
		exit;
	}
	# version
	if ($temp =~ /.*(version|-v).*/) {
		PrintVersion();
		exit;
	};
	# check
	if ($temp =~ /check/) {
		Check();
		exit;
	};

	# debug
	if ($temp =~ /debug/) {
		Debug();
		exit;
	};

	# $MAX_SYS
	if ($temp !~/\d+/) {
		PrintUsage();
		exit;
	}
	$MAX_SYS = $temp;

	# $MAX_USER
	$temp = shift @ARGV;
	if ( (!(defined $temp)) || ($temp !~/\d+/) ) {
		PrintUsage();
		exit;
	}
	$MAX_USER = $temp;

	# $PATH_PHP
	$temp = shift @ARGV;
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	$PATH_PHP = $temp;

	# path to home
	$temp = shift @ARGV;
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	InitPaths($temp);

	# $PATH_DOCROOT
	$temp = shift @ARGV;
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	if (!((substr $temp, -1) eq "/")) {
		$temp .= "/";
	}
	$PATH_DOCROOT = $temp;

	# $LOGLEVEL
	$temp = shift @ARGV;
	if ( (!(defined $temp)) && ($temp !~/\d+/) ) {
		PrintUsage();
		exit;
	}
	$LOGLEVEL = $temp;
}

#------------------------------------------------------------------------------#
# Sub: Initialize                                                              #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Initialize {
	# Windows is not supported
	if ("$^0" =~ /win32/i) {
		print "\r\nWin32 not supported.\r\n";
		exit;
	}
	# initialize some variables
	$VERSION = do { my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
	($DIR=$0) =~ s/([^\/\\]*)$//;
	($PROG=$1) =~ s/\.([^\.]*)$//;
	$EXTENSION=$1;
}

#------------------------------------------------------------------------------#
# Sub: InitPaths                                                               #
# Arguments: base path for t-flux                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub InitPaths {
	my $path = shift;
	if (!((substr $path, -1) eq "/")) {
		$path .= "/";
	}
	$PATH_TORRENT_DIR = $path.$PATH_TORRENT_DIR."/";
	$PATH_DATA_DIR = $path.$PATH_DATA_DIR."/";
	$PATH_QUEUE_FILE = $PATH_DATA_DIR.$PATH_QUEUE_FILE;
	$PATH_SOCKET = $PATH_DATA_DIR.$PATH_SOCKET;
	$LOG = $PATH_DATA_DIR.$LOG;
	$ERROR_LOG = $PATH_DATA_DIR.$ERROR_LOG;
	$PID_FILE = $PATH_DATA_DIR.$PID_FILE;
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $PATH_DATA_DIR) {
		mkdir($PATH_DATA_DIR,0700);
	}
}

#------------------------------------------------------------------------------#
# Sub: Daemonize                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Daemonize {
	#chdir '/'			or die "Can't chdir to /: $!";
	umask 0;			# sets our umask
	open STDIN, "/dev/null" 	or die "Can't read /dev/null: $!";
	open STDOUT, ">>$LOG"		or die "Can't Write to $LOG: $!";
	open STDERR, ">>$ERROR_LOG"	or die "Can't Write to error $ERROR_LOG: $!";
	defined(my $pid = fork)		or die "Can't fork: $!";
	exit if $pid;
	setsid				or die "Can't start a new session: $!";

	# check requirements, die if they aren't there
	#if (!(Check())) {
	#	exit;
	#}

	# Set up our signal handler
	$SIG{HUP} = \&GotSigHup;

	# set up daemon stuff...
        # set up server socket
        $SERVER = IO::Socket::UNIX->new(
                Type    => SOCK_STREAM,
                Local   => $PATH_SOCKET,
                Listen  => 16,
                Reuse   => 1,
                );
        die "Couldn't create socket: $!\n" unless $SERVER;
        # Add our server socket to the select read set.
        $Select->add($SERVER);

}

#------------------------------------------------------------------------------#
# Sub: PrintUsage                                                              #
# Arguments: Null                                                              #
# Returns: Usage Information                                                   #
#------------------------------------------------------------------------------#
sub PrintUsage {
	print <<"USAGE";

$PROG.$EXTENSION Revision $VERSION

Usage: $PROG.$EXTENSION <begin> max-running, max-user, path-to-php,
                              path-to-download, path-to-docroot, loglevel
                        starts fluxd
       $PROG.$EXTENSION <start|stop|reset|delete|wipe> foo.torrent
                        starts, stops, resets totals, deletes, or deletes
                        and resets totals for a torrent, as well as removing
                        all data downloaded for that torrent
       $PROG.$EXTENSION <torrents|status|netstat|start-all|stop-all|resume-all>
                        lists info about the selected aspect. Status shows all
       $PROG.$EXTENSION inject /path/to/foo.torrent user
                        injects a torrent file into flux as the specified user
       $PROG.$EXTENSION watch /path/to/watch/dir user
                        sets fluxd to watch the specified directory and upload
                        torrents entered in it as the specified user
       $PROG.$EXTENSION set <LOGLEVEL|MAX_USR|MAX_SYS> [VALUE]
                        if given without a value argument, returns current
                        value of the given variable. If given with a value
                        argument, sets the given variable to that value
       $PROG.$EXTENSION stop
                        stops the fluxd server
       $PROG.$EXTENSION <count-jobs|count-queue|list-queue|check>
                        returns the number of jobs, number of entries in the
                        queue, list entries in the queue, or check to ensure
                        that this computer has everything fluxd needs.
       $PROG.$EXTENSION repair
                        repairs torrentflux. DO NOT DO THIS if your system
                        is running as it should. You WILL break something.

       $PROG.$EXTENSION check
                        checks for requirements.
       $PROG.$EXTENSION <-h|--help>
                        print out help screen.
       $PROG.$EXTENSION <-v|--version>
                        print out version-info

USAGE
}

#------------------------------------------------------------------------------#
# Sub: PrintVersion                                                            #
# Arguments: Null                                                              #
# Returns: Version Information                                                 #
#------------------------------------------------------------------------------#
sub PrintVersion {
	print $PROG.".".$EXTENSION." Version ".$VERSION."\n";

	# Clientmaint
	require Clientmaint;
	print "Clientmaint Version ".Clientmaint->getVersion()."\n";

	# FluxDB
	require FluxDB;
	print "FluxDB Version ".FluxDB->getVersion()."\n";

	# Fluxinet
	require Fluxinet;
	print "Fluxinet Version ".Fluxinet->getVersion()."\n";

	# Qmgr
	#require Qmgr;
	#print "Qmgr Version ".Qmgr->getVersion()."\n";

	# Trigger
	require Trigger;
	print "Trigger Version ".Trigger->getVersion()."\n";

	# Watch
	require Watch;
	print "Watch Version ".Watch->getVersion()."\n";

}

#------------------------------------------------------------------------------#
# Sub: Config                                                                  #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Config {
	open(CONFIG, $PATH_DOCROOT."fluxd/fluxd.conf") || die("Can't open fluxd.conf: $!");
	while (<CONFIG>) {
		# I checked $/ and it's set to \n, but <CONFIG> reads the whole file.
		# any ideas why?
		my @lines = split(/\n/, $_);
		foreach $_ (@lines) {
			SWITCH: {
				# Load up modules, unless they're already
				# loaded
				/^INCLUDE\sQmgr\.pm$/ && do {
					if (!(exists &Qmgr::New)) {
						require Qmgr;
						$Qmgr = Qmgr->New();
						last SWITCH;
					}
				};
				/^INCLUDE\sFluxinet\.pm$/ && do {
					if (!(exists &Fluxinet::New)) {
						require Fluxinet;
						$Fluxinet = Fluxinet->New();
						last SWITCH;
					}
				};
				/^INCLUDE\sWatch\.pm$/ && do {
					if (!(exists &Watch::New)) {
						require Watch;
						$Watch = Watch->New();
						last SWITCH;
					}
				};
				/^INCLUDE\sClientmaint\.pm$/ && do {
					if (!(exists &Clientmaint::New)) {
						require Clientmaint;
						$Clientmaint = Clientmaint->New();
						last SWITCH;
					}
				};
				/^INCLUDE\sTrigger\.pm$/ && do {
					if (!(exists &Trigger::New)) {
						require Trigger;
						$Trigger = Trigger->New();
						last SWITCH;
					}
				};

				# Unload modules, if they are loaded
				/^#INCLUDE\sQmgr\.pm$/ && do {
					if(exists &Qmgr::New) {
						$Qmgr->Destroy();
						delete_package('Qmgr');
						undef $Qmgr;
						last SWITCH;
					}
				};
				/^#INCLUDE\sFluxinet\.pm$/ && do {
					if (exists &Fluxinet::New) {
						$Fluxinet->Destroy();
						delete_package('Fluxinet');
						undef $Fluxinet;
						last SWITCH;
					}
				};
				/^#INCLUDE\sWatch\.pm$/ && do {
					if (exists &Watch::New) {
						$Watch->Destroy();
						delete_package('Watch');
						undef $Watch;
						last SWITCH;
					}
				};
				/^#INCLUDE\sClientmaint\.pm$/ && do {
					if (exists &Clientmaint::New) {
						$Clientmaint->Destroy();
						delete_package('Clientmaint');
						undef $Clientmaint;
						last SWITCH;
					}
				};
				/^#INCLUDE\sTrigger\.pm$/ && do {
					if (exists &Trigger::New) {
						$Trigger->Destroy;
						delete_package('Trigger');
						undef $Trigger;
						last SWITCH;
					}
				};
			}
		}
	}
}
#------------------------------------------------------------------------------#
# Sub: GotSigHup                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub GotSigHup {
	print "Got SIGHUP, re-reading config...";
	Config();
	print "done.\n";
}

#------------------------------------------------------------------------------#
# Sub: CheckConnections                                                        #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub CheckConnections {
	# Get the readable handles. timeout is 0, only process stuff that can be
	# read NOW.
	my $return = "";
	my @ready = $Select->can_read(0);
	foreach my $socket (@ready) {
		if ($socket == $SERVER) {
			my $new = $socket->accept();
			$Select->add($new);
		} else {
			my $buf = "";
			my $char = getc($socket);
			while ($char ne "\n") {
				$buf .= $char;
				$char = getc($socket);
			}
			$return = ProcessRequest($buf);
			$socket->send($return);
			$Select->remove($socket);
			close($socket);
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: Debug                                                                   #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Debug {
	my $debug = shift @ARGV;

	# first arg is debug-operation.
	if (!(defined $debug)) {
		print "debug is missing an operation.\n";
		exit;
	}

	# database
	if ($debug =~ /db/) {
		my $dbcfg = shift @ARGV;
		if (!(defined $dbcfg)) {
			print "debug database is missing an argument.\n";
			exit;
		}
		print "debugging database...\n";
		# require
		require FluxDB;
		# create instance
		print "creating FluxDB\n";
		$fluxDB = FluxDB->new();
		if ($fluxDB->getState() == -1) {
			print " error : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# initialize
		print "initializing FluxDB( \"".$dbcfg."\")\n";
		$fluxDB->initialize($dbcfg);
		if ($fluxDB->getState() == -1) {
			print " error : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# hmm
		if ($fluxDB->getState() == 0) {
			print " hmm, FluxDB has state 0 : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# db-settings
		print "FluxDB->getDatabaseType : \"".$fluxDB->getDatabaseType()."\"\n";
		print "FluxDB->getDatabaseName : \"".$fluxDB->getDatabaseName()."\"\n";
		print "FluxDB->getDatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n";
		print "FluxDB->getDatabasePort : \"".$fluxDB->getDatabasePort()."\"\n";
		print "FluxDB->getDatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n";
		print "FluxDB->getDatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n";
		# something from the bean
		print "fluxDB->getFluxConfig(\"path\") : \"".$fluxDB->getFluxConfig("path")."\"\n";
		# destroy
		print "destroying FluxDB\n";
		$fluxDB->destroy();
		exit;
	}

	print "debug is missing an operation.\n";
	exit;
}
