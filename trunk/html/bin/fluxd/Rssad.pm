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
package Rssad;
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

# run-interval
my $interval;

# time of last run
my $time_last_run = 0;

# jobs
my @jobs;

# perl
my $binPerl = "perl";

# binTfrss
my $binTfrss = "/var/www/bin/tfrss/tfrss.pl";

# data-dir
my $dataDir = "rssad/";

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: Null                                                              #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {
	my $self = {};
	bless $self;
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub destroy {
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: path-to-perl, path-to-tfrss, data-dir, interval, jobs             #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# path-perl
	$binPerl = shift;
	if (!(defined $binPerl)) {
		# message
		$message = "path-to-perl not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}
	if (!(-x $binPerl)) {
		# message
		$message = "cant execute perl (".$binPerl.")";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# tfrss.pl
	$binTfrss = shift;
	if (!(defined $binTfrss)) {
		# message
		$message = "path-to-tfrss not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}
	if (!(-f $binTfrss)) {
		# message
		$message = "tfrss.pl no file (".$binTfrss.")";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# data-dir
	my $ddir = shift;
	if (!(defined $ddir)) {
		# message
		$message = "data-dir not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}
	$dataDir = $ddir . $dataDir;
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $dataDir) {
		print "Rssad : creating data-dir : ".$dataDir."\n"; # DEBUG
		mkdir($dataDir, 0700);
		if (! -d $dataDir) {
			# message
			$message = "data-dir does not exist and cannot be created";
			# set state
			$state = -1;
			# return
			return 0;
		}
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

	print "Rssad : initializing (data-dir: ".$dataDir." ; interval: ".$interval." ; jobs: ".$jobs.")\n"; # DEBUG

	# parse jobs
	# job1|job2|job3
	my (@jobsAry) = split(/\|/, $jobs);
	foreach my $jobEntry (@jobsAry) {
		# username#url#filtername
		chomp $jobEntry;
		my (@jobAry) = split(/#/,$jobEntry);
		my $savedir = shift @jobAry;
		chomp $savedir;
		my $url = shift @jobAry;
		chomp $url;
		my $filter = shift @jobAry;
		chomp $filter;
		# job-entry
		print "Rssad : job : savedir=".$savedir.", url=".$url.", filter=".$filter."\n"; # DEBUG
		# add to jobs-array
		if ((!($savedir eq "")) && (!($url eq "")) && (!($filter eq ""))) {
			my $index = scalar(@jobs);
			$jobs[$index] = {
				'savedir' => $savedir,
				'url' => $url,
				'filter' => $filter
			};
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

		# set last run time
		$time_last_run = $now;

		# TODO
		my $jobCount = scalar(@jobs);
		for (my $i = 0; $i < $jobCount; $i++) {
			my $url = $jobs[$i]{"url"};
			my $filter = $dataDir.$jobs[$i]{"filter"}.".dat";
			my $history = $dataDir.$jobs[$i]{"filter"}.".hist";
			my $savedir = $jobs[$i]{"savedir"};
			print "Rssad : executing job :\n"; # DEBUG
			print " url: ".$url."\n"; # DEBUG
			print " filter: ".$filter."\n"; # DEBUG
			print " history: ".$history."\n"; # DEBUG
			print " savedir: ".$savedir."\n"; # DEBUG
		}
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
	$return .= "\n-= Rssad.pm Revision ".$VERSION." =-\n";
	$return .= "interval : $interval s \n";
	$return .= "jobs :\n";
	my $jobCount = scalar(@jobs);
	for (my $i = 0; $i < $jobCount; $i++) {
		$return .= "  * savedir: ".$jobs[$i]{"savedir"}."\n";
		$return .= "    url: ".$jobs[$i]{"url"}."\n";
		$return .= "    filter: ".$jobs[$i]{"filter"}."\n";
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: tfrss                                                                   #
# Arguments: rss-feed-url, filter-file, history-file, save-location            #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub tfrss {
	my $url = shift;
	my $filter = shift;
	my $history = shift;
	my $save = shift;
	my $shellCmd = "";
	$shellCmd .= $binPerl;
	$shellCmd .= " ".$binTfrss;
	$shellCmd .= " ".$url;
	$shellCmd .= " ".$filter;
	$shellCmd .= " ".$history;
	$shellCmd .= " ".$save;
	$shellCmd .= " > /dev/null";
	eval {
		system($shellCmd);
	};
	if ($@) {
		print STDERR "Rssad : error calling tfrss.pl (".$shellCmd.") : ".$@."\n";
		return 0;
	}
	return 1;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
