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
use POSIX qw(setsid);
use FluxdCommon;
################################################################################

################################################################################
# fields                                                                       #
################################################################################
#
my $BIN_FLUXCLI = "fluxcli.php";
my $FILE_DBCONF = "config.db.php";
my $PATH_TRANSFER_DIR = ".transfers";
our $PATH_DATA_DIR = ".fluxd";
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
my $start_time_local = localtime();

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
my $loop = 1;
while ( $loop ) {

	# check Connections
	checkConnections();

	# Qmgr
	if ((defined $qmgr) && ($qmgr->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub { die "alarm\n" };
			alarm 15;
			$qmgr->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			print STDERR "Qmgr : Timed out\n";
			print STDERR $@."\n";
		}
	}

	# Fluxinet
	if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 3;
			$fluxinet->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			print STDERR "Fluxinet : Timed out\n";
			print STDERR $@."\n";
		}
	}

	# Watch
	if ((defined $watch) && ($watch->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 15;
			$watch->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			print STDERR "Watch : Timed out\n";
			print STDERR $@."\n";
		}
	}

	# Clientmaint
	if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 15;
			$clientmaint->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			print STDERR "Clientmaint : Timed out\n";
			print STDERR $@."\n";
		}
	}

	# Trigger
	if ((defined $trigger) && ($trigger->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 15;
			$trigger->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			print STDERR "Trigger : Timed out\n";
			print STDERR $@."\n";
		}
	}

	# sleep
	select undef, undef, undef, 0.1;
}

################################################################################
# subs                                                                         #
################################################################################

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
		$fluxDB->initialize($PATH_DOCROOT."inc/config/".$FILE_DBCONF);
		if ($fluxDB->getState() < 1) {
			print "Error : Problems initializing FluxDB : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# init paths
		initPaths(FluxDB->getFluxConfig("path"));
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
	$fluxDB->initialize($PATH_DOCROOT."inc/config/".$FILE_DBCONF);
	if ($fluxDB->getState() < 1) {
		print "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n";
		exit;
	}

	# init paths
	initPaths(FluxDB->getFluxConfig("path"));

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
	my $pwd = `pwd`;
	chop $pwd;
	print STDOUT "Starting up daemon with docroot ".$PATH_DOCROOT." (pid: ".$$." ; pwd: ".$pwd.")\n";

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

	# set main-loop-flag
	$loop = 0;

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
	$PATH_TRANSFER_DIR = $path.$PATH_TRANSFER_DIR."/";
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
	if (FluxDB->getFluxConfig("fluxd_Qmgr_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $qmgr)) {
			if (eval "require Qmgr") {
				eval {
					$qmgr = Qmgr->new();
					$qmgr->initialize(FluxDB->getFluxConfig("fluxd_Qmgr_interval"));
					if ($qmgr->getState() < 1) {
						print STDERR "error initializing service-module Qmgr :\n";
						print STDERR $qmgr->getMessage()."\n";
					}
				};
				if ($@) {
					print STDERR "error loading service-module Qmgr : $@\n";
				} else {
					# everything ok
					print STDOUT "Qmgr loaded\n"; # DEBUG
				}
			} else {
				print STDERR "error loading service-module Qmgr :$@\n";
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $qmgr) {
			eval {
				$qmgr->destroy();
				undef $qmgr;
			};
			if ($@) {
				print STDERR "error unloading service-module Qmgr : $@\n";
			} else {
				# everything ok
				print STDOUT "Qmgr unloaded\n"; # DEBUG
			}
		}
	}

	# Fluxinet
	if (FluxDB->getFluxConfig("fluxd_Fluxinet_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $fluxinet)) {
			if (eval "require Fluxinet") {
				eval {
					$fluxinet = Fluxinet->new();
					$fluxinet->initialize(FluxDB->getFluxConfig("fluxd_Fluxinet_port"));
					if ($fluxinet->getState() < 1) {
						print STDERR "error initializing service-module Fluxinet :\n";
						print STDERR $fluxinet->getMessage()."\n";
					}
				};
				if ($@) {
					print STDERR "error loading service-module Fluxinet : $@\n";
				} else {
					# everything ok
					print STDOUT "Fluxinet loaded\n"; # DEBUG
				}
			} else {
				print STDERR "error loading service-module Fluxinet : $@\n";
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $fluxinet) {
			eval {
				$fluxinet->destroy();
				undef $fluxinet;
			};
			if ($@) {
				print STDERR "error unloading service-module Fluxinet : $@\n";
			} else {
				# everything ok
				print STDOUT "Fluxinet unloaded\n"; # DEBUG
			}
		}
	}

	# Watch
	if (FluxDB->getFluxConfig("fluxd_Watch_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $watch)) {
			if (eval "require Watch") {
				eval {
					$watch = Watch->new();
					$watch->initialize(FluxDB->getFluxConfig("fluxd_Watch_interval"), FluxDB->getFluxConfig("fluxd_Watch_jobs"));
					if ($watch->getState() < 1) {
						print STDERR "error initializing service-module Watch :\n";
						print STDERR $watch->getMessage()."\n";
					}
				};
				if ($@) {
					print STDERR "error loading service-module Watch : $@\n";
				} else {
					# everything ok
					print STDOUT "Watch loaded\n"; # DEBUG
				}
			} else {
				print STDERR "error loading service-module Watch :$@\n";
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $watch) {
			eval {
				$watch->destroy();
				undef $watch;
			};
			if ($@) {
				print STDERR "error unloading service-module Watch : $@\n";
			} else {
				# everything ok
				print STDOUT "Watch unloaded\n"; # DEBUG
			}
		}
	}

	# Clientmaint
	if (FluxDB->getFluxConfig("fluxd_Clientmaint_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $clientmaint)) {
			if (eval "require Clientmaint") {
				eval {
					$clientmaint = Clientmaint->new();
					$clientmaint->initialize(FluxDB->getFluxConfig("fluxd_Clientmaint_interval"));
					if ($clientmaint->getState() < 1) {
						print STDERR "error initializing service-module Clientmaint :\n";
						print STDERR $clientmaint->getMessage()."\n";
					}
				};
				if ($@) {
					print STDERR "error loading service-module Clientmaint : $@\n";
				} else {
					# everything ok
					print STDOUT "Clientmaint loaded\n"; # DEBUG
				}
			} else {
				print STDERR "error loading service-module Clientmaint :$@\n";
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $clientmaint) {
			eval {
				$clientmaint->destroy();
				undef $clientmaint;
			};
			if ($@) {
				print STDERR "error unloading service-module Clientmaint : $@\n";
			} else {
				# everything ok
				print STDOUT "Clientmaint unloaded\n"; # DEBUG
			}
		}
	}

	# Trigger
	if (FluxDB->getFluxConfig("fluxd_Trigger_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $trigger)) {
			if (eval "require Trigger") {
				eval {
					$trigger = Trigger->new();
					$trigger->initialize(FluxDB->getFluxConfig("fluxd_Trigger_interval"));
					if ($trigger->getState() < 1) {
						print STDERR "error initializing service-module Trigger :\n";
						print STDERR $trigger->getMessage()."\n";
					}
				};
				if ($@) {
					print STDERR "error loading service-module Trigger : $@\n";
				} else {
					# everything ok
					print STDOUT "Trigger loaded\n"; # DEBUG
				}
			} else {
				print STDERR "error loading service-module Trigger :$@\n";
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $trigger) {
			eval {
				$trigger->destroy;
				undef $trigger;
			};
			if ($@) {
				print STDERR "error unloading service-module Trigger : $@\n";
			} else {
				# everything ok
				print STDOUT "Trigger unloaded\n"; # DEBUG
			}
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
	# have FluxDB reload the DB first, so we can see the changes
	if ($fluxDB->loadFluxConfig()) {
		loadServiceModules();
		print "done.\n";
	} else {
		print "Error\n";
		print STDERR "Error connecting to DB to read changes\n";
		print STDERR $fluxDB->getMessage()."\n";
	}
}

#------------------------------------------------------------------------------#
# Sub: gotSigQuit                                                              #
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
			while ((defined($char)) && ($char ne "\n")) {
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

		#print "processing request ".$_."\n"; # DEBUG

		# Actual fluxd subroutine calls
		/^die/ && do {
			$return = daemonShutdown();
			last SWITCH;
		};
		/^status/ && do {
			$return = status();
			last SWITCH;
		};
		/^modstate/ && do {
			$return = modState(shift);
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
		/^reloadDBCache/ && do {
			$return = $fluxDB->reload();
			last SWITCH;
		};
		/^reloadModules/ && do {
			$return = loadServiceModules();
			last SWITCH;
		};

		# fluxcli.php calls
		/^start|^stop|^inject|^wipe|^delete|^reset|^\w+-all|^torrents|^netstat|^watch/ && do {
			$return = fluxcli($_, shift, shift);
			last SWITCH;
		};

		# module-calls
		/^!(.+):(.+)/ && do {
			my $mod = $1;
			my $command = $2;
			$return = "";
			$_ = $mod;
			MODCALL: {
				/Qmgr/ && do {
					if ((defined $qmgr) && ($qmgr->getState() == 1)) {
						$return = $qmgr->command($command);
					}
					last SWITCH;
				};
				/Fluxinet/ && do {
					if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
						$return = $fluxinet->command($command);
					}
					last SWITCH;
				};
				/Trigger/ && do {
					if ((defined $trigger) && ($trigger->getState() == 1)) {
						$return = $trigger->command($command);
					}
					last SWITCH;
				};
				/Watch/ && do {
					if ((defined $watch) && ($watch->getState() == 1)) {
						$return = $watch->command($command);
					}
					last SWITCH;
				};
				/Clientmaint/ && do {
					if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
						$return = $clientmaint->command($command);
					}
					last SWITCH;
				};
			}
		};

		# Default case.
		$return = printUsage(1);
	}

	#print $return."\n"; # DEBUG

	# return
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

	if ($Command =~/^torrents|^netstat|^\w+-all|^repair/) {
		if ((defined $Arg1) || (defined $Arg2)) {
			return printUsage();
		} else {
			my $shellCmd = FluxDB->getFluxConfig("bin_php");
			$shellCmd .= " bin/".$BIN_FLUXCLI." ".$Command;
			return `$shellCmd`;
		}
	}
	if ($Command =~/^start|^stop|^reset|^delete|^wipe|^xfer/) {
		if ((!(defined $Arg1)) || (defined $Arg2)) {;
			return printUsage();
		} else {
			my $shellCmd = FluxDB->getFluxConfig("bin_php");
			$shellCmd .= " bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1;
			return `$shellCmd`;
		}
	}
	if ($Command =~/^inject|^watch/) {
		if ((!(defined $Arg1)) || (!(defined $Arg2))) {
			return printUsage();
		} else {
			my $shellCmd = FluxDB->getFluxConfig("bin_php");
			$shellCmd .= " bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1." ".$Arg2;
			return `$shellCmd`;
		}
	}

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
	my $head = "fluxd has been up since ".$start_time_local." (".FluxdCommon::niceTimeString($start_time).")\n\n";
	my $status = "";
	my $modules = "- Loaded Modules -\n";
	# Qmgr
	if ((defined $qmgr) && ($qmgr->getState() == 1)) {
		$modules .= "  * Qmgr.pm\n";
		$status .= $qmgr->status();
	}
	# Fluxinet
	if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
		$modules .= "  * Fluxinet.pm\n";
		$status .= eval { $fluxinet->status(); };
	}
	# Clientmaint
	if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
		$modules .= "  * Clientmaint.pm\n";
		$status .= eval { $clientmaint->status(); };
	}
	# Watch
	if ((defined $watch) && ($watch->getState() == 1)) {
		$modules .= "  * Watch.pm\n";
		$status .= eval { $watch->status(); };
	}
	# Trigger
	if ((defined $trigger) && ($trigger->getState() == 1)) {
		$modules .= "  * Trigger.pm\n";
		$status .= eval { $trigger->status(); };
	}
	# return
	return $head.$modules.$status;
}

#------------------------------------------------------------------------------#
# Sub: modState                                                                #
# Arguments: name of service-module                                            #
# Returns: state of service-module                                             #
#------------------------------------------------------------------------------#
sub modState {
	$_ = shift;
	if (!(defined $_)) {
		return 0;
	} else {
		/Qmgr/ && do {
			if (defined $qmgr) {
				return $qmgr->getState();
			} else {
				return 0;
			}
		};
		/Fluxinet/ && do {
			if (defined $fluxinet) {
				return $fluxinet->getState();
			} else {
				return 0;
			}
		};
		/Trigger/ && do {
			if (defined $trigger) {
				return $trigger->getState();
			} else {
				return 0;
			}
		};
		/Watch/ && do {
			if (defined $watch) {
				return $watch->getState();
			} else {
				return 0;
			}
		};
		/Clientmaint/ && do {
			if (defined $clientmaint) {
				return $clientmaint->getState();
			} else {
				return 0;
			}
		};
	}
}

#------------------------------------------------------------------------------#
# Sub: printUsage                                                              #
# Arguments: bool (or undefined)                                               #
# Returns: Usage Information                                                   #
#------------------------------------------------------------------------------#
sub printUsage {
	my $return = shift;
	my $data = <<"USAGE";
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

	if ($return) {
		return $data;
	} else {
		print $data;
	}
}

#------------------------------------------------------------------------------#
# Sub: printVersion                                                            #
# Arguments: Null                                                              #
# Returns: Version Information                                                 #
#------------------------------------------------------------------------------#
sub printVersion {
	print $PROG.".".$EXTENSION." Version ".$VERSION."\n";

	# FluxdCommon
	print "FluxdCommon Version : ";
	print FluxdCommon::getVersion()."\n";

	# FluxDB
	print "FluxDB Version : ";
	if (eval "require FluxDB") {
		print FluxDB->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

	# Clientmaint
	print "Clientmaint Version : ";
	if (eval "require Clientmaint") {
		print Clientmaint->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

	# Fluxinet
	print "Fluxinet Version : ";
	if (eval "require Fluxinet") {
		print Fluxinet->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

	# Qmgr
	print "Qmgr Version : ";
	if (eval "require Qmgr") {
		print Qmgr->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

	# Trigger
	print "Trigger Version : ";
	if (eval "require Trigger") {
		print Trigger->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

	# Watch
	print "Watch Version : ";
	if (eval "require Watch") {
		print Watch->getVersion()."\n";
	} else {
		print "cant load module\n";
	}

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
			print "Error : cant load module ".$mod."\n";
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
	if (-f $PATH_DOCROOT."inc/config/".$FILE_DBCONF) {
		print $PATH_DOCROOT."inc/config/".$FILE_DBCONF."\n";
	} else {
		print "Error : cant find database-config ".$FILE_DBCONF." in ".$PATH_DOCROOT."inc/config/"."\n";
	}
	print " - ".$BIN_FLUXCLI." : ";
	if (-f $PATH_DOCROOT."bin/".$BIN_FLUXCLI) {
		print $PATH_DOCROOT."bin/".$BIN_FLUXCLI."\n";
	} else {
		print "\nError : cant find fluxcli ".$BIN_FLUXCLI." in ".$PATH_DOCROOT."bin/"."\n";
	}

	# 3. db-bean
	print "3. database\n";

	# require
	if (!(eval "require FluxDB")) {
		print "Error : cant load database-module FluxDB\n";
		exit;
	}

	# create instance
	$fluxDB = FluxDB->new();
	if ($fluxDB->getState() == -1) {
		print "Error : creating FluxDB: ".$fluxDB->getMessage()."\n";
		exit;
	}

	# initialize
	$fluxDB->initialize($PATH_DOCROOT."inc/config/".$FILE_DBCONF);
	if ($fluxDB->getState() < 1) {
		print "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n";
		exit;
	}

	# db-settings
	print " - Type : ".$fluxDB->getDatabaseType()."\n";
	print " - Name : ".$fluxDB->getDatabaseName()."\n";
	print " - Host : ".$fluxDB->getDatabaseHost()."\n";

	# init paths
	initPaths(FluxDB->getFluxConfig("path"));

	# 4. paths
	print "4. paths\n";
	print " - flux-data-dir : ".FluxDB->getFluxConfig("path")."\n";
	print " - PATH_TRANSFER_DIR : ".$PATH_TRANSFER_DIR."\n";
	print " - PATH_DATA_DIR : ".$PATH_DATA_DIR."\n";
	print " - PATH_SOCKET : ".$PATH_SOCKET."\n";
	print " - ERROR_LOG : ".$ERROR_LOG."\n";
	print " - LOG : ".$LOG."\n";
	print " - PID_FILE : ".$PID_FILE."\n";
	print " - PATH_QUEUE_FILE : ".$PATH_QUEUE_FILE."\n";

	# 5. service-modules
	print "5. service-modules\n";

	# Qmgr
	print " - Qmgr : ";
	if (eval "require Qmgr") {
		eval {
			$qmgr = Qmgr->new();
			$qmgr->initialize(FluxDB->getFluxConfig("fluxd_Qmgr_interval"));
			if ($qmgr->getState() < 1) {
				print "error initializing service-module Qmgr :\n";
				print $qmgr->getMessage()."\n";
			}
			$qmgr->destroy();
			undef $qmgr;
		};
		if ($@) {
			print "\n $@\n";
		} else {
			# everything ok
		}
	} else {
		print "cant load service-module Qmgr\n";
	}

	# Fluxinet
	print " - Fluxinet : ";
	if (eval "require Fluxinet") {
		eval {
			$fluxinet = Fluxinet->new();
			$fluxinet->initialize(FluxDB->getFluxConfig("fluxd_Fluxinet_port"));
			if ($fluxinet->getState() < 1) {
				print "error initializing service-module Fluxinet :\n";
				print $fluxinet->getMessage()."\n";
			}
			$fluxinet->destroy();
			undef $fluxinet;
		};
		if ($@) {
			print "\n $@\n";
		} else {
			# everything ok
		}
	} else {
		print "cant load service-module Fluxinet\n";
	}

	# Watch
	print " - Watch : ";
	if (eval "require Watch") {
		eval {
			$watch = Watch->new();
			$watch->initialize(FluxDB->getFluxConfig("fluxd_Watch_interval"), FluxDB->getFluxConfig("fluxd_Watch_jobs"));
			if ($watch->getState() < 1) {
				print "error initializing service-module Watch :\n";
				print $watch->getMessage()."\n";
			}
			$watch->destroy();
			undef $watch;
		};
		if ($@) {
			print "\n $@\n";
		} else {
			# everything ok
		}
	} else {
		print "cant load service-module Watch\n";
	}

	# Clientmaint
	print " - Clientmaint : ";
	if (eval "require Clientmaint") {
		eval {
			$clientmaint = Clientmaint->new();
			$clientmaint->initialize(FluxDB->getFluxConfig("fluxd_Clientmaint_interval"));
			if ($clientmaint->getState() < 1) {
				print "error initializing service-module Clientmaint :\n";
				print $clientmaint->getMessage()."\n";
			}
			$clientmaint->destroy();
			undef $clientmaint;
		};
		if ($@) {
			print "\n $@\n";
		} else {
			# everything ok
		}
	} else {
		print "cant load service-module Clientmaint\n";
	}

	# Trigger
	print " - Trigger : ";
	if (eval "require Trigger") {
		eval {
			$trigger = Trigger->new();
			$trigger->initialize(FluxDB->getFluxConfig("fluxd_Trigger_interval"));
			if ($trigger->getState() < 1) {
				print "error initializing service-module Trigger :\n";
				print $trigger->getMessage()."\n";
			}
			$trigger->destroy();
			undef $trigger;
		};
		if ($@) {
			print "\n $@\n";
		} else {
			# everything ok
		}
	} else {
		print "cant load service-module Trigger\n";
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
		$dbcfg .= "inc/config/".$FILE_DBCONF;
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
			# db-settings
			print "\$fluxDB->getDatabaseType : \"".$fluxDB->getDatabaseType()."\"\n";
			print "\$fluxDB->getDatabaseName : \"".$fluxDB->getDatabaseName()."\"\n";
			print "\$fluxDB->getDatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n";
			print "\$fluxDB->getDatabasePort : \"".$fluxDB->getDatabasePort()."\"\n";
			print "\$fluxDB->getDatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n";
			print "\$fluxDB->getDatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n";
			print "\$fluxDB->getDatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n";
			exit;
		}
		# db-settings
		print "\$fluxDB->getDatabaseType : \"".$fluxDB->getDatabaseType()."\"\n";
		print "\$fluxDB->getDatabaseName : \"".$fluxDB->getDatabaseName()."\"\n";
		print "\$fluxDB->getDatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n";
		print "\$fluxDB->getDatabasePort : \"".$fluxDB->getDatabasePort()."\"\n";
		print "\$fluxDB->getDatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n";
		print "\$fluxDB->getDatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n";
		print "\$fluxDB->getDatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n";
		# something from the bean
		print "FluxDB->getFluxConfig(\"path\") : \"".FluxDB->getFluxConfig("path")."\"\n";
		print "FluxDB->getFluxConfig(\"bin_php\") : \"".FluxDB->getFluxConfig("bin_php")."\"\n";
		print "FluxDB->getFluxConfig(\"fluxd_Fluxinet_enabled\") : \"".FluxDB->getFluxConfig("fluxd_Fluxinet_enabled")."\"\n";
		print "FluxDB->getFluxConfig(\"fluxd_Fluxinet_port\") : \"".FluxDB->getFluxConfig("fluxd_Fluxinet_port")."\"\n";
		# test to set a val
		print "FluxDB->getFluxConfig(\"default_theme\") : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		$fluxDB->setFluxConfig("default_theme","foo");
		print "FluxDB->getFluxConfig(\"default_theme\") after set : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		# now reload and check again
		$fluxDB->reload();
		print "FluxDB->getFluxConfig(\"default_theme\") after reload : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		print "FluxDB->getFluxConfig(\"fluxd_Fluxinet_enabled\") : \"".FluxDB->getFluxConfig("fluxd_Fluxinet_enabled")."\"\n";
		print "FluxDB->getFluxConfig(\"fluxd_Fluxinet_port\") : \"".FluxDB->getFluxConfig("fluxd_Fluxinet_port")."\"\n";
		# destroy
		print "destroying \$fluxDB\n";
		$fluxDB->destroy();
		exit;
	} elsif	($debug =~ /fluxcli/) { # fluxcli-debug
		my $dbcfg = shift @ARGV;
		if (!(defined $dbcfg)) {
			print "debug fluxcli is missing an argument : path to docroot\n";
			exit;
		}
		if (!((substr $dbcfg, -1) eq "/")) {
			$dbcfg .= "/";
		}
		$dbcfg .= "inc/config/".$FILE_DBCONF;
		print "debugging fluxcli...\n";
		# require
		require FluxDB;
		# create instance
		$fluxDB = FluxDB->new();
		if ($fluxDB->getState() == -1) {
			print " error : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# initialize
		$fluxDB->initialize($dbcfg);
		if ($fluxDB->getState() < 1) {
			print " hmm : ".$fluxDB->getMessage()."\n";
			exit;
		}
		# init paths
		initPaths(FluxDB->getFluxConfig("path"));
		# test fluxcli-command "torrents"
		my $return = fluxcli("torrents");
		print $return;
		# destroy
		$fluxDB->destroy();
		exit;
	}

	# bail out
	print "debug is missing an operation.\n";
	exit;
}
