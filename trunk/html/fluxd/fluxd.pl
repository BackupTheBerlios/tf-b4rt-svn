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

################################################################################
# fields                                                                       #
################################################################################
#
my $BIN_FLUXCLI = "fluxcli.php";
my $FILE_DBCONF = "config.db.php";
my $PATH_TORRENT_DIR = ".torrents";
my $PATH_DATA_DIR = ".fluxd";
my $PATH_SOCKET = "fluxd.sock";
my $ERROR_LOG = "fluxd-error.log";
my $LOG = "fluxd.log";
my $PID_FILE = "fluxd.pid";
my $PATH_QUEUE_FILE = "fluxd.queue";
#
my ( $VERSION, $DIR, $PROG, $EXTENSION );
my $PATH_DOCROOT;
my $SERVER;
my $Select = new IO::Select();
my $start_time = time();

#------------------------------------------------------------------------------#
# Class reference variables                                                    #
#------------------------------------------------------------------------------#
use vars qw( $fluxDB $qmgr $fluxinet $watch $clientmaint $trigger );


################################################################################
# main                                                                         #
################################################################################

# flush the buffer
$| = 1;

# intialize
initialize();

# Verify that we have been started in a valid way
processArguments();

# daemonise the script
&daemonize();

# load flux-service-modules
loadServiceModules();

# Here we go! The main loop!
use vars qw( $loop );
$loop = 0;
while ( 1 ) {

	# check Connections
	checkConnections();

	# Qmgr
	if ((defined $qmgr) && ($qmgr->getState() == 1)) {
		eval { $qmgr->main(); };
	}

	# Fluxinet
	if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
		eval { $fluxinet->main(); };
	}

	# Watch
	if ((defined $watch) && ($watch->getState() == 1)) {
		eval { $watch->main(); };
	}

	# Clientmaint
	if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
		eval { $clientmaint->main(); };
	}

	# Trigger
	if ((defined $trigger) && ($trigger->getState() == 1)) {
		eval { $trigger->main(); };
	}

	# TODO : sleep-code                                                         /* TODO */
	sleep 1; # DEBUG

	# increment loop-counter
	$loop++;
}

#------------------------------------------------------------------------------#
# Sub: initialize                                                              #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub initialize {
	# Windows is not supported
	if ("$^0" =~ /win32/i) {
		print "\r\nWin32 not supported.\r\n";
		exit;
	}
	# initialize some variables
	$VERSION = do {
		my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
	($DIR=$0) =~ s/([^\/\\]*)$//;
	($PROG=$1) =~ s/\.([^\.]*)$//;
	$EXTENSION=$1;
}

#------------------------------------------------------------------------------#
# Sub: processArguments                                                        #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub processArguments {
	my $temp = shift @ARGV;

	# first arg is operation.
	if (!(defined $temp)) {
		printUsage();
		exit;
	}
	# help
	if ($temp =~ /.*(help|-h).*/) {
		printUsage();
		exit;
	}
	# version
	if ($temp =~ /.*(version|-v).*/) {
		printVersion();
		exit;
	};
	# check
	if ($temp =~ /check/) {
		check();
		exit;
	};

	# debug
	if ($temp =~ /debug/) {
		debug();
		exit;
	};

	# TODO : more ops                                                           /* TODO */

	# daemon-stop
	if ($temp =~ /daemon-stop/) {
		# $PATH_DOCROOT
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$PATH_DOCROOT = $temp;
		print "Stopping daemon...\n";
		# db-bean
		# require
		require FluxDB;
		# create instance
		$fluxDB = FluxDB->new();
		# initialize
		$fluxDB->initialize($PATH_DOCROOT . $FILE_DBCONF);
		if ($fluxDB->getState() < 1) {
			print "Error : Problems initializing FluxDB : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# init paths
		initPaths($fluxDB->getFluxConfig("path"));
		if (-f $PID_FILE) {
			# get pid
			open(PIDFILE,"< $PID_FILE");
			my $daemonPid = <PIDFILE>;
			close(PIDFILE);
			chomp $daemonPid;
			# send QUIT to daemon
			kill 'SIGQUIT', $daemonPid;
		} else {
			print "Error : cant find pid-file (".$PID_FILE."), daemon running ?\n";
		}
		# exit
		exit;
	};

	# daemon-start
	if ($temp =~ /daemon-start/) {
		# $PATH_DOCROOT
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$PATH_DOCROOT = $temp;
		print "Starting up daemon with docroot ".$PATH_DOCROOT."\n"; # DEBUG
		# return
		return 1;
	};

	# hmmm dont know this arg, print usage screen
	printUsage();
	exit;
}

#------------------------------------------------------------------------------#
# Sub: daemonize                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub daemonize {

	# db-bean

	# require
	require FluxDB;

	# create instance
	$fluxDB = FluxDB->new();
	if ($fluxDB->getState() == -1) {
		print "Error : creating FluxDB: ".$fluxDB->getMessage()."\n";
		exit;
	}

	# initialize
	$fluxDB->initialize($PATH_DOCROOT . $FILE_DBCONF);
	if ($fluxDB->getState() < 1) {
		print "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n";
		exit;
	}

	# init paths
	initPaths($fluxDB->getFluxConfig("path"));

	# check for pid-file : if exists bail out
	if (-f $PID_FILE) {
		print "Error : pid-file (".$PID_FILE.") exists. daemon running ?\n";
		exit;
	}

	# check for socket : if exists bail out
	if (-r $PATH_SOCKET) {
		print "Error : socket (".$PATH_SOCKET.") exists. daemon running ?\n";
		exit;
	}

	#chdir '/'			or die "Can't chdir to /: $!";
	umask 0;			# sets our umask
	open STDIN, "/dev/null" 	or die "Can't read /dev/null: $!";
	open STDOUT, ">>$LOG"		or die "Can't Write to $LOG: $!";
	open STDERR, ">>$ERROR_LOG"	or die "Can't Write to error $ERROR_LOG: $!";
	defined(my $pid = fork)		or die "Can't fork: $!";
	exit if $pid;
	setsid				or die "Can't start a new session: $!";

	# log
	print STDOUT "Starting up daemon with docroot ".$PATH_DOCROOT." (pid: ".$$.")\n"; # DEBUG

	# write out pid-file
	writePidFile($$);

	# check requirements, die if they aren't there
	#if (!(check())) {
	#	exit;
	#}

	# set up our signal handlers
	$SIG{HUP} = \&gotSigHup;
	$SIG{QUIT} = \&gotSigQuit;

	# set up daemon stuff...

	# set up server socket
	$SERVER = IO::Socket::UNIX->new(
			Type    => SOCK_STREAM,
			Local   => $PATH_SOCKET,
			Listen  => 16,
			Reuse   => 1,
			);
	die "Couldn't create socket: $!\n" unless $SERVER;
	print STDOUT "created socket ".$PATH_SOCKET."\n"; # DEBUG

	# Add our server socket to the select read set.
	$Select->add($SERVER);
}

#------------------------------------------------------------------------------#
# Sub: daemonShutdown                                                          #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub daemonShutdown {
	print "Shutting down!\n";

	# remove socket
	print STDOUT "deleting socket ".$PATH_SOCKET."\n"; # DEBUG
	unlink($PATH_SOCKET);

	# destroy db-bean
	if (defined($fluxDB)) {
		$fluxDB->destroy();
	}

	# remove pid-file
	deletePidFile();

	# get out here
	exit;
}

#------------------------------------------------------------------------------#
# Sub: initPaths                                                               #
# Arguments: base path for t-flux                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub initPaths {
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
# Sub: loadServiceModules                                                      #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadServiceModules {

	# Qmgr
	if ($fluxDB->getFluxConfig("fluxd_Qmgr_enabled") == 1) {
		# Load up module, unless it is already
		if (!(exists &Qmgr::new)) {
			require Qmgr;
			$qmgr = Qmgr->new();
			$qmgr->initialize();
			if ($qmgr->getState() < 1) {
				print STDERR "error initializing service-module Qmgr :\n";
				print STDERR $qmgr->getMessage()."\n";
				# TODO : handle this                                            /* TODO */
			}
		}
	} else {
		# Unload module, if it is loaded
		if(exists &Qmgr::new) {
			$qmgr->destroy();
			delete_package('Qmgr');
			undef $qmgr;
		}
	}

	# Fluxinet
	if ($fluxDB->getFluxConfig("fluxd_Fluxinet_enabled") == 1) {
		# Load up module, unless it is already
		if (!(exists &Fluxinet::new)) {
			require Fluxinet;
			$fluxinet = Fluxinet->new();
			$fluxinet->initialize($fluxDB->getFluxConfig("fluxd_Fluxinet_port"));
			if ($fluxinet->getState() < 1) {
				print STDERR "error initializing service-module Fluxinet :\n";
				print STDERR $fluxinet->getMessage()."\n";
				# TODO : handle this                                            /* TODO */
			}
		}
	} else {
		# Unload module, if it is loaded
		if (exists &Fluxinet::new) {
			$fluxinet->destroy();
			delete_package('Fluxinet');
			undef $fluxinet;
		}
	}

	# Watch
	if ($fluxDB->getFluxConfig("fluxd_Watch_enabled") == 1) {
		# Load up module, unless it is already
		if (!(exists &Watch::new)) {
			require Watch;
			$watch = Watch->new();
			$watch->initialize($fluxDB->getFluxConfig("fluxd_Watch_jobs"));
			if ($watch->getState() < 1) {
				print STDERR "error initializing service-module Watch :\n";
				print STDERR $watch->getMessage()."\n";
				# TODO : handle this                                            /* TODO */
			}
		}
	} else {
		# Unload module, if it is loaded
		if (exists &Watch::new) {
			$watch->destroy();
			delete_package('Watch');
			undef $watch;
		}
	}

	# Clientmaint
	if ($fluxDB->getFluxConfig("fluxd_Clientmaint_enabled") == 1) {
		# Load up module, unless it is already
		if (!(exists &Clientmaint::new)) {
			require Clientmaint;
			$clientmaint = Clientmaint->new();
			$clientmaint->initialize();
			if ($clientmaint->getState() < 1) {
				print STDERR "error initializing service-module Clientmaint :\n";
				print STDERR $clientmaint->getMessage()."\n";
				# TODO : handle this                                            /* TODO */
			}
		}
	} else {
		# Unload module, if it is loaded
		if (exists &Clientmaint::new) {
			$clientmaint->destroy();
			delete_package('Clientmaint');
			undef $clientmaint;
		}
	}

	# Trigger
	if ($fluxDB->getFluxConfig("fluxd_Trigger_enabled") == 1) {
		# Load up module, unless it is already
		if (!(exists &Trigger::new)) {
			require Trigger;
			$trigger = Trigger->new();
			$trigger->initialize();
			if ($trigger->getState() < 1) {
				print STDERR "error initializing service-module Trigger :\n";
				print STDERR $trigger->getMessage()."\n";
				# TODO : handle this                                            /* TODO */
			}
		}
	} else {
		# Unload module, if it is loaded
		if (exists &Trigger::new) {
			$trigger->destroy;
			delete_package('Trigger');
			undef $trigger;
		}
	}

}

#------------------------------------------------------------------------------#
# Sub: gotSigHup                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub gotSigHup {
	print "Got SIGHUP, re-loading service-modules...";
	loadServiceModules();
	print "done.\n";
}

#------------------------------------------------------------------------------#
# Sub: gotSigHup                                                               #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub gotSigQuit {
	daemonShutdown();
}

#------------------------------------------------------------------------------#
# Sub: checkConnections                                                        #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub checkConnections {
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
			$return = processRequest($buf);
			$socket->send($return);
			$Select->remove($socket);
			close($socket);
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: processRequest                                                          #
# Arguments: Command                                                           #
# Returns: String info on command success/failure                              #
#------------------------------------------------------------------------------#
sub processRequest {
	my @array = ();
	my $temp = shift;
	@array = split (/ /, $temp);
	@_ = @array;
	my $return;

	SWITCH: {
		$_ = shift;

		# Actual fluxd subroutine calls
		/^die/ && do {
			$return = daemonShutdown();
			last SWITCH;
		};
		/^status/ && do {
			$return = status();
			last SWITCH;
		};
		/^check/ && do {
			$return = check();
			last SWITCH;
		};
		/^set/ && do {
			$return = set(shift, shift);
			last SWITCH;
		};

		# fluxcli.php calls
		/^start|^stop|^inject|^wipe|^delete|^reset|^\w+-all|^torrents|^netstat/ && do {
			$return = fluxcli($_, shift, shift);
			last SWITCH;
		};

		# Package calls
		if (exists &Qmgr::main) {
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
		if (exists &Watch::setWatch) {
			/^watch/ && do {
				$return = Watch::setWatch(shift, shift);
				last SWITCH;
			};
		}
		if (exists &Trigger::setTrigger) {
			/^trigger/ && do {
				$return = Trigger::setTrigger(shift, shift);
				last SWITCH;
			};
		}

		# Default case.
		$return = printUsage();
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: set                                                                     #
# Arguments: Variable, [Value]                                                 #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub set {
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
				$return = $qmgr->set($pair[1], $value) if (defined $qmgr);
				last SWITCH;
			};
			/Fluxinet/ && do {
				$return = $fluxinet->set($pair[1], $value) if(defined $fluxinet);
				last SWITCH;
			};
			/Trigger/ && do {
				$return = $trigger->set($pair[1], $value) if(defined $trigger);
				last SWITCH;
			};
			/Watch/ && do {
				$return = $watch->set($pair[1], $value) if(defined $watch);
				last SWITCH;
			};
			/Clientmaint/ && do {
				$return = $clientmaint->set($pair[1], $value) if(defined $clientmaint);
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
# Sub: fluxcli                                                                 #
# Arguments: Command [Arg1, [Arg2]]                                            #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub fluxcli {
	my $Command = shift;
	my $Arg1 = shift;
	my $Arg2 = shift;
	my $return;

	if ($Command =~/^torrents|^netstat|^\w+-all|^repair/) {
		if ( (defined $Arg1) || (defined $Arg2) ) {
			$return = printUsage();
			next;
		} else {
			my $shellCmd = $fluxDB->getFluxConfig("bin_php");
			$shellCmd .= " ".$BIN_FLUXCLI." ".$Command;
			$return = `$shellCmd`;
			next;
		}
	}
	if ($Command =~/^start|^stop|^reset|^delete|^wipe|^xfer/) {
		if ( (!(defined $Arg1)) || (defined $Arg2) ) {
			$return = printUsage();
			next;
		} else {
			my $shellCmd = $fluxDB->getFluxConfig("bin_php");
			$shellCmd .= " ".$BIN_FLUXCLI." ".$Command." ".$Arg1;
			$return = `$shellCmd`;
			next;
		}
	}
	if ($Command =~/^inject/) {
		if ( (!(defined $Arg1)) || (!(defined $Arg2)) ) {
			$return = printUsage();
			next;
		} else {
			my $shellCmd = $fluxDB->getFluxConfig("bin_php");
			$shellCmd .= " ".$BIN_FLUXCLI." ".$Command." ".$Arg1." ".$Arg2;
			$return = `$shellCmd`;
			next;
		}
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: writePidFile                                                            #
# Arguments: int with pid                                                      #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub writePidFile {
	my $pid = shift;
	if (!(defined $pid)) {
		$pid = $$;
	}
	print STDOUT "writing pid-file ".$PID_FILE." (pid: ".$pid.")\n"; # DEBUG
	open(PIDFILE,">$PID_FILE");
	print PIDFILE $pid."\n";
	close(PIDFILE);
}

#------------------------------------------------------------------------------#
# Sub: deletePidFile                                                           #
# Arguments: null                                                              #
# Returns: return-val of delete                                                #
#------------------------------------------------------------------------------#
sub deletePidFile {
	print STDOUT "deleting pid-file ".$PID_FILE."\n"; # DEBUG
	return unlink($PID_FILE);
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Server information page                                             #
#------------------------------------------------------------------------------#
sub status {
	my $retval = "";
	$retval .= "Fluxd has been up since $start_time\n";
	return $retval;
}

#------------------------------------------------------------------------------#
# Sub: printUsage                                                              #
# Arguments: Null                                                              #
# Returns: Usage Information                                                   #
#------------------------------------------------------------------------------#
sub printUsage {
	print <<"USAGE";

$PROG.$EXTENSION Revision $VERSION

Usage: $PROG.$EXTENSION <daemon-start> path-to-docroot
                        starts fluxd daemon
       $PROG.$EXTENSION <daemon-stop> path-to-docroot
                        stops fluxd daemon
       $PROG.$EXTENSION <start|stop|reset|delete|wipe> foo.torrent
                        starts, stops, resets totals, deletes, or deletes
                        and resets totals for a torrent, as well as removing
                        all data downloaded for that torrent
       $PROG.$EXTENSION <torrents|status|netstat|start-all|stop-all|resume-all>
                        lists info about the selected aspect. status shows all
       $PROG.$EXTENSION inject /path/to/foo.torrent user
                        injects a torrent file into flux as the specified user
       $PROG.$EXTENSION watch /path/to/watch/dir user
                        sets fluxd to watch the specified directory and upload
                        torrents entered in it as the specified user
       $PROG.$EXTENSION set <LOGLEVEL|MAX_USR|MAX_SYS> [VALUE]
                        if given without a value argument, returns current
                        value of the given variable. If given with a value
                        argument, sets the given variable to that value
       $PROG.$EXTENSION <count-jobs|count-queue|list-queue|check>
                        returns the number of jobs, number of entries in the
                        queue, list entries in the queue, or check to ensure
                        that this computer has everything fluxd needs.
       $PROG.$EXTENSION repair
                        repairs torrentflux. DO NOT DO THIS if your system
                        is running as it should. You WILL break something.

       $PROG.$EXTENSION check path-to-docroot
                        checks for requirements.
       $PROG.$EXTENSION <-h|--help>
                        print out help screen.
       $PROG.$EXTENSION <-v|--version>
                        print out version-info

USAGE
}

#------------------------------------------------------------------------------#
# Sub: printVersion                                                            #
# Arguments: Null                                                              #
# Returns: Version Information                                                 #
#------------------------------------------------------------------------------#
sub printVersion {
	print $PROG.".".$EXTENSION." Version ".$VERSION."\n";

	# FluxDB
	require FluxDB;
	print "FluxDB Version ".FluxDB->getVersion()."\n";

	# Clientmaint
	require Clientmaint;
	print "Clientmaint Version ".Clientmaint->getVersion()."\n";

	# Fluxinet
	require Fluxinet;
	print "Fluxinet Version ".Fluxinet->getVersion()."\n";

	# Qmgr                                                                      /* TODO */
	#require Qmgr;
	#print "Qmgr Version ".Qmgr->getVersion()."\n";
	print "Qmgr Version ?\n";

	# Trigger
	require Trigger;
	print "Trigger Version ".Trigger->getVersion()."\n";

	# Watch
	require Watch;
	print "Watch Version ".Watch->getVersion()."\n";
}

#------------------------------------------------------------------------------#
# Sub: check                                                                   #
# Arguments: Null                                                              #
# Returns: info on system requirements                                         #
#------------------------------------------------------------------------------#
sub check {
	print "Checking requirements...\n";
	my $return = 0;

	# $PATH_DOCROOT
	my $temp = shift @ARGV;
	if (!(defined $temp)) {
		printUsage();
		exit;
	}
	if (!((substr $temp, -1) eq "/")) {
		$temp .= "/";
	}
	$PATH_DOCROOT = $temp;

	# 1. perl-modules
	print "1. perl-modules\n";
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

	# 2. required files
	print "2. required files\n";
	print " - ".$FILE_DBCONF." : ";
	if (-f $PATH_DOCROOT.$FILE_DBCONF) {
		print $PATH_DOCROOT.$FILE_DBCONF."\n";
	} else {
		print "Error : cant find database-config ".$FILE_DBCONF." in ".$PATH_DOCROOT."\n";
	}
	print " - ".$BIN_FLUXCLI." : ";
	if (-f $PATH_DOCROOT.$BIN_FLUXCLI) {
		print $PATH_DOCROOT.$BIN_FLUXCLI."\n";
	} else {
		print "\nError : cant find fluxcli ".$BIN_FLUXCLI." in ".$PATH_DOCROOT."\n";
	}

	# 3. db-bean
	print "3. database\n";

	# require
	require FluxDB;

	# create instance
	$fluxDB = FluxDB->new();
	if ($fluxDB->getState() == -1) {
		print "Error : creating FluxDB: ".$fluxDB->getMessage()."\n";
		exit;
	}

	# initialize
	$fluxDB->initialize($PATH_DOCROOT . $FILE_DBCONF);
	if ($fluxDB->getState() < 1) {
		print "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n";
		exit;
	}

	# db-settings
	print " - Type : ".$fluxDB->getDatabaseType()."\n";
	print " - Name : ".$fluxDB->getDatabaseName()."\n";
	print " - Host : ".$fluxDB->getDatabaseHost()."\n";

	# init paths
	initPaths($fluxDB->getFluxConfig("path"));

	# 4. paths
	print "4. paths\n";
	print " - flux-data-dir : ".$fluxDB->getFluxConfig("path")."\n";
	print " - PATH_TORRENT_DIR : ".$PATH_TORRENT_DIR."\n";
	print " - PATH_DATA_DIR : ".$PATH_DATA_DIR."\n";
	print " - PATH_SOCKET : ".$PATH_SOCKET."\n";
	print " - ERROR_LOG : ".$ERROR_LOG."\n";
	print " - LOG : ".$LOG."\n";
	print " - PID_FILE : ".$PID_FILE."\n";
	print " - PATH_QUEUE_FILE : ".$PATH_QUEUE_FILE."\n";

	# 5. service-modules
	print "5. service-modules\n";

	# Qmgr
	#print " - Qmgr : ";
	#require Qmgr;
	#$qmgr = Qmgr->new();
	#$qmgr->initialize();
	#if ($qmgr->getState() < 1) {
	#	print STDERR "error initializing service-module Qmgr :\n";
	#	print STDERR $qmgr->getMessage()."\n";
	#}

	# Fluxinet
	print " - Fluxinet : ";
	require Fluxinet;
	$fluxinet = Fluxinet->new();
	$fluxinet->initialize($fluxDB->getFluxConfig("fluxd_Fluxinet_port"));
	if ($fluxinet->getState() < 1) {
		print STDERR "error initializing service-module Fluxinet :\n";
		print STDERR $fluxinet->getMessage()."\n";
	}

	# Watch
	print " - Watch : ";
	require Watch;
	$watch = Watch->new();
	$watch->initialize($fluxDB->getFluxConfig("fluxd_Watch_jobs"));
	if ($watch->getState() < 1) {
		print STDERR "error initializing service-module Watch :\n";
		print STDERR $watch->getMessage()."\n";
	}

	# Clientmaint
	print " - Clientmaint : ";
	require Clientmaint;
	$clientmaint = Clientmaint->new();
	$clientmaint->initialize();
	if ($clientmaint->getState() < 1) {
		print STDERR "error initializing service-module Clientmaint :\n";
		print STDERR $clientmaint->getMessage()."\n";
	}

	# Trigger
	print " - Trigger : ";
	require Trigger;
	$trigger = Trigger->new();
	$trigger->initialize();
	if ($trigger->getState() < 1) {
		print STDERR "error initializing service-module Trigger :\n";
		print STDERR $trigger->getMessage()."\n";
	}

	# destroy fluxDB
	$fluxDB->destroy();
}

#------------------------------------------------------------------------------#
# Sub: debug                                                                   #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub debug {
	my $debug = shift @ARGV;

	# first arg is debug-operation.
	if (!(defined $debug)) {
		print "debug is missing an operation.\n";
		exit;
	}

	# database-debug
	if ($debug =~ /db/) {
		my $dbcfg = shift @ARGV;
		if (!(defined $dbcfg)) {
			print "debug database is missing an argument : path to docroot\n";
			exit;
		}
		if (!((substr $dbcfg, -1) eq "/")) {
			$dbcfg .= "/";
		}
		$dbcfg .= $FILE_DBCONF;
		print "debugging database...\n";
		# require
		require FluxDB;
		# create instance
		print "creating \$fluxDB\n";
		$fluxDB = FluxDB->new();
		if ($fluxDB->getState() == -1) {
			print " error : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# initialize
		print "initializing \$fluxDB(\"".$dbcfg."\")\n";
		$fluxDB->initialize($dbcfg);
		if ($fluxDB->getState() < 1) {
			print " hmm : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# db-settings
		print "\$fluxDB->getDatabaseType : \"".$fluxDB->getDatabaseType()."\"\n";
		print "\$fluxDB->getDatabaseName : \"".$fluxDB->getDatabaseName()."\"\n";
		print "\$fluxDB->getDatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n";
		print "\$fluxDB->getDatabasePort : \"".$fluxDB->getDatabasePort()."\"\n";
		print "\$fluxDB->getDatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n";
		print "\$fluxDB->getDatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n";
		# something from the bean
		print "\$fluxDB->getFluxConfig(\"path\") : \"".$fluxDB->getFluxConfig("path")."\"\n";
		print "\$fluxDB->getFluxConfig(\"bin_php\") : \"".$fluxDB->getFluxConfig("bin_php")."\"\n";
		# test to set a val
		print "\$fluxDB->getFluxConfig(\"default_theme\") : \"".$fluxDB->getFluxConfig("default_theme")."\"\n";
		$fluxDB->setFluxConfig("default_theme","foo");
		print "\$fluxDB->getFluxConfig(\"default_theme\") after set : \"".$fluxDB->getFluxConfig("default_theme")."\"\n";
		# now reload and check again
		$fluxDB->reload();
		print "\$fluxDB->getFluxConfig(\"default_theme\") after reload : \"".$fluxDB->getFluxConfig("default_theme")."\"\n";
		# destroy
		print "destroying \$fluxDB\n";
		$fluxDB->destroy();
		exit;
	}

	# bail out
	print "debug is missing an operation.\n";
	exit;
}
