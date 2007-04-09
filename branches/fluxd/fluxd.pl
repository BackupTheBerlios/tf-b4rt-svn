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
#   * IO::Select         ( perl -MCPAN -e "install IO::Select" )               #
#   * IO::Socket::UNIX   ( perl -MCPAN -e "install IO::Socket::UNIX" )         #
#   * IO::Socket::INET   ( perl -MCPAN -e "install IO::Socket::INET" )         #
#   * POSIX              ( perl -MCPAN -e "install POSIX" )                    #
#                                                                              #
################################################################################
package Fluxd;
use strict;
use warnings;
use FluxCommon;
use StatFile;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# constants
use constant MOD_STATE_ERROR => -1;
use constant MOD_STATE_NULL => 0;
use constant MOD_STATE_OK => 1;

# files and dirs
my $path_data_dir = ".fluxd";
my $path_transfer_dir = ".transfers";
my $bin_fluxcli = "fluxcli.php";
my $path_socket = "fluxd.sock";
my $log_error = "fluxd-error.log";
my $log = "fluxd.log";
my $file_pid = "fluxd.pid";

# defaults
my $loglevel = 0;
my $path_docroot = "/var/www/";
my $path_path = "/usr/local/torrentflux/";
my $bin_php = "/usr/bin/php";
my $dbMode = "dbi";
my $pwd = ".";

# delims of modList
my $delimMod = ";";
my $delimState = ":";

# internal vars
my ($VERSION, $DIR, $PROG, $EXTENSION);
my $server;
my $select;
my $start_time = time();
my $start_time_local = localtime();
my $loop = 1;

# db-bean
my $fluxDB;

# service-modules
my %serviceModules = (
	1 => {
		name    => "Fluxinet",
		timeout => 3
	},
	2 => {
		name    => "Qmgr",
		timeout => 20
	},
	3 => {
		name    => "Rssad",
		timeout => 20
	},
	4 => {
		name    => "Watch",
		timeout => 20
	},
	5 => {
		name    => "Maintenance",
		timeout => 20
	},
	6 => {
		name    => "Trigger",
		timeout => 20
	}
);
my %serviceModuleObjects;

################################################################################
# main                                                                         #
################################################################################

# flush the buffer
$| = 1;

# initialize
initialize();

# process arguments
processArguments();

# daemon-startup
daemonStartup();

# daemon-main
daemonMain();

# daemon-shutdown
daemonShutdown();

################################################################################
# subs                                                                         #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize                                                              #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub initialize {
	# initialize some variables
	$VERSION = do {
		my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
	($DIR=$0) =~ s/([^\/\\]*)$//;
	($PROG=$1) =~ s/\.([^\.]*)$//;
	$EXTENSION = $1;
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
		# path_docroot
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_docroot = $temp;
		# path_path
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_path = $temp;
		# bin_php
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$bin_php = $temp;
		# dbMode
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$dbMode = $temp;
		# init paths
		initPaths();
		# check if running
		if (daemonIsRunning($path_docroot) == 0) {
			printError("CORE", "daemon not running.\n");
			exit;
		}
		printMessage("CORE", "Stopping daemon...\n");
		# shutdown
		if (-f $file_pid) {
			# get pid
			open(PIDFILE,"< $file_pid");
			my $daemonPid = <PIDFILE>;
			close(PIDFILE);
			chomp $daemonPid;
			# send TERM to daemon
			kill 'SIGTERM', $daemonPid;
		} else {
			printError("CORE", "Error : cant find pid-file (".$file_pid."), daemon running ?\n");
		}
		# exit
		exit;
	};
	# start
	if ($temp =~ /start/) {
		# path_docroot
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_docroot = $temp;
		# path_path
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_path = $temp;
		# bin_php
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$bin_php = $temp;
		# dbMode
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printUsage();
			exit;
		}
		$dbMode = $temp;
		# init paths
		initPaths();
		# return
		return 1;
	};
	# hmmm dont know this arg, show usage screen
	printUsage();
	exit;
}

#------------------------------------------------------------------------------#
# Sub: daemonStartup                                                           #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub daemonStartup {

	# print
	printMessage("CORE", "fluxd starting...\n");

	# daemonise the script
	&daemonize();

	# load flux-service-modules
	serviceModulesLoad();

	# print that we started ok
	printMessage("CORE", "fluxd-startup complete. fluxd is up and running.\n");

}

#------------------------------------------------------------------------------#
# Sub: daemonize                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub daemonize {

	# umask
	umask 0;

	# STD-IN/OUT/ERR

	# STDIN
	unless (open STDIN, "/dev/null") {
		logError("CORE", "failed to open STDIN: ".$!."\n");
		exit;
	}
	# STDOUT
	unless (open STDOUT, ">>$log") {
		logError("CORE", "failed to open STDOUT: ".$!."\n");
		exit;
	}
	# STDERR
	unless (open STDERR, ">>$log_error") {
		logError("CORE", "failed to open STDERR: ".$!."\n");
		exit;
	}

	# check if already running
	if (daemonIsRunning($path_docroot) == 1) {
		printError("CORE", "daemon already running.\n");
		exit;
	}

	# check for pid-file
	if (-f $file_pid) {
		printMessage("CORE", "pid-file (".$file_pid.") exists but daemon not running. deleting...\n");
		pidFileDelete();
	}

	# check for socket
	if (-r $path_socket) {
		printMessage("CORE", "socket (".$path_socket.") exists but daemon not running. deleting...\n");
		socketRemove();
	}

	# load perl-modules
	loadModules();

	# print
	printMessage("CORE", "initialize FluxDB...\n");

	# db-bean
	require FluxDB;

	# create instance
	$fluxDB = FluxDB->new();

	# initialize
	$fluxDB->initialize($path_docroot, $bin_php, $dbMode);
	if ($fluxDB->getState() != MOD_STATE_OK) {
		printError("CORE", "Error : initializing FluxDB : ".$fluxDB->getMessage()."\n");
		exit;
	}

	# loglevel
	$loglevel = FluxDB->getFluxConfig("fluxd_loglevel");

	# chdir
	#chdir($path_docroot) or die "Can't chdir to docroot: $!";

	# fork
	if ($loglevel > 1) {
		printMessage("CORE", "forking and starting a new session...\n");
	}
	my $pid = fork;
	unless (defined($pid)) {
		printError("CORE", "could not fork: ".$!."\n");
		exit;
	}
	exit if $pid;
	unless (POSIX::setsid()) {
		printError("CORE", "could not start a new session: ".$!."\n");
		exit;
	}

	# get cwd
	$pwd = qx(pwd);
	chop $pwd;

	# log
	printMessage("CORE", "daemon starting with docroot ".$path_docroot." (pid: ".$$." ; pwd: ".$pwd.")\n");

	# set up our signal handlers
	if ($loglevel > 1) {
		printMessage("CORE", "setting up signal handlers...\n");
	}
	$SIG{HUP} = \&gotSigHup;
	$SIG{INT} = \&gotSigInt;
	$SIG{TERM} = \&gotSigTerm;
	$SIG{QUIT} = \&gotSigQuit;

	# set up server socket
	socketInitialize();

	# write out pid-file
	pidFileWrite($$);
}

#------------------------------------------------------------------------------#
# Sub: daemonMain                                                              #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub daemonMain {

	# loop
	while ($loop) {

		# check Connections
		checkConnections();

		# service-modules main-methods
		foreach my $smod (sort keys %serviceModules) {
			if ((exists $serviceModuleObjects{$serviceModules{$smod}{"name"}}) &&
				($serviceModuleObjects{$serviceModules{$smod}{"name"}}->getState() == MOD_STATE_OK)) {
				eval {
					local $SIG{ALRM} = sub {die "alarm\n"};
					alarm $serviceModules{$smod}{"timeout"};
					$serviceModuleObjects{$serviceModules{$smod}{"name"}}->main();
					alarm 0;
				};
				# Check for alarm (timeout) condition
				if ($@) {
					printError("CORE", $serviceModules{$smod}{"name"}." Timed out:\n ".$@."\n");
				}
			}
		}

		# sleep
		select undef, undef, undef, 0.1;

	} # loop end
}

#------------------------------------------------------------------------------#
# Sub: daemonShutdown                                                          #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub daemonShutdown {
	printMessage("CORE", "Shutting down!\n");

	# unload modules
	serviceModulesUnload();

	# remove socket
	socketRemove();

	# destroy db-bean
	printMessage("CORE", "shutting down FluxDB...\n");
	if (defined($fluxDB)) {
		$fluxDB->destroy();
	}

	# remove pid-file
	pidFileDelete();

	# print that we started ok
	printMessage("CORE", "fluxd-shutdown complete.\n");

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
	my $qstring = "ps x -o pid='' -o ppid='' -o command='' -ww 2> /dev/null";
	my $pcount = 0;
	foreach my $line (grep(/fluxd running.*$docroot/, qx($qstring))) {
		print STDOUT $line."\n";
		$pcount++;
	}
	if ($pcount > 0) {
		return 1;
	}
	return 0;
}

#------------------------------------------------------------------------------#
# Sub: initPaths                                                               #
# Arguments: null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub initPaths {
	$path_transfer_dir = $path_path.$path_transfer_dir."/";
	$path_data_dir = $path_path.$path_data_dir."/";
	$path_socket = $path_data_dir.$path_socket;
	$log = $path_data_dir.$log;
	$log_error = $path_data_dir.$log_error;
	$file_pid = $path_data_dir.$file_pid;
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $path_data_dir) {
		mkdir($path_data_dir, 0700);
	}
}

#------------------------------------------------------------------------------#
# Sub: loadModules                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadModules {
	# print
	if ($loglevel > 1) {
		printMessage("CORE", "loading Perl-modules...\n");
	}
	# load IO::Socket::UNIX
	if ($loglevel > 2) {
		printMessage("CORE", "loading Perl-module IO::Socket::UNIX\n");
	}
	if (eval "require IO::Socket::UNIX")  {
		IO::Socket::UNIX->import();
	} else {
		printError("CORE", "load perl-module IO::Socket::UNIX failed\n");
		exit;
	}
	# load IO::Select
	if ($loglevel > 2) {
		printMessage("CORE", "loading Perl-module IO::Select\n");
	}
	if (eval "require IO::Select")  {
		IO::Select->import();
	} else {
		printError("CORE", "load perl-module IO::Select failed\n");
		exit;
	}
	# load POSIX
	if ($loglevel > 2) {
		printMessage("CORE", "loading Perl-module POSIX\n");
	}
	if (eval "require POSIX")  {
		POSIX->import(qw(setsid));
	} else {
		printError("CORE", "load perl-module POSIX failed\n");
		exit;
	}
	# print
	if ($loglevel > 1) {
		printMessage("CORE", "Perl-modules loaded.\n");
	}
}

#------------------------------------------------------------------------------#
# Sub: serviceModuleLoad                                                       #
# Arguments: name of module                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub serviceModuleLoad {
	my $modName = shift;
	if (exists $serviceModuleObjects{$modName}) {
		return 1;
	}
	if ($loglevel > 1) {
		printMessage("CORE", "loading service-module ".$modName." ...\n");
	}
	# create and initialize
	if (eval "require ".$modName) {
		eval {
			$serviceModuleObjects{$modName} = eval $modName."->new();";
			$serviceModuleObjects{$modName}->initialize();
			if ($serviceModuleObjects{$modName}->getState() != MOD_STATE_OK) {
				my $msg = "error initializing service-module ".$modName." :\n";
				$msg .= " ".$serviceModuleObjects{$modName}->getMessage()."\n";
				printError("CORE", $msg);
			}
		};
		if ($@) {
			printError("CORE", "error loading service-module ".$modName." : ".$@."\n");
		} else {
			# everything ok
			if ($loglevel > 0) {
				printMessage("CORE", $modName." loaded\n");
			}
			return 1;
		}
	} else {
		printError("CORE", "error loading service-module ".$modName." : ".$@."\n");
	}
}

#------------------------------------------------------------------------------#
# Sub: serviceModuleUnload                                                     #
# Arguments: name of module                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub serviceModuleUnload {
	my $modName = shift;
	if (exists $serviceModuleObjects{$modName}) {
		if ($loglevel > 1) {
			printMessage("CORE", "unloading service-module ".$modName." ...\n");
		}
		eval {
			$serviceModuleObjects{$modName}->destroy();
			delete($serviceModuleObjects{$modName});
		};
		if ($@) {
			printError("CORE", "error unloading service-module ".$modName." : ".$@."\n");
			return 0;
		} else {
			# everything ok
			if ($loglevel > 0) {
				printMessage("CORE", $modName." unloaded\n");
			}
		}
	}
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: serviceModulesLoad                                                      #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub serviceModulesLoad {

	# print
	if ($loglevel > 0) {
		printMessage("CORE", "loading service-modules...\n");
	}

	# load/unload
	foreach my $smod (sort keys %serviceModules) {
		if (FluxDB->getFluxConfig("fluxd_".$serviceModules{$smod}{"name"}."_enabled") == 1) {
			# Load up module, unless it is already
			serviceModuleLoad($serviceModules{$smod}{"name"});
		} else {
			# Unload module, if it is loaded
			serviceModuleUnload($serviceModules{$smod}{"name"});
		}
	}

	# set command line
	my @cmdmodlist = ();
	foreach my $smod (sort keys %serviceModules) {
		if ((exists $serviceModuleObjects{$serviceModules{$smod}{"name"}}) &&
			($serviceModuleObjects{$serviceModules{$smod}{"name"}}->getState() == MOD_STATE_OK)) {
			push(@cmdmodlist, $serviceModules{$smod}{"name"});
		}
	}
	my $cmdmodliststr = "No Modules Loaded";
	my $modCount = scalar(@cmdmodlist);
	if ($modCount > 0) {
		$cmdmodliststr = "";
		for (my $i = 0; $i < $modCount; $i++) {
			if ($i > 0) {
				$cmdmodliststr .= " ";
			}
			$cmdmodliststr .= $cmdmodlist[$i];
		}
	}
	$0 = '[ fluxd running ('.$path_docroot.') ('.$cmdmodliststr.') ]';

	# print
	if ($loglevel > 0) {
		printMessage("CORE", "done loading service-modules.\n");
	}

}

#------------------------------------------------------------------------------#
# Sub: serviceModulesUnload                                                    #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub serviceModulesUnload {

	# print
	if ($loglevel > 0) {
		printMessage("CORE", "unloading service-modules...\n");
	}

	# unload
	foreach my $smod (sort keys %serviceModules) {
		serviceModuleUnload($serviceModules{$smod}{"name"});
	}

	# print
	if ($loglevel > 0) {
		printMessage("CORE", "done unloading service-modules.\n");
	}

}

#------------------------------------------------------------------------------#
# Sub: serviceModuleList                                                       #
# Arguments: null                                                              #
# Returns: string with list of mods+state                                      #
#------------------------------------------------------------------------------#
sub serviceModuleList {

	# retval
	my $modList = "";

	# build list
	foreach my $smod (sort keys %serviceModules) {
		$modList .= $serviceModules{$smod}{"name"}.$delimState;
		if (exists $serviceModuleObjects{$serviceModules{$smod}{"name"}}) {
			$modList .= $serviceModuleObjects{$serviceModules{$smod}{"name"}}->getState();
		} else {
			$modList .= MOD_STATE_NULL;
		}
		$modList .= $delimMod;
	}

	# return
	return (substr ($modList, 0, (length($modList)) - 1));
}

#------------------------------------------------------------------------------#
# Sub: serviceModuleState                                                      #
# Arguments: name of service-module                                            #
# Returns: state of service-module                                             #
#------------------------------------------------------------------------------#
sub serviceModuleState {
	my $modName = shift;
	if (exists $serviceModuleObjects{$modName}) {
		return $serviceModuleObjects{$modName}->getState();
	} else {
		return MOD_STATE_NULL;
	}
}

#------------------------------------------------------------------------------#
# Sub: gotSigHup                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub gotSigHup {
	printMessage("CORE", "got SIGHUP, reloading config and service-modules...\n");
	# have FluxDB reload the DB first, so we can see the changes
	if ($fluxDB->reload()) {
		serviceModulesLoad();
	} else {
		my $msg = "Error connecting to DB to read changes :\n";
		$msg .= $fluxDB->getMessage()."\n";
		printError("CORE", $msg);
	}
}

#------------------------------------------------------------------------------#
# Sub: gotSigInt                                                               #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub gotSigInt {
	printMessage("CORE", "got SIGINT, setting shutdown-flag...\n");
	# set main-loop-flag
	$loop = 0;
}

#------------------------------------------------------------------------------#
# Sub: gotSigTerm                                                              #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub gotSigTerm {
	printMessage("CORE", "got SIGTERM, setting shutdown-flag...\n");
	# set main-loop-flag
	$loop = 0;
}

#------------------------------------------------------------------------------#
# Sub: gotSigQuit                                                              #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub gotSigQuit {
	printMessage("CORE", "got SIGQUIT, setting shutdown-flag...\n");
	# set main-loop-flag
	$loop = 0;
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
	my @ready = $select->can_read(0);
	foreach my $socket (@ready) {
		if ($socket == $server) {
			my $new = $socket->accept();
			$select->add($new);
		} else {
			my $buf = "";
			my $char = getc($socket);
			while ((defined($char)) && ($char ne "\n")) {
				$buf .= $char;
				$char = getc($socket);
			}
			$return = processRequest($buf);
			$socket->send($return);
			$select->remove($socket);
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
		/^modlist/ && do {
			$return = serviceModuleList();
			last SWITCH;
		};
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
			# set main-loop-flag
			$loop = 0;
			$return = 1;
			last SWITCH;
		};
		# module-calls
		/^!(.+):(.+)/ && do {
			my $mod = $1;
			my $command = $2;
			$return = "";
			if ($mod !~/Fluxinet|Qmgr|Rssad|Watch|Maintenance|Trigger/) {
				$return = "Unknown Module";
			} else {
				if ((exists $serviceModuleObjects{$mod}) &&
					($serviceModuleObjects{$mod}->getState() == MOD_STATE_OK)) {
					$return = $serviceModuleObjects{$mod}->command($command);
				}
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
		if ($pair[0] !~/Fluxinet|Qmgr|Rssad|Watch|Maintenance|Trigger/) {
			$return = "Unknown Module";
		} else {
			if ((exists $serviceModuleObjects{$pair[0]}) &&
				($serviceModuleObjects{$pair[0]}->getState() == MOD_STATE_OK)) {
				$return = $serviceModuleObjects{$pair[0]}-->set($pair[1], $value);
			}
		}
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
	if ($Command =~/^transfers|^netstat/) {
		if ((defined $Arg1) || (defined $Arg2)) {
			return printUsage();
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command)." 2>> ".$log_error;
			return qx($shellCmd);
		}
	}
	if ($Command =~/^dump/) {
		if ((!(defined $Arg1)) || (defined $Arg2)) {;
			return printUsage();
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command)." ".quotemeta($Arg1)." 2>> ".$log_error;
			return qx($shellCmd);
		}
	}
	# syscall
	if ($Command =~/^\w+-all|^repair/) {
		if ((defined $Arg1) || (defined $Arg2)) {
			return 0;
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command);
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^start|^stop|^reset|^delete|^wipe|^xfer/) {
		if ((!(defined $Arg1)) || (defined $Arg2)) {;
			return 0;
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command)." ".quotemeta($Arg1);
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^inject|^watch|^maintenance/) {
		if ((!(defined $Arg1)) || (!(defined $Arg2))) {
			return 0;
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command)." ".quotemeta($Arg1)." ".quotemeta($Arg2);
			return doSysCall($shellCmd);
		}
	}
	if ($Command =~/^rss/) {
		if ((!(defined $Arg1)) || (!(defined $Arg2)) || (!(defined $Arg3)) || (!(defined $Arg4))) {
			return 0;
		} else {
			my $shellCmd = $bin_php." bin/".$bin_fluxcli." ".quotemeta($Command)." ".quotemeta($Arg1)." ".quotemeta($Arg2)." ".quotemeta($Arg3)." ".quotemeta($Arg4);
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
	$command .= " 1>> ".$log." 2>> ".$log_error." &";
    system($command);
    if ($? == -1) {
		printError("CORE", "failed to execute: ".$!."; command:\n".$command."\n");
    } elsif ($? & 127) {
		printError("CORE", (sprintf "child died with signal %d, %s coredump; command:\n%s\n", ($? & 127),  ($? & 128) ? 'with' : 'without'), $command);
    } else {
		if ($loglevel > 2) {
			printMessage("CORE", (sprintf "child exited with value %d; command:\n%s\n", $? >> 8, $command));
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
	$server = IO::Socket::UNIX->new(
			Type    => IO::Socket::UNIX->SOCK_STREAM,
			Local   => $path_socket,
			Listen  => 16,
			Reuse   => 1,
			);

	# check socket
	unless ($server) {
		printError("CORE", "could not create socket: ".$!."\n");
		exit;
	}

	# print
	if ($loglevel > 0) {
		printMessage("CORE", "created socket ".$path_socket."\n");
	}

	# create select
	$select = new IO::Select();

	# Add our server socket to the select read set.
	$select->add($server);
}

#------------------------------------------------------------------------------#
# Sub: socketRemove                                                            #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub socketRemove {
	if ($loglevel > 0) {
		printMessage("CORE", "removing socket ".$path_socket."\n");
	}
	unlink($path_socket);
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
	if ($loglevel > 0) {
		printMessage("CORE", "writing pid-file ".$file_pid." (pid: ".$pid.")\n");
	}
	open(PIDFILE,">$file_pid");
	print PIDFILE $pid."\n";
	close(PIDFILE);
}

#------------------------------------------------------------------------------#
# Sub: pidFileDelete                                                           #
# Arguments: null                                                              #
# Returns: return-val of delete                                                #
#------------------------------------------------------------------------------#
sub pidFileDelete {
	if ($loglevel > 0) {
		printMessage("CORE", "deleting pid-file ".$file_pid."\n");
	}
	return unlink($file_pid);
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Server information page                                             #
#------------------------------------------------------------------------------#
sub status {
	my $head = "";
	$head .= "\n\nfluxd has been up since ".$start_time_local." (".FluxCommon::niceTimeString($start_time).")\n\n";
	$head .= "data-dir : ".$path_data_dir."\n";
	$head .= "log : ".$log."\n";
	$head .= "error-log : ".$log_error."\n";
	$head .= "pid : ".$file_pid."\n";
	$head .= "socket : ".$path_socket."\n";
	$head .= "transfers-dir : ".$path_transfer_dir."\n";
	$head .= "docroot : ".$path_docroot."\n";
	$head .= "fluxcli : ".$pwd."/bin/".$bin_fluxcli."\n";
	$head .= "php : ".$bin_php."\n";
	$head .= "db-mode : ".$dbMode."\n";
	$head .= "loglevel : ".$loglevel."\n";
	$head .= "\n";
	my $status = "";
	my $modules = "- Loaded Modules -\n";
	foreach my $smod (sort keys %serviceModules) {
		if ((exists $serviceModuleObjects{$serviceModules{$smod}{"name"}}) &&
			($serviceModuleObjects{$serviceModules{$smod}{"name"}}->getState() == MOD_STATE_OK)) {
			$modules .= "  * ".$serviceModules{$smod}{"name"}."\n";
			$status .= eval { $serviceModuleObjects{$serviceModules{$smod}{"name"}}->status(); };
		}
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
	# FluxCommon
	print "FluxCommon Version : ";
	print FluxCommon::getVersion()."\n";
	# StatFile
	print "StatFile Version : ";
	print StatFile::getVersion()."\n";
	# FluxDB
	print "FluxDB Version : ";
	if (eval "require FluxDB") {
		print FluxDB->getVersion()."\n";
	} else {
		print "cant load module\n";
	}
	# service-mods
	foreach my $smod (sort keys %serviceModules) {
		print $serviceModules{$smod}{"name"}." Version : ";
		if (eval "require ".$serviceModules{$smod}{"name"}) {
			my $modversion =  eval $serviceModules{$smod}{"name"}."->getVersion();";
			print $modversion."\n";
		} else {
			print "cant load module\n";
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: check                                                                   #
# Arguments: Null                                                              #
# Returns: info on sys requirements                                            #
#------------------------------------------------------------------------------#
sub check {

	my $errors = 0;
	my $warnings = 0;
	my @errorMessages = ();
	my @warningMessages = ();
	printMessage("CORE", "checking requirements...\n");

	# 1. CORE-Perl-modules
	printMessage("CORE", "1. CORE-Perl-modules\n");
	my @mods = ('IO::Select', 'IO::Socket::UNIX', 'IO::Socket::INET', 'POSIX');
	foreach my $mod (@mods) {
		if (eval "require $mod")  {
			printMessage("CORE", "   - OK : ".$mod."\n");
			next;
		} else {
			$errors++;
			push(@errorMessages, "Loading of CORE-Perl-module ".$mod." failed.\n");
			printMessage("CORE", "   - FAILED : ".$mod."\n");
		}
	}

	# 2. FluxDB-Perl-modules
	printMessage("CORE", "2. Database-Perl-modules\n");
	if (eval "require DBI")  {
		printMessage("CORE", "   - OK : DBI\n");
	} else {
		$warnings++;
		push(@warningMessages, "Loading of FluxDB-Perl-module DBI failed. fluxd cannot work in DBI/DBD-mode but only in PHP-mode.\n");
		printMessage("CORE", "   - FAILED : DBI\n");
	}
	my $dbdwarnings = 0;
	@mods = ('DBD::mysql', 'DBD::SQLite', 'DBD::Pg');
	foreach my $mod (@mods) {
		if (eval "require $mod")  {
			printMessage("CORE", "   - OK : ".$mod."\n");
			next;
		} else {
			$dbdwarnings++;
			printMessage("CORE", "   - FAILED : ".$mod."\n");
		}
	}
	if ($dbdwarnings == 3) {
		$warnings++;
		push(@warningMessages, "No DBD-Module could be loaded. fluxd cannot work in DBI/DBD-mode but only in PHP-mode.\n");
	}

	# 3. Result
	printMessage("CORE", "3. Result : ".(($errors == 0) ? "PASSED" : "FAILED")."\n");
	# failures
	if ($errors > 0) {
		printMessage("CORE", "Errors:\n");
		foreach my $msg (@errorMessages) {
			printMessage("CORE", $msg);
		}
	}
	# warnings
	if ($warnings > 0) {
		printMessage("CORE", "Warnings:\n");
		foreach my $msg (@warningMessages) {
			printMessage("CORE", $msg);
		}
	}
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
		printMessage("CORE", "debug is missing an operation.\n");
		exit;
	}

	# database-debug
	if ($debug =~ /db/) {
		# $path_docroot
		my $temp = shift @ARGV;
		if (!(defined $temp)) {
			printMessage("CORE", "debug database is missing an argument : path to docroot\n");
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_docroot = $temp;
		# PATH_PATH
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printMessage("CORE", "debug database is missing an argument : path to path\n");
			exit;
		}
		if (!((substr $temp, -1) eq "/")) {
			$temp .= "/";
		}
		$path_path = $temp;
		# $bin_php
		$temp = shift @ARGV;
		if (!(defined $temp)) {
			printMessage("CORE", "debug database is missing an argument : path to php\n");
			exit;
		}
		$bin_php = $temp;
		printMessage("CORE", "debugging database...\n");
		# require
		require FluxDB;
		# create instance
		printMessage("CORE", "creating \$fluxDB\n");
		$fluxDB = FluxDB->new();
		# PHP
		# initialize
		printMessage("CORE", "initializing \$fluxDB (php)\n");
		$fluxDB->initialize($path_docroot, $bin_php, "php");
		if ($fluxDB->getState() != MOD_STATE_OK) {
			printMessage("CORE", "error : ".$fluxDB->getMessage()."\n");
			exit;
		}
		# something from the bean
		printMessage("CORE", "FluxConfig(\"path\") : \"".FluxDB->getFluxConfig("path")."\"\n");
		printMessage("CORE", "FluxConfig(\"docroot\") : \"".FluxDB->getFluxConfig("docroot")."\"\n");
		# test to set a val
		printMessage("CORE", "FluxConfig(\"default_theme\") : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		$fluxDB->setFluxConfig("default_theme","foo");
		printMessage("CORE", "FluxConfig(\"default_theme\") after set : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		# now reload and check again
		$fluxDB->reload();
		printMessage("CORE", "FluxConfig(\"default_theme\") after reload : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		# destroy
		printMessage("CORE", "destroying \$fluxDB\n");
		$fluxDB->destroy();
		# DBI
		# initialize
		printMessage("CORE", "initializing \$fluxDB (dbi)\n");
		$fluxDB->initialize($path_docroot, $bin_php, "dbi");
		if ($fluxDB->getState() != MOD_STATE_OK) {
			printMessage("CORE", "error : ".$fluxDB->getMessage()."\n");
			# db-settings
			printMessage("CORE", " DatabaseType : \"".$fluxDB->getDatabaseType()."\"\n");
			printMessage("CORE", " DatabaseName : \"".$fluxDB->getDatabaseName()."\"\n");
			printMessage("CORE", " DatabaseHost : \"".$fluxDB->getDatabaseHost()."\"\n");
			printMessage("CORE", " DatabasePort : \"".$fluxDB->getDatabasePort()."\"\n");
			printMessage("CORE", " DatabaseUser : \"".$fluxDB->getDatabaseUser()."\"\n");
			printMessage("CORE", " DatabasePassword : \"".$fluxDB->getDatabasePassword()."\"\n");
			printMessage("CORE", " DatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n");
			exit;
		}
		# db-settings
		printMessage("CORE", "DatabaseDSN : \"".$fluxDB->getDatabaseDSN()."\"\n");
		# something from the bean
		printMessage("CORE", "FluxConfig(\"path\") : \"".FluxDB->getFluxConfig("path")."\"\n");
		printMessage("CORE", "FluxConfig(\"docroot\") : \"".FluxDB->getFluxConfig("docroot")."\"\n");
		# test to set a val
		printMessage("CORE", "FluxConfig(\"default_theme\") : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		$fluxDB->setFluxConfig("default_theme","foo");
		printMessage("CORE", "FluxConfig(\"default_theme\") after set : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		# now reload and check again
		$fluxDB->reload();
		printMessage("CORE", "FluxConfig(\"default_theme\") after reload : \"".FluxDB->getFluxConfig("default_theme")."\"\n");
		# destroy
		printMessage("CORE", "destroying \$fluxDB\n");
		$fluxDB->destroy();
		# done
		printMessage("CORE", "database debug done.\n");
		exit;
	}

	# bail out
	printMessage("CORE", "debug is missing an operation.\n");
	exit;
}

#------------------------------------------------------------------------------#
# Sub: getLoglevel                                                             #
# Arguments: null                                                              #
# Returns: loglevel-int                                                        #
#------------------------------------------------------------------------------#
sub getLoglevel {
	return $loglevel;
}

#------------------------------------------------------------------------------#
# Sub: getPathDataDir                                                          #
# Arguments: null                                                              #
# Returns: path-string                                                         #
#------------------------------------------------------------------------------#
sub getPathDataDir {
	return $path_data_dir;
}

#------------------------------------------------------------------------------#
# Sub: getPathTransferDir                                                      #
# Arguments: null                                                              #
# Returns: path-string                                                         #
#------------------------------------------------------------------------------#
sub getPathTransferDir {
	return $path_transfer_dir;
}

#------------------------------------------------------------------------------#
# Sub: printMessage                                                            #
# Arguments: module, message                                                   #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub printMessage {
	my $module = shift;
	my $message = shift;
	print STDOUT FluxCommon::getTimeStamp()."[".$module."] ".$message;
}

#------------------------------------------------------------------------------#
# Sub: printError                                                              #
# Arguments: module, message                                                   #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub printError {
	my $module = shift;
	my $message = shift;
	print STDERR FluxCommon::getTimeStamp()."[".$module."] ".$message;
}

#------------------------------------------------------------------------------#
# Sub: logMessage                                                              #
# Arguments: module, message                                                   #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub logMessage {
	my $module = shift;
	my $message = shift;
	logToFile($log, FluxCommon::getTimeStamp()."[".$module."] ".$message);
}

#------------------------------------------------------------------------------#
# Sub: logError                                                                #
# Arguments: module, message                                                   #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub logError {
	my $module = shift;
	my $message = shift;
	logToFile($log_error, FluxCommon::getTimeStamp()."[".$module."] ".$message);
}

#------------------------------------------------------------------------------#
# Sub: logToFile                                                               #
# Arguments: file, message                                                     #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub logToFile {
	my $file = shift;
	my $message = shift;
	open(LOG, ">>$file");
	print LOG $message;
	close LOG;
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

Usage:

 $PROG.$EXTENSION <start> path-to-docroot path-to-path path-to-php db-mode
   start fluxd daemon.
   db-mode : dbi/php

 $PROG.$EXTENSION <stop> path-to-docroot path-to-path path-to-php db-mode
   stop fluxd daemon
   db-mode : dbi/php

 $PROG.$EXTENSION <check>
   check for requirements.

 $PROG.$EXTENSION <debug> type path-to-docroot path-to-path path-to-php
   debug fluxd daemon
   type : db

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
