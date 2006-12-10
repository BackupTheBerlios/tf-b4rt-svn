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
#                                                                              #
#  Requirements :                                                              #
#   * IO::Socket::UNIX   ( perl -MCPAN -e "install IO::Socket::UNIX" )         #
#   * IO::Socket::INET   ( perl -MCPAN -e "install IO::Socket::INET" )         #
#   * IO::Select         ( perl -MCPAN -e "install IO::Select" )               #
#   * POSIX              ( perl -MCPAN -e "install POSIX" )                    #
#                                                                              #
################################################################################
package Fluxd;
use strict;
use warnings;
use FluxdCommon;
use AliasFile;
################################################################################

################################################################################
# fields                                                                       #
################################################################################
# files and dirs
my $PATH_DATA_DIR = ".fluxd";
my $PATH_TRANSFER_DIR = ".transfers";
my $BIN_FLUXCLI = "fluxcli.php";
my $PATH_SOCKET = "fluxd.sock";
my $ERROR_LOG = "fluxd-error.log";
my $LOG = "fluxd.log";
my $PID_FILE = "fluxd.pid";
# defaults
my $LOGLEVEL = 2;
my $PATH_DOCROOT = "/var/www";
my $BIN_PHP = "/usr/bin/php";
my $dbMode = "dbi";
my $pwd = ".";
# internal vars
my ($VERSION, $DIR, $PROG, $EXTENSION);
my $SERVER;
my $Select;
my $start_time = time();
my $start_time_local = localtime();

#------------------------------------------------------------------------------#
# Class reference variables                                                    #
#------------------------------------------------------------------------------#
use vars qw($fluxDB $qmgr $fluxinet $rssad $watch $clientmaint $trigger);

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
serviceModulesLoad();

# Here we go! The main loop!
my $loop = 1;
while ($loop) {

	# check Connections
	checkConnections();

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
			FluxdCommon::printError("CORE", "Fluxinet Timed out:\n ".$@."\n");
		}
	}

	# Qmgr
	if ((defined $qmgr) && ($qmgr->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub { die "alarm\n" };
			alarm 20;
			$qmgr->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			FluxdCommon::printError("CORE", "Qmgr Timed out:\n ".$@."\n");
		}
	}

	# Rssad
	if ((defined $rssad) && ($rssad->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 20;
			$rssad->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			FluxdCommon::printError("CORE", "Rssad Timed out:\n ".$@."\n");
		}
	}

	# Watch
	if ((defined $watch) && ($watch->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 20;
			$watch->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			FluxdCommon::printError("CORE", "Watch Timed out:\n ".$@."\n");
		}
	}

	# Clientmaint
	if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 5;
			$clientmaint->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			FluxdCommon::printError("CORE", "Clientmaint Timed out:\n ".$@."\n");
		}
	}

	# Trigger
	if ((defined $trigger) && ($trigger->getState() == 1)) {
		eval {
			local $SIG{ALRM} = sub {die "alarm\n"};
			alarm 5;
			$trigger->main();
			alarm 0;
		};

		# Check for alarm (timeout) condition
		if ($@) {
			FluxdCommon::printError("CORE", "Trigger Timed out:\n ".$@."\n");
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
	# daemon-stop
	if ($temp =~ /stop/) {
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
		# $BIN_PHP
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$BIN_PHP = $temp;
		# $dbMode
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$dbMode = $temp;
		# check if running
		if (daemonIsRunning($PATH_DOCROOT) == 0) {
			print STDERR "daemon not running.\n";
			exit;
		}
		print "Stopping daemon...\n";
		# db-bean
		# require
		require FluxDB;
		# create instance
		$fluxDB = FluxDB->new();
		# initialize
		$fluxDB->initialize($PATH_DOCROOT, $BIN_PHP, $dbMode);
		if ($fluxDB->getState() < 1) {
			print STDERR "Error : Problems initializing FluxDB : ".$fluxDB->getMessage()."\n";
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
			print STDERR "Error : cant find pid-file (".$PID_FILE."), daemon running ?\n";
		}
		# exit
		exit;
	};
	# start
	if ($temp =~ /start/) {
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
		# $BIN_PHP
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$BIN_PHP = $temp;
		# $dbMode
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$dbMode = $temp;
		# check if already running
		if (daemonIsRunning($PATH_DOCROOT) == 1) {
			print STDERR "daemon already running.\n";
			exit;
		}
		print "Starting daemon...\n";
		# return
		return 1;
	};
	# hmmm dont know this arg, show usage screen
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
	require FluxDB;

	# create instance
	$fluxDB = FluxDB->new();
	if ($fluxDB->getState() == -1) {
		FluxdCommon::printError("CORE", "Error : creating FluxDB: ".$fluxDB->getMessage()."\n");
		exit;
	}

	# initialize
	$fluxDB->initialize($PATH_DOCROOT, $BIN_PHP, $dbMode);
	if ($fluxDB->getState() < 1) {
		FluxdCommon::printError("CORE", "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n");
		exit;
	}

	# loglevel
	$LOGLEVEL = FluxDB->getFluxConfig("fluxd_loglevel");

	# init paths
	initPaths(FluxDB->getFluxConfig("path"));

	# chdir
	#chdir($PATH_DOCROOT) or die "Can't chdir to docroot: $!";

	# umask
	umask 0;

	# STD-IN/OUT/ERR
	open STDIN, "/dev/null" 	or die "Can't read /dev/null: $!";
	open STDOUT, ">>$LOG"		or die "Can't Write to $LOG: $!";
	open STDERR, ">>$ERROR_LOG"	or die "Can't Write to error $ERROR_LOG: $!";

	# check if already running
	if (daemonIsRunning($PATH_DOCROOT) == 1) {
		FluxdCommon::printError("CORE", "daemon already running.\n");
		exit;
	}

	# check for pid-file
	if (-f $PID_FILE) {
		FluxdCommon::printMessage("CORE", "pid-file (".$PATH_SOCKET.") exists but daemon not running. deleting...\n");
		pidFileDelete();
	}

	# check for socket
	if (-r $PATH_SOCKET) {
		FluxdCommon::printMessage("CORE", "socket (".$PATH_SOCKET.") exists but daemon not running. deleting...\n");
		socketRemove();
	}

	# load perl-modules
	loadModules();

	# fork
	defined(my $pid = fork) or die "CORE : Can't fork: $!";
	exit if $pid;
	POSIX::setsid() or die "CORE : Can't start a new session: $!";

	# get cwd
	$pwd = qx(pwd);
	chop $pwd;

	# log
	FluxdCommon::printMessage("CORE", "Starting up daemon with docroot ".$PATH_DOCROOT." (pid: ".$$." ; pwd: ".$pwd.")\n");

	# set up our signal handlers
	$SIG{HUP} = \&gotSigHup;
	$SIG{QUIT} = \&gotSigQuit;

	# set up server socket
	socketInitialize();

	# write out pid-file
	pidFileWrite($$);
}

#------------------------------------------------------------------------------#
# Sub: daemonShutdown                                                          #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub daemonShutdown {
	FluxdCommon::printMessage("CORE", "Shutting down!\n");

	# set main-loop-flag
	$loop = 0;

	# unload modules
	serviceModulesUnload();

	# remove socket
	socketRemove();

	# destroy db-bean
	if (defined($fluxDB)) {
		$fluxDB->destroy();
	}

	# remove pid-file
	pidFileDelete();

	# get out here
	exit;
}

#------------------------------------------------------------------------------#
# Sub: daemonIsRunning                                                         #
# Arguments: docroot                                                           #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub daemonIsRunning {
	my $docroot = shift;
	if (!(defined $docroot)) {
		return 0;
	}
	my $qstring = "ps -aux 2> /dev/null";
	my $pcount = 0;
	foreach my $line (grep(/$0.*$docroot/, qx($qstring))) {
		$pcount++;
	}
	if ($pcount > 1) {
		return 1;
	}
	return 0;
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
	$PATH_SOCKET = $PATH_DATA_DIR.$PATH_SOCKET;
	$LOG = $PATH_DATA_DIR.$LOG;
	$ERROR_LOG = $PATH_DATA_DIR.$ERROR_LOG;
	$PID_FILE = $PATH_DATA_DIR.$PID_FILE;
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $PATH_DATA_DIR) {
		mkdir($PATH_DATA_DIR, 0700);
	}
}

#------------------------------------------------------------------------------#
# Sub: loadModules                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadModules {
	# load IO::Socket::UNIX
	if ($LOGLEVEL > 2) {
		FluxdCommon::printMessage("CORE", "loading Perl-module IO::Socket::UNIX\n");
	}
	if (eval "require IO::Socket::UNIX")  {
		IO::Socket::UNIX->import();
	} else {
		FluxdCommon::printError("CORE", "load perl-module IO::Socket::UNIX failed\n");
		exit;
	}
	# load IO::Select
	if ($LOGLEVEL > 2) {
		FluxdCommon::printMessage("CORE", "loading Perl-module IO::Select\n");
	}
	if (eval "require IO::Select")  {
		IO::Select->import();
	} else {
		FluxdCommon::printError("CORE", "load perl-module IO::Select failed\n");
		exit;
	}
	# load POSIX
	if ($LOGLEVEL > 2) {
		FluxdCommon::printMessage("CORE", "loading Perl-module POSIX\n");
	}
	if (eval "require POSIX")  {
		POSIX->import(qw(setsid));
	} else {
		FluxdCommon::printError("CORE", "load perl-module POSIX failed\n");
		exit;
	}
}

#------------------------------------------------------------------------------#
# Sub: serviceModulesLoad                                                      #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub serviceModulesLoad {

	# Fluxinet
	if (FluxDB->getFluxConfig("fluxd_Fluxinet_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $fluxinet)) {
			if (eval "require Fluxinet") {
				eval {
					$fluxinet = Fluxinet->new();
					$fluxinet->initialize(
						$LOGLEVEL,
						FluxDB->getFluxConfig("fluxd_Fluxinet_port")
					);
					if ($fluxinet->getState() < 1) {
						my $msg = "error initializing service-module Fluxinet :\n";
						$msg .= " ".$fluxinet->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Fluxinet : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Fluxinet loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Fluxinet : $@\n");
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
				FluxdCommon::printError("CORE", "error unloading service-module Fluxinet : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Fluxinet unloaded\n");
				}
			}
		}
	}

	# Qmgr
	if (FluxDB->getFluxConfig("fluxd_Qmgr_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $qmgr)) {
			if (eval "require Qmgr") {
				eval {
					$qmgr = Qmgr->new();
					$qmgr->initialize(
						$LOGLEVEL,
						$PATH_DATA_DIR,
						$PATH_TRANSFER_DIR,
						FluxDB->getFluxConfig("fluxd_Qmgr_interval"),
						FluxDB->getFluxConfig("fluxd_Qmgr_maxTotalTorrents"),
						FluxDB->getFluxConfig("fluxd_Qmgr_maxUserTorrents")
					);
					if ($qmgr->getState() < 1) {
						my $msg = "error initializing service-module Qmgr :\n";
						$msg .= " ".$qmgr->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Qmgr : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Qmgr loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Qmgr : $@\n");
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
				FluxdCommon::printError("CORE", "error unloading service-module Qmgr : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Qmgr unloaded\n");
				}
			}
		}
	}

	# Rssad
	if (FluxDB->getFluxConfig("fluxd_Rssad_enabled") == 1) {
		# Load up module, unless it is already
		if (!(defined $rssad)) {
			if (eval "require Rssad") {
				eval {
					$rssad = Rssad->new();
					$rssad->initialize(
						$LOGLEVEL,
						$PATH_DATA_DIR,
						FluxDB->getFluxConfig("fluxd_Rssad_interval"),
						FluxDB->getFluxConfig("fluxd_Rssad_jobs")
					);
					if ($rssad->getState() < 1) {
						my $msg = "error initializing service-module Rssad :\n";
						$msg .= " ".$rssad->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Rssad : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Rssad loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Rssad : $@\n");
			}
		}
	} else {
		# Unload module, if it is loaded
		if (defined $rssad) {
			eval {
				$rssad->destroy();
				undef $rssad;
			};
			if ($@) {
				FluxdCommon::printError("CORE", "error unloading service-module Rssad : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Rssad unloaded\n");
				}
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
					$watch->initialize(
						$LOGLEVEL,
						FluxDB->getFluxConfig("fluxd_Watch_interval"),
						FluxDB->getFluxConfig("fluxd_Watch_jobs")
					);
					if ($watch->getState() < 1) {
						my $msg = "error initializing service-module Watch :\n";
						$msg .= " ".$watch->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Watch : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Watch loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Watch : $@\n");
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
				FluxdCommon::printError("CORE", "error unloading service-module Watch : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Watch unloaded\n");
				}
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
					$clientmaint->initialize(
						$LOGLEVEL,
						FluxDB->getFluxConfig("fluxd_Clientmaint_interval"),
						FluxDB->getFluxConfig("fluxd_Clientmaint_trestart")
					);
					if ($clientmaint->getState() < 1) {
						my $msg = "error initializing service-module Clientmaint :\n";
						$msg .= " ".$clientmaint->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Clientmaint : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Clientmaint loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Clientmaint : $@\n");
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
				FluxdCommon::printError("CORE", "error unloading service-module Clientmaint : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Clientmaint unloaded\n");
				}
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
					$trigger->initialize(
						$LOGLEVEL,
						FluxDB->getFluxConfig("fluxd_Trigger_interval")
					);
					if ($trigger->getState() < 1) {
						my $msg = "error initializing service-module Trigger :\n";
						$msg .= " ".$trigger->getMessage()."\n";
						FluxdCommon::printError("CORE", $msg);
					}
				};
				if ($@) {
					FluxdCommon::printError("CORE", "error loading service-module Trigger : $@\n");
				} else {
					# everything ok
					if ($LOGLEVEL > 0) {
						FluxdCommon::printMessage("CORE", "Trigger loaded\n");
					}
				}
			} else {
				FluxdCommon::printError("CORE", "error loading service-module Trigger : $@\n");
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
				FluxdCommon::printError("CORE", "error unloading service-module Trigger : $@\n");
			} else {
				# everything ok
				if ($LOGLEVEL > 0) {
					FluxdCommon::printMessage("CORE", "Trigger unloaded\n");
				}
			}
		}
	}

}

#------------------------------------------------------------------------------#
# Sub: serviceModulesUnload                                                    #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub serviceModulesUnload {

	# Fluxinet
	if (defined $fluxinet) {
		eval {
			$fluxinet->destroy();
			undef $fluxinet;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Fluxinet : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Fluxinet unloaded\n");
			}
		}
	}

	# Qmgr
	if (defined $qmgr) {
		eval {
			$qmgr->destroy();
			undef $qmgr;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Qmgr : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Qmgr unloaded\n");
			}
		}
	}

	# Rssad
	if (defined $rssad) {
		eval {
			$rssad->destroy();
			undef $rssad;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Rssad : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Rssad unloaded\n");
			}
		}
	}

	# Watch
	if (defined $watch) {
		eval {
			$watch->destroy();
			undef $watch;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Watch : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Watch unloaded\n");
			}
		}
	}

	# Clientmaint
	if (defined $clientmaint) {
		eval {
			$clientmaint->destroy();
			undef $clientmaint;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Clientmaint : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Clientmaint unloaded\n");
			}
		}
	}

	# Trigger
	if (defined $trigger) {
		eval {
			$trigger->destroy;
			undef $trigger;
		};
		if ($@) {
			FluxdCommon::printError("CORE", "error unloading service-module Trigger : $@\n");
		} else {
			# everything ok
			if ($LOGLEVEL > 0) {
				FluxdCommon::printMessage("CORE", "Trigger unloaded\n");
			}
		}
	}

}

#------------------------------------------------------------------------------#
# Sub: serviceModuleState                                                      #
# Arguments: name of service-module                                            #
# Returns: state of service-module                                             #
#------------------------------------------------------------------------------#
sub serviceModuleState {
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
		/Watch/ && do {
			if (defined $watch) {
				return $watch->getState();
			} else {
				return 0;
			}
		};
		/Rssad/ && do {
			if (defined $rssad) {
				return $rssad->getState();
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
		/Clientmaint/ && do {
			if (defined $clientmaint) {
				return $clientmaint->getState();
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
	}
}

#------------------------------------------------------------------------------#
# Sub: gotSigHup                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub gotSigHup {
	FluxdCommon::printMessage("CORE", "Got SIGHUP, re-loading service-modules...\n");
	# have FluxDB reload the DB first, so we can see the changes
	if ($fluxDB->reload()) {
		serviceModulesLoad();
	} else {
		my $msg = "Error connecting to DB to read changes :\n";
		$msg .= $fluxDB->getMessage()."\n";
		FluxdCommon::printError("CORE", $msg);
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
		# Actual fluxd subroutine calls
		/^modstate/ && do {
			$return = serviceModuleState(shift);
			last SWITCH;
		};
		/^status/ && do {
			$return = status();
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
			$return = serviceModulesLoad();
			last SWITCH;
		};
		/^check/ && do {
			$return = check();
			last SWITCH;
		};
		/^die/ && do {
			$return = daemonShutdown();
			last SWITCH;
		};
		# module-calls
		/^!(.+):(.+)/ && do {
			my $mod = $1;
			my $command = $2;
			$return = "";
			$_ = $mod;
			MODCALL: {
				/Fluxinet/ && do {
					if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
						$return = $fluxinet->command($command);
					}
					last SWITCH;
				};
				/Qmgr/ && do {
					if ((defined $qmgr) && ($qmgr->getState() == 1)) {
						$return = $qmgr->command($command);
					}
					last SWITCH;
				};
				/Rssad/ && do {
					if ((defined $rssad) && ($rssad->getState() == 1)) {
						$return = $rssad->command($command);
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
				/Trigger/ && do {
					if ((defined $trigger) && ($trigger->getState() == 1)) {
						$return = $trigger->command($command);
					}
					last SWITCH;
				};
			}
		};
		# Default case.
		$return = printUsage(1);
	}
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
		next if ($pair[0] !~/Fluxinet|Qmgr|Rssad|Watch|Trigger|Clientmaint/);
		SWITCH: {
			$_ = $pair[0];
			/Fluxinet/ && do {
				$return = $fluxinet->set($pair[1], $value) if(defined $fluxinet);
				last SWITCH;
			};
			/Qmgr/ && do {
				$return = $qmgr->set($pair[1], $value) if (defined $qmgr);
				last SWITCH;
			};
			/Rssad/ && do {
				$return = $rssad->set($pair[1], $value) if(defined $rssad);
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
			/Trigger/ && do {
				$return = $trigger->set($pair[1], $value) if(defined $trigger);
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
# Arguments: Command [Arg1, Arg2, Arg3, Arg4]                                  #
# Returns: string or 0|1                                                       #
#------------------------------------------------------------------------------#
sub fluxcli {
	my $Command = shift;
	my $Arg1 = shift;
	my $Arg2 = shift;
	my $Arg3 = shift;
	my $Arg4 = shift;
	# qx
	if ($Command =~/^torrents|^netstat/) {
		if ((defined $Arg1) || (defined $Arg2)) {
			return printUsage();
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command." 2>> ".$ERROR_LOG;
			return qx($shellCmd);
		}
	}
	if ($Command =~/^dump/) {
		if ((!(defined $Arg1)) || (defined $Arg2)) {;
			return printUsage();
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1." 2>> ".$ERROR_LOG;
			return qx($shellCmd);
		}
	}
	# syscall
	if ($Command =~/^\w+-all|^repair/) {
		if ((defined $Arg1) || (defined $Arg2)) {
			return 0;
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command;
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^start|^stop|^reset|^delete|^wipe|^xfer/) {
		if ((!(defined $Arg1)) || (defined $Arg2)) {;
			return 0;
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1;
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^inject|^watch|^maintenance/) {
		if ((!(defined $Arg1)) || (!(defined $Arg2))) {
			return 0;
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1." ".$Arg2;
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^rss/) {
		if ((!(defined $Arg1)) || (!(defined $Arg2)) || (!(defined $Arg3)) || (!(defined $Arg4))) {
			return 0;
		} else {
			my $shellCmd = $BIN_PHP." bin/".$BIN_FLUXCLI." ".$Command." ".$Arg1." ".$Arg2." ".$Arg3." ".$Arg4;
			return doSysCall($shellCmd);
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: doSysCall                                                               #
# Arguments: Command-string                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub doSysCall {
	my $command = shift;
	$command .= " 1>> ".$LOG." 2>> ".$ERROR_LOG." &";
    system($command);
    if ($? == -1) {
		FluxdCommon::printError("CORE", "failed to execute: $!\n");
    } elsif ($? & 127) {
		FluxdCommon::printError("CORE", (sprintf "child died with signal %d, %s coredump\n", ($? & 127),  ($? & 128) ? 'with' : 'without'));
    } else {
		if ($LOGLEVEL > 2) {
			FluxdCommon::printMessage("CORE", (sprintf "child exited with value %d\n", $? >> 8));
		}
		return 1;
    }
	return 0;
}

#------------------------------------------------------------------------------#
# Sub: socketInitialize                                                        #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub socketInitialize {
	$SERVER = IO::Socket::UNIX->new(
			Type    => IO::Socket::UNIX->SOCK_STREAM,
			Local   => $PATH_SOCKET,
			Listen  => 16,
			Reuse   => 1,
			);
	die "CORE : Couldn't create socket: $!\n" unless $SERVER;
	if ($LOGLEVEL > 0) {
		FluxdCommon::printMessage("CORE", "created socket ".$PATH_SOCKET."\n");
	}

	# create select
	$Select = new IO::Select();

	# Add our server socket to the select read set.
	$Select->add($SERVER);
}

#------------------------------------------------------------------------------#
# Sub: socketRemove                                                            #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub socketRemove {
	if ($LOGLEVEL > 0) {
		FluxdCommon::printMessage("CORE", "removing socket ".$PATH_SOCKET."\n");
	}
	unlink($PATH_SOCKET);
}

#------------------------------------------------------------------------------#
# Sub: pidFileWrite                                                            #
# Arguments: int with pid                                                      #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub pidFileWrite {
	my $pid = shift;
	if (!(defined $pid)) {
		$pid = $$;
	}
	if ($LOGLEVEL > 0) {
		FluxdCommon::printMessage("CORE", "writing pid-file ".$PID_FILE." (pid: ".$pid.")\n");
	}
	open(PIDFILE,">$PID_FILE");
	print PIDFILE $pid."\n";
	close(PIDFILE);
}

#------------------------------------------------------------------------------#
# Sub: pidFileDelete                                                           #
# Arguments: null                                                              #
# Returns: return-val of delete                                                #
#------------------------------------------------------------------------------#
sub pidFileDelete {
	if ($LOGLEVEL > 0) {
		FluxdCommon::printMessage("CORE", "deleting pid-file ".$PID_FILE."\n");
	}
	return unlink($PID_FILE);
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Server information page                                             #
#------------------------------------------------------------------------------#
sub status {
	my $head = "";
	$head .= "\n\nfluxd has been up since ".$start_time_local." (".FluxdCommon::niceTimeString($start_time).")\n\n";
	$head .= "data-dir : ".$PATH_DATA_DIR."\n";
	$head .= "log : ".$LOG."\n";
	$head .= "error-log : ".$ERROR_LOG."\n";
	$head .= "pid : ".$PID_FILE."\n";
	$head .= "socket : ".$PATH_SOCKET."\n";
	$head .= "transfers-dir : ".$PATH_TRANSFER_DIR."\n";
	$head .= "docroot : ".$PATH_DOCROOT."\n";
	$head .= "fluxcli : ".$pwd."/bin/".$BIN_FLUXCLI."\n";
	$head .= "php : ".$BIN_PHP."\n";
	$head .= "db-mode : ".$dbMode."\n";
	$head .= "loglevel : ".$LOGLEVEL."\n";
	$head .= "\n";
	my $status = "";
	my $modules = "- Loaded Modules -\n";
	# Fluxinet
	if ((defined $fluxinet) && ($fluxinet->getState() == 1)) {
		$modules .= "  * Fluxinet\n";
		$status .= eval { $fluxinet->status(); };
	}
	# Qmgr
	if ((defined $qmgr) && ($qmgr->getState() == 1)) {
		$modules .= "  * Qmgr\n";
		$status .= $qmgr->status();
	}
	# Rssad
	if ((defined $rssad) && ($rssad->getState() == 1)) {
		$modules .= "  * Rssad\n";
		$status .= eval { $rssad->status(); };
	}
	# Watch
	if ((defined $watch) && ($watch->getState() == 1)) {
		$modules .= "  * Watch\n";
		$status .= eval { $watch->status(); };
	}
	# Clientmaint
	if ((defined $clientmaint) && ($clientmaint->getState() == 1)) {
		$modules .= "  * Clientmaint\n";
		$status .= eval { $clientmaint->status(); };
	}
	# Trigger
	if ((defined $trigger) && ($trigger->getState() == 1)) {
		$modules .= "  * Trigger\n";
		$status .= eval { $trigger->status(); };
	}
	# return
	return $head.$modules.$status;
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
	# AliasFile
	print "AliasFile Version : ";
	print AliasFile::getVersion()."\n";
	# FluxDB
	print "FluxDB Version : ";
	if (eval "require FluxDB") {
		print FluxDB->getVersion()."\n";
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
	# Rssad
	print "Rssad Version : ";
	if (eval "require Rssad") {
		print Rssad->getVersion()."\n";
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
	# Clientmaint
	print "Clientmaint Version : ";
	if (eval "require Clientmaint") {
		print Clientmaint->getVersion()."\n";
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
}

#------------------------------------------------------------------------------#
# Sub: check                                                                   #
# Arguments: Null                                                              #
# Returns: info on sys requirements                                            #
#------------------------------------------------------------------------------#
sub check {
	print "checking requirements...\n";
	# 1. perl-modules
	print "1. perl-modules\n";
	my @mods = ('IO::Socket::UNIX', 'IO::Socket::INET', 'IO::Select', 'POSIX');
	foreach my $mod (@mods) {
		if (eval "require $mod")  {
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
	# done
	print "done.\n";
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

		# $PATH_DOCROOT
		my $temp = shift @ARGV;
		if (!(defined $temp)) {
			print "debug database is missing an argument : path to docroot\n";
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$PATH_DOCROOT = $temp;

		# $BIN_PHP
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			print "debug database is missing an argument : path to php\n";
			exit;
		}
		$BIN_PHP = $temp;

		print "debugging database...\n";

		# require
		require FluxDB;
		# create instance
		print " creating \$fluxDB\n";
		$fluxDB = FluxDB->new();
		if ($fluxDB->getState() == -1) {
			print " error : ".$fluxDB->getMessage()."\n";
			exit;
		}

		# PHP
		# initialize
		print " initializing \$fluxDB (php)\n";
		$fluxDB->initialize($PATH_DOCROOT, $BIN_PHP, "php");
		if ($fluxDB->getState() < 1) {
			print " hmm : ".$fluxDB->getMessage()."\n";
			exit;
		}

		# something from the bean
		print "  FluxConfig(\"path\") : \"".FluxDB->getFluxConfig("path")."\"\n";
		print "  FluxConfig(\"docroot\") : \"".FluxDB->getFluxConfig("docroot")."\"\n";
		# test to set a val
		print "  FluxConfig(\"default_theme\") : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		$fluxDB->setFluxConfig("default_theme","foo");
		print "  FluxConfig(\"default_theme\") after set : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		# now reload and check again
		$fluxDB->reload();
		print "  FluxConfig(\"default_theme\") after reload : \"".FluxDB->getFluxConfig("default_theme")."\"\n";

		# destroy
		print " destroying \$fluxDB\n";
		$fluxDB->destroy();

		# DBI
		# initialize
		print " initializing \$fluxDB (dbi)\n";
		$fluxDB->initialize($PATH_DOCROOT, $BIN_PHP, "dbi");
		if ($fluxDB->getState() < 1) {
			print " hmm : ".$fluxDB->getMessage()."\n";
			# db-settings
			print " DatabaseType : \"".$fluxDB->getDatabaseType()."\"\n";
			print " DatabaseName : \"".$fluxDB->getDatabaseName()."\"\n";
			print " DatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n";
			print " DatabasePort : \"".$fluxDB->getDatabasePort()."\"\n";
			print " DatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n";
			print " DatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n";
			print " DatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n";
			exit;
		}
		# db-settings
		print "  DatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n";

		# something from the bean
		print "  FluxConfig(\"path\") : \"".FluxDB->getFluxConfig("path")."\"\n";
		print "  FluxConfig(\"docroot\") : \"".FluxDB->getFluxConfig("docroot")."\"\n";
		# test to set a val
		print "  FluxConfig(\"default_theme\") : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		$fluxDB->setFluxConfig("default_theme","foo");
		print "  FluxConfig(\"default_theme\") after set : \"".FluxDB->getFluxConfig("default_theme")."\"\n";
		# now reload and check again
		$fluxDB->reload();
		print "  FluxConfig(\"default_theme\") after reload : \"".FluxDB->getFluxConfig("default_theme")."\"\n";

		# destroy
		print " destroying \$fluxDB\n";
		$fluxDB->destroy();

		# done
		print "done.\n";
		exit;

	} elsif	($debug =~ /fluxcli/) { # fluxcli-debug
		# $PATH_DOCROOT
		my $temp = shift @ARGV;
		if (!(defined $temp)) {
			print "debug fluxcli is missing an argument : path to docroot\n";
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$PATH_DOCROOT = $temp;
		# $BIN_PHP
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			print "debug fluxcli is missing an argument : path to php\n";
			exit;
		}
		$BIN_PHP = $temp;
		print "debugging fluxcli...\n";
		# test fluxcli-command "torrents"
		my $return = fluxcli("torrents");
		print $return;
		# exit
		exit;
	}

	# bail out
	print "debug is missing an operation.\n";
	exit;
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

Usage: $PROG.$EXTENSION <start> path-to-docroot path-to-php db-mode
                        start fluxd daemon.
                        db-mode : dbi/php
       $PROG.$EXTENSION <stop> path-to-docroot path-to-php db-mode
                        stop fluxd daemon
                        db-mode : dbi/php
       $PROG.$EXTENSION <check>
                        check for requirements.
       $PROG.$EXTENSION <-v|--version>
                        print out version-info
       $PROG.$EXTENSION <-h|--help>
                        print out help screen.

USAGE

	if ($return) {
		return $data;
	} else {
		print $data;
	}

}
