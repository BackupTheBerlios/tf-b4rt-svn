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
package Qmgr;
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

# data-dir
my $dataDir = "qmgr/";

# queue-file
my $fileQueue = "qmgr.queue";

# transfers-dir
my $transfersDir = ".transfers";

# time-vars
my ($time, $localtime);

# globals
my %globals;

# jobs
my %jobs;

# queue
my @queue;
my $queueIdx = 0;

# some defaults
my $DEFAULT_limitStartTries = 3;
my $DEFAULT_startTrySleep = 5;

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new (Constructor Method)                                                #
# Arguments: Null                                                              #
# Returns: Object                                                              #
#------------------------------------------------------------------------------#
sub new {
	my $objclass = shift;
	my $self = {};
	bless ($self, $objclass);
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
	print "Qmgr : shutdown\n";
	# save queue
	my $jobcount = countQueue();
	if ($jobcount > 0) {
		if ($LOGLEVEL > 0) {
			print "Qmgr : jobs queued : ".$jobcount.". writing queue-file...\n";
		}
		# save queue
		saveQueue();
	}
	# undef
	undef %globals;
	undef %jobs;
	undef @queue;
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: loglevel,data-dir,transfers-dir,interval,limit-sys,limit-user     #
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
		print "Qmgr : creating data-dir : ".$dataDir."\n";
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
	# queue-file
	$fileQueue = $dataDir . $fileQueue;

	# transfers-dir
	$transfersDir = shift;
	if (!(defined $transfersDir)) {
		# message
		$message = "transfers-dir not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}
	if (! -d $transfersDir) {
		# message
		$message = "transfers-dir does not exist";
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

	# global-limit
	my $limitGlobal = shift;
	if (!(defined $limitGlobal)) {
		# message
		$message = "global-limit not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# user-limit
	my $limitUser = shift;
	if (!(defined $limitUser)) {
		# message
		$message = "user-limit not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	print "Qmgr : initializing (loglevel: ".$LOGLEVEL." ; data-dir: ".$dataDir." ; interval: ".$interval." ; global-limit: ".$limitGlobal." ; user-limit: ".$limitUser.")\n";

	# Create some time vars
	$time = time();
	$localtime = localtime();

	# initialize our globals hash
	$globals{'main'} = 0;
	$globals{'started'} = 0;
	$globals{'limitGlobal'} = $limitGlobal;
	$globals{'limitUser'} = $limitUser;
	$globals{'limitStartTries'} = $DEFAULT_limitStartTries;
	$globals{'startTrySleep'} = $DEFAULT_startTrySleep;

	#initialize the queue
	if (-f $fileQueue) {
		# actually load the queue
		loadQueue();
	} else {
		if ($LOGLEVEL > 0) {
			print "Qmgr : creating empty queue\n";
		}
		@queue = qw();
	}

	# update running transfers
	updateRunningTransfers();

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
	my $key = shift;
	$globals{$key} = shift;
}

#------------------------------------------------------------------------------#
# Sub: main                                                                    #
# Arguments: Null                                                              #
# Returns:                                                                     #
#------------------------------------------------------------------------------#
sub main {

	my $now = time();
	if (($now - $time_last_run) >= $interval) {

		# log
		if ($LOGLEVEL > 1) {
			print "Qmgr : process queue...\n";
		}

		# process queue
		processQueue();

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
	$_= shift;
	SWITCH: {
		/^count-jobs/ && do {
			return countJobs();
		};
		/^count-queue/ && do {
			return countQueue();
		};
		/^list-queue/ && do {
			return listQueue();
		};
		/^enqueue;(.*);(.*)/ && do {
			if ($LOGLEVEL > 1) {
				print "Qmgr : enqueue-request : ".$1." (".$2.")\n";
			}
			return add($1, $2);
		};
		/^dequeue;(.*);(.*)/ && do {
			if ($LOGLEVEL > 1) {
				print "Qmgr : dequeue-request : ".$1." (".$2.")\n";
			}
			return remove($1, $2);
		};
		/^set;(.*);(.*)/ && do {
			if ($LOGLEVEL > 1) {
				print "Qmgr : set : \"".$1."\"->\"".$2."\")\n";
			}
			return set($1, $2);
		};
	}
	return "Unknown command";
}

#-------------------------------------------------------------------------------#
# Sub: processQueue                                                             #
# Arguments: Null                                                               #
# Returns: Null                                                                 #
#-------------------------------------------------------------------------------#
sub processQueue {
	$queueIdx = 0;
	my $startTry = 0;
	QUEUE: while (1) {
		# update running transfers
		updateRunningTransfers();
		# process queue
		my $jobcountq = countQueue();
		if ($jobcountq > 0) {                                                   # we have queued jobs
			# next job
			my $nextTransfer = $queue[$queueIdx];
			my $nextUser = $jobs{"queued"}{$nextTransfer};
			# check if this queue-entry exists in running-jobs.
			# dont try to start what is already running.
			# this may be after a restart or transfer was started outside.
			if (exists $jobs{"running"}{$nextTransfer}) {                       # already running
				# remove job from queue
				my $removed = remove($nextTransfer, $nextUser);
				if ($removed == 0) { $queueIdx++; }
				# check if more entries
				if (queueHasMoreEntries() == 0) { last QUEUE; }
			} else {                                                            # transfer not already running
				my @jobAry = (keys %{$jobs{"running"}});
				my $jobcountr = scalar(@jobAry);
				# lets see if max limit applies
				if ($jobcountr < $globals{'limitGlobal'}) {                     # max limit does not apply
					# lets see if per user limit applies
					$jobcountr = 0;
					foreach my $anJob (@jobAry) {
						if ($jobs{"running"}{$anJob} eq $nextUser) {
							$jobcountr++;
						}
					}
					if ($jobcountr < $globals{'limitUser'}) {                   # user limit does not apply
						# startup the thing
						if ($LOGLEVEL > 0) {
							print "Qmgr : starting transfer : ".$nextTransfer." (".$nextUser.") (".localtime().")\n";
						}
						if (startTransfer($nextTransfer) == 1) {                # start transfer succeeded
							# reset start-counter-var
							$startTry = 0;
							# remove job from queue
							my $removed = remove($nextTransfer, $nextUser);
							if ($removed == 0) { $queueIdx++; }
							# add job to jobs running
							if ($LOGLEVEL > 0) {
								print "Qmgr : adding job to jobs running : ".$nextTransfer." (".$nextUser.")\n";
							}
							$jobs{"running"}{$nextTransfer} = $nextUser;
							# check if more entries
							if (queueHasMoreEntries() == 0) { last QUEUE; }
						} else {                                                # start transfer failed
							print STDERR "Qmgr : start transfer failed : ".$nextTransfer." (".$nextUser.")\n";
							# already tried max-times to start this thing ?
							if ($startTry == $globals{'limitStartTries'}) {
								$startTry = 0;
								print STDERR "Qmgr : ".$globals{'limitStartTries'}." errors when starting, cancel job : ".$nextTransfer." (".$nextUser.")\n";
								# remove job from queue
								my $removed = remove($nextTransfer, $nextUser);
								if ($removed == 0) { $queueIdx++; }
								# check if more entries
								if (queueHasMoreEntries() == 0) { last QUEUE; }
							} else {
								$startTry++;
								sleep $globals{'startTrySleep'};
							}
						} # end start transfer failed
					} else {                                                    # user-limit for this user applies
						# check next queue-entry if one exists
						if ($queueIdx < (countQueue() - 1)) { # there is a next entry
							if ($LOGLEVEL > 0) {
								print "Qmgr : user limit reached, skipping job : ".$nextTransfer." (".$nextUser.") (next queue-entry)\n";
							}
							$queueIdx++;
						} else { # no more in queue
							if ($LOGLEVEL > 0) {
								print "Qmgr : user limit reached, skipping job : ".$nextTransfer." (".$nextUser.") (last queue-entry)\n";
							}
							last QUEUE;
						}
					}
				} else {                                                        # max limit does apply
					if ($LOGLEVEL > 0) {
						print "Qmgr : max limit reached, skipping job : ".$nextTransfer." (".$nextUser.")\n";
					}
					last QUEUE;
				}
			} # end already runnin
		} else {                                                                # no queued jobs
			if ($LOGLEVEL > 1) {
				print "Qmgr : empty queue...\n";
			}
			last QUEUE;
		}
	} # queue-while-loop
	# increment main-count
	$globals{"main"} += 1;
}

#------------------------------------------------------------------------------#
# Sub: queueHasMoreEntries                                                     #
# Arguments: Null                                                              #
# Returns: 0|num of entries                                                    #
#------------------------------------------------------------------------------#
sub queueHasMoreEntries {
	# check if more entries
	my $jobcount = countQueue();
	if ($jobcount > 0) { # more jobs in queue
		if ($queueIdx < ($jobcount)) { # there is a next entry
			if ($LOGLEVEL > 1) {
				print "Qmgr : next queue-entry\n";
			}
			return ($jobcount - $queueIdx);
		} else { # no more in queue
			if ($LOGLEVEL > 1) {
				print "Qmgr : last queue-entry\n";
			}
			return 0;
		}
	} else { # nothing more in queue
		if ($LOGLEVEL > 1) {
			print "Qmgr : last queue-entry\n";
		}
		return 0;
	}
}

#------------------------------------------------------------------------------#
# Sub: loadQueue                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub loadQueue {
	if ($LOGLEVEL > 0) {
		print "Qmgr : loading queue-file : ".$fileQueue."\n";
	}
	# read from file into queue-array
	my $lineSep = $/;
	$/ = "\n";
	open(QUEUEFILE,"< $fileQueue");
	while (<QUEUEFILE>) {
		chomp;
		push(@queue, $_);
	}
	close QUEUEFILE;
	$/ = $lineSep;
	# fill job-hash
	foreach my $transfer (@queue) {
		my $user = getTransferOwner($transfer);
		if (!(defined $user)) {
			$jobs{"queued"}{$transfer} = "unknown";
		} else {
			if (! exists $jobs{"queued"}{$transfer}) {
				$jobs{"queued"}{$transfer} = $user;
			}
		}
	}
	# done loading, delete queue-file
	return unlink($fileQueue);
}

#------------------------------------------------------------------------------#
# Sub: saveQueue                                                               #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub saveQueue {
	if ($LOGLEVEL > 1) {
		print "Qmgr : saving queue-file : ".$fileQueue."\n";
	}
	# open queue-file
	open(QUEUEFILE,">$fileQueue");
	# queued transfers
	foreach my $queueEntry (@queue) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#------------------------------------------------------------------------------#
# Sub: dumpQueue                                                               #
# Parameters: -                                                                #
# Return: -                                                                    #
#------------------------------------------------------------------------------#
sub dumpQueue {
	if ($LOGLEVEL > 0) {
		print "Qmgr : dumping queue-file : ".$fileQueue."\n";
	}
	# open queue-file
	open(QUEUEFILE,">$fileQueue");
	# running transfers
	foreach my $jobName (keys %{$jobs{"running"}}) {
		print QUEUEFILE $jobName."\n";
	}
	# queued transfers
	foreach my $queueEntry (@queue) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#------------------------------------------------------------------------------#
# Sub: countJobs                                                               #
# Parameters: -                                                                #
# Return: number of  Jobs                                                      #
#------------------------------------------------------------------------------#
sub countJobs {
	my $jobcount = 0;
	$jobcount += countQueue();
	$jobcount += countRunning();
	return $jobcount;
}

#------------------------------------------------------------------------------#
# Sub: countQueue                                                              #
# Parameters: -                                                                #
# Return: number of queued jobs                                                #
#------------------------------------------------------------------------------#
sub countQueue {
	return scalar(@queue);
	#return scalar((keys %{$jobs{"queued"}}));
}

#------------------------------------------------------------------------------#
# Sub: countRunning                                                            #
# Parameters: -                                                                #
# Return: number of queued jobs                                                #
#------------------------------------------------------------------------------#
sub countRunning {
	return scalar((keys %{$jobs{"running"}}));
}

#------------------------------------------------------------------------------#
# Sub: listQueue                                                               #
# Arguments: Null                                                              #
# Returns: List of queued transfers                                            #
#------------------------------------------------------------------------------#
sub listQueue {
	my $return = "";
	foreach my $queueEntry (@queue) {
		$return .= $queueEntry."\n";
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: add                                                                     #
# Arguments: transfer, user                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub add {
	# Verify that the arguments look good
	my $temp = shift;
	if (!(defined $temp)) {
		print STDERR "Qmgr : invalid argument for transfer on add\n";
		return 0;
	}
	my $transfer = $temp;
	$temp = shift;
	if (!(defined $temp)) {
		print STDERR "Qmgr : invalid argument for username on add\n";
		return 0;
	}
	my $username = $temp;
	# add it
	my $addIt = 0;
	if ((! exists $jobs{"queued"}{$transfer}) && (! exists $jobs{"running"}{$transfer})) {
		$addIt = 1;
		if ($LOGLEVEL > 0) {
			print "Qmgr : adding job to jobs queued : ".$transfer." (".$username.")\n";
		}
		$jobs{"queued"}{$transfer} = $username;
	} else {
		if ($LOGLEVEL > 0) {
			print "Qmgr : job already present in jobs : ".$transfer." (".$username.")\n";
		}
	}
	if ($addIt == 1) {
		# add
		push(@queue,$transfer);
		# save queue
		saveQueue();
	}
	# return
	return $addIt;
}

#------------------------------------------------------------------------------#
# Sub: remove                                                                  #
# Arguments: transfer, user                                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub remove {
	# Verify that the arguments look good
	my $temp = shift;
	if (!(defined $temp)) {
		print STDERR "Qmgr : invalid argument for transfer on remove\n";
		return 0;
	}
	my $transfer = $temp;
	$temp = shift;
	if (!(defined $temp)) {
		print STDERR "Qmgr : invalid argument for username on remove\n";
		return 0;
	}
	my $username = $temp;
	# log
	if ($LOGLEVEL > 0) {
		print "Qmgr : remove job from jobs queued : ".$transfer." (".$username.")\n";
	}
	# remove from job-hash
	my $retValJobs = 0;
	if (exists $jobs{"queued"}{$transfer}) {
		delete($jobs{"queued"}{$transfer});
		$retValJobs = 1;
	}
	# remove from queue-stack
	my $retValQueue = 0;
	my $Idx = 0;
	LOOP: foreach my $queueEntry (@queue) {
		if ($queueEntry eq $transfer) {
			$retValQueue = 1;
			last LOOP;
		}
		$Idx++;
	}
	if ($retValQueue > 0) {
		if ($Idx > 0) { # not first entry, stack-action
			my @stack;
			for (my $i = 0; $i < $Idx; $i++) {
				push(@stack, (shift @queue));
			}
			shift @queue;
			for (my $i = 0; $i < $Idx; $i++) {
				push(@queue, (shift @stack));
			}
			$Idx--;
		} else { # first entry, just shift
			shift @queue;
		}
	}
	# return / save
	if (($retValJobs > 0) && ($retValQueue > 0)) {
		# save queue
		saveQueue();
		return 1;
	} else {
		return 0;
	}
}

#------------------------------------------------------------------------------#
# Sub: updateRunningTransfers                                                  #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub updateRunningTransfers {
	# get runnin clients
	opendir(DIR, $transfersDir);
	my @pids = map { $_->[1] } # extract pathnames
	map { [ $_, "$_" ] } # no full paths
	grep { !/^\./ } # no dot-files
	grep { /.*\.pid$/ } # only .pid-files
	readdir(DIR);
	closedir(DIR);
	# flush running-jobs-hash
	foreach my $jobName (keys %{$jobs{"running"}}) {
		# delete job
		delete($jobs{"running"}{$jobName});
	}
	# refill hash
	if (scalar(@pids) > 0) {
		foreach my $pidFile (@pids) {
			my $transfer = (substr ($pidFile, 0, (length($pidFile)) - 9));
			my $user = getTransferOwner($transfer);
			if ((!(defined $user)) || ($user eq "")) {
				$jobs{"running"}{$transfer} = "unknown";
			} else {
				if (! exists $jobs{"running"}{$transfer}) {
					$jobs{"running"}{$transfer} = $user;
				}
			}
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: getTransferOwner                                                        #
# Arguments: transfer                                                          #
# Returns: user                                                                #
#------------------------------------------------------------------------------#
sub getTransferOwner {
	my $transfer = shift;
	if (!(defined $transfer)) {
		return undef;
	}
	my $statFile = $transfersDir.$transfer.".stat";
	if (-f $statFile) {
		my $lineSep = $/;
		$/ = "\n";
		$. = 0;
		open(STATFILE,"<$statFile");
		while (<STATFILE>) {
			if ($. == 6) {
				chomp;
				close STATFILE;
				$/ = $lineSep;
				return $_;
			}
		}
		close STATFILE;
		$/ = $lineSep;
	}
	return undef;
}

#------------------------------------------------------------------------------#
# Sub: startTransfer                                                           #
# Parameters: transfer-name                                                    #
# Return: int with return of start-call (0|1)                                  #
#------------------------------------------------------------------------------#
sub startTransfer {
	my $transfer = shift;
	if (!(defined $transfer)) {
		return 0;
	}
	# fluxcli-call
	my $result = Fluxd::fluxcli("start", $transfer.".torrent");
	if ($result == 1) {
		$globals{"started"} += 1;
		return 1;
	}
	return 0;
}

#------------------------------------------------------------------------------#
# Sub: stack                                                                   #
# Arguments: integer, array ref                                                #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub stack {
	my $index = shift;
	my $array = shift;
	if ($index) {
		my @stack;
		for (my $i = 0; $i < $index; $i++) {
			push(@stack, (shift @$array));
		}
		shift @$array;
		for (my $i = 0; $i < $index; $i++) {
			push(@$array, (shift @stack));
		}
		$index--;
	} else {
		shift @$array;
	}
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: status string                                                       #
#------------------------------------------------------------------------------#
sub status {
	my $return = "";
	$return .= "\n-= Qmgr Revision ".$VERSION." =-\n";
	$return .= "interval : ".$interval." s \n";
	# get count-vars
	my $countQueue = countQueue();
	my $countRunning = countRunning();
	my $countJobs = $countQueue + $countRunning;
	# some vars
	$return .= "max transfers global : ".$globals{'limitGlobal'}."\n";
	$return .= "max transfers per user : ".$globals{'limitUser'}."\n";
	$return .= "max start-tries : ".$globals{'limitStartTries'}."\n";
	$return .= "start-try-extra-sleep : ".$globals{'startTrySleep'}." s\n";
	# jobs total
	$return .= "jobs total : ".$countJobs."\n";
	# jobs queued
	$return .= "jobs queued : ".$countQueue."\n";
	foreach my $jobName (sort keys %{$jobs{"queued"}}) {
		my $jobUser = $jobs{"queued"}{$jobName};
		$return .= "  * ".$jobName." (".$jobUser.")\n";
	}
	# jobs running
	$return .= "jobs running : ".$countRunning."\n";
	foreach my $jobName (sort keys %{$jobs{"running"}}) {
		my $jobUser = $jobs{"running"}{$jobName};
		$return .= "  * ".$jobName." (".$jobUser.")\n";
	}
	# misc stats
	$return .= "running since : $localtime (";
	$return .= FluxdCommon::niceTimeString($time).") ";
	$return .= "(".$globals{'main'}." cycles) \n";
	$return .= "started transfers : ".$globals{'started'}."\n";
	# return
	return $return;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
