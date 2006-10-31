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
package Watch;
use strict;
use warnings;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

# state
# -1 error
#  0 not initialized (null)
#  1 initialized
my $state = 0;

# message, error etc. keep it in one string for simplicity atm.
my $message = "";

# loglevel
my $LOGLEVEL = 2;

# run-interval
my $interval;

# time of last run
my $time_last_run = 0;

# jobs-hash
my %jobs;

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: Null                                                              #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {
	my $class = shift;
	my $self = bless ({}, ref ($class) || $class);
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub destroy {
	# set state
	$state = 0;
	# log
	print "Watch : shutdown\n";
	# undef
	undef %jobs;
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: interval, jobs                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# loglevel
	$LOGLEVEL = shift;
	if (!(defined $LOGLEVEL)) {
		# message
		$message = "loglevel not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# interval
	$interval = shift;
	if (!(defined $interval)) {
		# message
		$message = "interval not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# jobs
	my $jobs = shift;
	if (!(defined $jobs)) {
		# message
		$message = "jobs not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	print "Watch : initializing (loglevel: ".$LOGLEVEL." ; interval: ".$interval." ; jobs: ".$jobs.")\n";

	# parse jobs
	my (@jobsAry) = split(/;/,$jobs);
	foreach my $jobEntry (@jobsAry) {
		chomp $jobEntry;
		my (@jobAry) = split(/:/,$jobEntry);
		my $user = shift @jobAry;
		chomp $user;
		my $dir = shift @jobAry;
		chomp $dir;
		if ((!($user eq "")) && (-d $dir)) {
			if ($LOGLEVEL > 1) {
				print "Watch : job : user=".$user.", dir=".$dir."\n";
			}
			$jobs{$user} = $dir;
		}
	}

	# reset last run time
	$time_last_run = time();

	# set state
	$state = 1;

	# return
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: getVersion                                                              #
# Arguments: null                                                              #
# Returns: VERSION                                                             #
#------------------------------------------------------------------------------#
sub getVersion {
	return $VERSION;
}

#------------------------------------------------------------------------------#
# Sub: getState                                                                #
# Arguments: null                                                              #
# Returns: state                                                               #
#------------------------------------------------------------------------------#
sub getState {
	return $state;
}

#------------------------------------------------------------------------------#
# Sub: getMessage                                                              #
# Arguments: null                                                              #
# Returns: message                                                             #
#------------------------------------------------------------------------------#
sub getMessage {
	return $message;
}

#------------------------------------------------------------------------------#
# Sub: set                                                                     #
# Arguments: Variable [value]                                                  #
# Returns:                                                                     #
#------------------------------------------------------------------------------#
sub set {
}

#------------------------------------------------------------------------------#
# Sub: main                                                                    #
# Arguments: Null                                                              #
# Returns:                                                                     #
#------------------------------------------------------------------------------#
sub main {
	my $now = time();
	if (($now - $time_last_run) >= $interval) {

		# watch in dirs for dropped meta-files
		foreach my $user (sort keys %jobs) {
			my $dir = $jobs{$user};
			if ((!($user eq "")) && (-d $dir)) {
				if ($LOGLEVEL > 1) {
					print "Watch : executing job (".localtime().") :\n";
					print " user: ".$user."\n";
					print " dir: ".$dir."\n";
				}
				# exec
				tfwatch($dir, $user);
			}
		}

		# set last run time
		$time_last_run = time();

	}
}

#------------------------------------------------------------------------------#
# Sub: command                                                                 #
# Arguments: command-string                                                    #
# Returns: result-string                                                       #
#------------------------------------------------------------------------------#
sub command {
	shift; # class
	my $command = shift;
	# TODO
	return "";
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Status information                                                  #
#------------------------------------------------------------------------------#
sub status {
	my $return = "";
	$return .= "\n-= Watch Revision ".$VERSION." =-\n";
	$return .= "interval : $interval s \n";
	$return .= "jobs :\n";
	foreach my $user (sort keys %jobs) {
		my $dir = $jobs{$user};
		if ((!($user eq "")) && (-d $dir)) {
			$return .= "  * username: ".$user."\n";
			$return .= "    dir: ".$dir."\n";
		}
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: tfwatch                                                                 #
# Arguments: dir, user                                                         #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub tfwatch {
	my $dir = shift;
	my $user = shift;
	# fluxcli-call
	return Fluxd::fluxcli("watch", $dir, $user);
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
