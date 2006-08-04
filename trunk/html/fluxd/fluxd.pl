#!/usr/bin/perl
################################################################################
# fluxd.pl                                                                     #
#------------------------------------------------------------------------------#
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
use vars qw( @users %names);
my $BIN_PHP = "/usr/bin/php";
my $BIN_FLUXDCLI = "fluxdcli.php";
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
my ( $REVISION, $DIR, $PROG, $EXTENSION );
my $start_time = time();

#------------------------------------------------------------------------------#
# Class reference variables                                                    #
#------------------------------------------------------------------------------#
use vars qw( $Queue $Socket $Watch $Client $Trigger);

#------------------------------------------------------------------------------#
# Main                                                                         #
#------------------------------------------------------------------------------#

# flush the buffer
$| = 1;

# Verify that we have been started in a valid way
VerifyArguments();

# Intialize
Initialize();

# Daemonise the script
&Daemonize;

# Read config and load modules
Config();

use vars qw( $loop );
$loop = 0;
# Here we go! The main loop!
while ( 1 ) {
	CheckConnections();
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

		/^die/ && do {
			$return = StopServer();
			last SWITCH;
		};
		/^stop/ && do {
			$return = Qmgr::StopTorrent(shift);
			last SWITCH;
		};
		/^start/ && do {
			$return = StartTorrent(shift);
			last SWITCH;
		};
		/^count-jobs/ && do {
			$return = CountJobs();
			last SWITCH;
		};
		/^count-queue/ && do {
			$return = CountQueue();
			last SWITCH;
		};
		/^list-queue/ && do {
			$return = ListQueue();
			last SWITCH;
		};
		/^watch/ && do {
			$return = Watch(shift, shift);
			last SWITCH;
		};
		/^trigger/ && do {
			$return = Trigger(shift, shift);
			last SWITCH;
		};
		/^status/ && do {
			$return .= Status();
			last SWITCH;
		};
		/^set/ && do {
			$return = Set(shift, shift);
			last SWITCH;
		};
		/^check/ && do {
			$return = Check();
			last SWITCH;
		};
		/^torrents/ && do {
			$return = Torrents();
			last SWITCH;
		};
		/^netstat/ && do {
			$return = Netstat();
			last SWITCH;
		};
		/^start-all/ && do {
			$return = StartAll();
			last SWITCH;
		};
		/^stop-all/ && do {
			$return = StopAll();
			last SWITCH;
		};
		/^resume-all/ && do {
			$return = ResumeAll();
			last SWITCH;
		};
		/^reset/ && do {
			$return = Reset(shift);
			last SWITCH;
		};
		/^delete/ && do {
			$return = Delete(shift);
			last SWITCH;
		};
		/^wipe/ && do {
			$return = Wipe(shift);
			last SWITCH;
		};
		/^inject/ && do {
			$return = Inject(shift, shift);
			last SWITCH;
		};
		$return = PrintUsage();
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: Inject                                                                  #
# Arguments: /path/to/foo.torrent, username                                    #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Inject {
}

#------------------------------------------------------------------------------#
# Sub: Wipe                                                                    #
# Arguments: torrent name                                                      #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Wipe {
}

#------------------------------------------------------------------------------#
# Sub: Delete                                                                  #
# Arguments: torrent name                                                      #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Delete {
}

#------------------------------------------------------------------------------#
# Sub: Reset                                                                   #
# Arguments: torrent name                                                      #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Reset {
}

#------------------------------------------------------------------------------#
# Sub: ResumeAll                                                               #
# Arguments: Null                                                              #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub ResumeAll {
}

#------------------------------------------------------------------------------#
# Sub: StopAll                                                                 #
# Arguments: Null                                                              #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub StopAll {
}

#------------------------------------------------------------------------------#
# Sub: StartAll                                                                #
# Arguments: Null                                                              #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub StartAll {
}

#------------------------------------------------------------------------------#
# Sub: Netstat                                                                 #
# Arguments:                                                                   #
# Returns: list of open connections (port and host)                            #
#------------------------------------------------------------------------------#
sub Netstat {
}

#------------------------------------------------------------------------------#
# Sub: Torrents                                                                #
# Arguments: Null                                                              #
# Returns: information on torrents and speed                                   #
#------------------------------------------------------------------------------#
sub Torrents {
}

#------------------------------------------------------------------------------#
# Sub: Check                                                                   #
# Arguments: Null                                                              #
# Returns: info on system requirements                                         #
#------------------------------------------------------------------------------#
sub Check {
}

#------------------------------------------------------------------------------#
# Sub: Status                                                                  #
# Arguments: Null                                                              #
# Returns: Server information page                                             #
#------------------------------------------------------------------------------#
sub Status {
	my $retval = "";
	$retval .= "Fluxd has been up since $start_time\n";
	print "Retval is $retval\n";
	return $retval;
}

#------------------------------------------------------------------------------#
# Sub: Set                                                                     #
# Arguments: Variable, [Value]                                                 #
# Returns: info string                                                         #
#------------------------------------------------------------------------------#
sub Set {
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
# Sub: VerifyArguments                                                         #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub VerifyArguments {
	my $temp = shift @ARGV;
	if ( (!(defined $temp)) && ($temp !~/\d+/) ) {
		PrintUsage();
		exit;
	}
	$MAX_SYS = $temp;

	$temp = shift @ARGV;
	if ( (!(defined $temp)) && ($temp !~/\d+/) ) {
		PrintUsage();
		exit;
	}
	$MAX_USER = $temp;

	$temp = shift @ARGV;
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	$PATH_PHP = $temp;

	$temp = shift @ARGV;
	if (!(defined $temp)) {
		PrintUsage();
		exit;
	}
	InitPaths($temp);

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
	$REVISION = do { my @r = (q$Revision: 1 $ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
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
	chdir '/'			or die "Can't chdir to /: $!";
	umask 0;			# sets our umask
	open STDIN, "/dev/null" 	or die "Can't read /dev/null: $!";
	open STDOUT, ">>$LOG"		or die "Can't Write to $LOG: $!";
	open STDERR, ">>$ERROR_LOG"	or die "Can't Write to error $ERROR_LOG: $!";
	defined(my $pid = fork)		or die "Can't fork: $!";
	exit if $pid;
	setsid				or die "Can't start a new session: $!";

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

        # First, get our DB info
        GetDBInfo();

        # Now execute the SQL and get the user list
        my @tempary;
        if ($DB_TYPE eq "mysql") {
                @tempary = split(/\n/, `echo "select uid, user_id FROM tf_users" | mysql -u $DB_USER -h $DB_HOST -p$DB_PASS $DB_NAME`);
        }
        my $index = 0;
        foreach (@tempary) {
                if (!/^uid\suser_id/) {
                        my @array = split;
                        @_ = @array;
                        my $uid = shift || die("Can't get uid from DB, dying: $!");
                        my $user_id = shift || die("Can't get user_id from DB, dying: $!");
                        $users[$index] = {
                                uid             => $uid,
                                username        => $user_id,
                                        };
                        $names{$user_id} = $uid;
                }
                $index++;
        }
}

#------------------------------------------------------------------------------#
# Sub: PrintUsage                                                              #
# Arguments: Null                                                              #
# Returns: Usage Information                                                   #
#------------------------------------------------------------------------------#
sub PrintUsage {
	print <<"USAGE";
$PROG.$EXTENSION Revision $REVISION

Usage: $PROG.$EXTENSION <begin> max-running, max-user, path-to-php,
                              path-to-download, loglevel
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

USAGE
}

#------------------------------------------------------------------------------#
# Sub: GetDBInfo                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub GetDBInfo {
	my $file='/usr/local/www/trunk/html/config.db.php';
	open(CONFIG, $file) || die("Cannot open $file for read: $!");
	undef $/;
	while (<CONFIG>) {
		if (/db_type.*[^\[]\"(\w+)\"[^\]]/) {
			$DB_TYPE = $1;
		}
		if (/db_host.*[^\[]\"(\w+)\"[^\]]/) {
			$DB_HOST = $1;
		}
		if (/db_name.*[^\[]\"(\w+)\"[^\]]/) {
			$DB_NAME = $1;
		}
		if (/db_user.*[^\[]\"(\w+)\"[^\]]/) {
			$DB_USER = $1;
		}
		if (/db_pass.*[^\[]\"(\w+)\"[^\]]/) {
			$DB_PASS = $1;
		}
	}
	$/ = '\n';
}

#------------------------------------------------------------------------------#
# Sub: PrintVersion                                                            #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub PrintVersion {
        print "fluxd.pl Revision ".$REVISION."\n";
}

#------------------------------------------------------------------------------#
# Sub: Config                                                                  #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Config {
	open(CONFIG, "/usr/local/www/trunk/html/fluxd/fluxd.conf") || die("Can't open fluxd.conf: $!");
	while (<CONFIG>) {
		SWITCH: {
			if(/^INCLUDE/) {
				# Load up modules, unless they're already
				# loaded.
				/Qmgr\.pm$/ && do {
					if (!(exists &Qmgr::New)) {
						require Qmgr;
						$Queue = Qmgr->New();
						last SWITCH;
					}
				};
				/Fluxinet\.pm$/ && do {
					if (!(exists &Fluxinet::New)) {
						require Fluxinet;
						$Socket = Fluxinet->New();
						last SWITCH;
					}
				};
				/Watch\.pm$/ && do {
					if (!(exists &Watch::New)) {
						require Watch;
						$Watch = Watch->New();
						last SWITCH;
					}
				};
				/Clientmaint\.pm$/ && do {
					if (!(exists &Clientmaint::New)) {
						require Clientmaint;
						$Client = Clientmaint->New();
						last SWITCH;
					}
				};
				/Trigger\.pm$/ && do {
					if (!(exists &Trigger::New)) {
						require Trigger;
						$Trigger = Trigger->New();
						last SWITCH;
					}
				};
			}
			if (/^#INCLUDE/) {
				# Load up modules, as long as they aren't
				# already loaded.
				/Qmgr\.pm$/ && do {
					if(exists &Qmgr::New) {
						delete_package('Qmgr');
						last SWITCH;
					}
				};
				/Fluxinet\.pm$/ && do {
					if (exists &Fluxinet::New) {
						delete_package('Fluxinet');
						last SWITCH;
					}
				};
				/Watch\.pm$/ && do {
					if (exists &Watch::New) {
						delete_package('Watch');
						last SWITCH;
					}
				};
				/Clientmaint\.pm$/ && do {
					if (exists &Clientmaint::New) {
						delete_package('Clientmaint');
						last SWITCH;
					}
				};
				/Trigger\.pm$/ && do {
					if (exists &Trigger::New) {
						delete_package('Trigger');
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
	print "Got SIGHUP, rea-reading config...";
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

