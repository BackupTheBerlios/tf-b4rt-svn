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

# usernames
my %names;

# users
my @users;

# data-dir
my $dataDir = "qmgr/";

# queue-file
my $fileQueue = "Qmgr.queue";

# transfers-dir
my $transfersDir = ".transfers";

# limits
my $limitGlobal = 5;
my $limitUser = 2;
my $limitStartTries = 5;
my $startTrySleep = 10;

# time-vars
my ($time, $localtime);

# globals
my %globals;

# jobs
my %jobs;

# queue
my @queue;

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
		print "Qmgr : jobs queued : ".$jobcount.". writing queue-file...";
		# save queue
		saveQueue();
	}
	# undef
	undef %names;
	undef @users;
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
	my $transfersDir = shift;
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

	# limit-sys, limit-user
	$limitGlobal = shift;
	if (!(defined $interval)) {
		# message
		$message = "interval not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# limit-sys, limit-user
	$limitUser = shift;
	if (!(defined $interval)) {
		# message
		$message = "interval not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	print "Qmgr : initializing (loglevel: ".$LOGLEVEL." data-dir: ".$dataDir." ; interval: ".$interval." ; limit-global: ".$limitGlobal." ; limit-user: ".$limitUser.")\n";

	# Create some time vars
	$time = time();
	$localtime = localtime();

	# get users + usernames
	@users = FluxDB->getFluxUsers();
	%names = FluxDB->getFluxUsernames();

	# initialize our globals hash
	$globals{'main'} = 0;
	$globals{'started'} = 0;

	#initialize the queue
	if (-f $fileQueue) {
		print "Qmgr : Loading Queue-file\n";
		# actually load the queue
		loadQueue();
	} else {
		print "Qmgr : Creating empty queue\n";
		@queue = qw();
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

		# process queue
		processQueue();

		# set last run time
		$time_last_run = $now;
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
				print "Qmgr : enqueue : \"".$1."\" (user : ".$2.")\n";
			}
			# TODO
			#return add($1,$2);
			return 1;
		};
		/^dequeue;(.*);(.*)/ && do {
			if ($LOGLEVEL > 1) {
				print "Qmgr : dequeue : \"".$1."\" (user : ".$2.")\n";
			}
			# TODO
			#return remove($1,$2);
			return 1;
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

	# update running torrents
	updateRunningTorrents();

	# TODO
	return 1;

=for later
	# process queue
	my $jobcountq = queue();
	my $notDoneProcessingQueue = 1;
	if ($jobcountq > 0) { # we have queued jobs

		USER: foreach my $user (@users) {
			# initilize some variables for this user
			my $queueId = 0;
			my $startTry = 0;

			# Grab the next torrent
			my $nextTorrent = ${$user->{'queue'}}[$queueId];
			my $nextUser = $user->{'username'};

			# check to ensure that we aren't already running this torrent
			foreach my $torrent (@{$user->{'running'}}) {
				if ($torrent eq $nextTorrent) {
					print "Qmgr : removing already running job from queue $nextTorrent ($nextUser)\n";
					stack($queueId, \@{$user->{'queue'}});

					if ($queueId < (queue()-1)) {
						# there is a next entry
						print "Qmgr : next queue-entry\n" if ($LOGLEVEL > 2);
						$queueId++;
					} else {
						# queue is empty
						print "Qmgr : last queue-entry for $nextUser\n" if ($LOGLEVEL > 2);
						next USER;
					}
				}
			}

			# ok, torrent isn't running, try to start it up

			# check to see if system max applies
			my $jobCount = running();
			if ($jobCount >= $limitGlobal) {
				# Can't start it now.
				print "Qmgr : Max limit applies, skipping torrent $nextTorrent ($nextUser)\n" if ($LOGLEVEL);
				last USER;
			}

			# check to see if user max applies
			if (scalar($user->{'running'}) >= $limitUser) {
				# Can't start it now.
				print "Qmgr : User limit applies, skipping torrent $nextTorrent ($nextUser)\n" if ($LOGLEVEL);
				next USER;
			}

			# Neither limit applies, we can try to start the torrent
			print "Qmgr : starting torrent $nextTorrent ($nextUser)\n";
			if (Fluxd::fluxcli("start", $nextTorrent) == 1) {
				# reset start-tries counter
				$startTry = 0;

				# remove torrent from queue
				print "Qmgr : Removing $nextTorrent from queue\n" if ($LOGLEVEL);
				stack($queueId, \@{$user->{'queue'}});

				# slow it down now!
				sleep 1;
			} else {
				# how many times have we tried to start this thing?
				if ($startTry >= $limitStartTries) {
					# TODO : provide option to remove bogus torrents
					print "Qmgr : tried $limitStartTries to start $nextTorrent, skipping\n" if ($LOGLEVEL);
					next USER;
				} else {
					$startTry++;
					sleep $startTrySleep; # TODO : new looping code .. this should not be here any longer as it blocks main
				}
			}
		} # USER

		$globals{'main'} += 1;
	}
=cut




}

#-------------------------------------------------------------------------------#
# Sub: loadQueue                                                                #
# Arguments: Null                                                               #
# Returns: Null                                                                 #
#-------------------------------------------------------------------------------#
sub loadQueue {
	# read from file into queue-array
	open(QUEUEFILE,"< $fileQueue");
	while (<QUEUEFILE>) {
		chomp;
		push(@queue, $_);
	}
	close QUEUEFILE;
	# fill job-hash
	foreach my $torrent (@queue) {
		my $user = getTorrentOwner($torrent);
		if (!(defined $user)) {
			$jobs{"queued"}{$torrent} = "unknown";
		} else {
			if (! exists $jobs{"queued"}{$torrent}) {
				$jobs{"queued"}{$torrent} = $user;
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
	# open queue-file
	open(QUEUEFILE,">$fileQueue");
	# queued torrents
	foreach my $queueEntry (@queue) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#-------------------------------------------------------------------------------
# Sub: dumpQueue
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub dumpQueue {
	# open queue-file
	open(QUEUEFILE,">$fileQueue");
	# running torrents
	foreach my $jobName (keys %{$jobs{"running"}}) {
		print QUEUEFILE $jobName."\n";
	}
	# queued torrents
	foreach my $queueEntry (@queue) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#-----------------------------------------------------------------------------#
# Sub: status                                                                 #
# Arguments: Null                                                             #
# Returns: status string                                                      #
#-----------------------------------------------------------------------------#
sub status {
	my $return = "";
	$return .= "\n-= Qmgr.pm Revision ".$VERSION." =-\n";
	$return .= "interval : $interval s \n";
	# get count-vars
	my $countQueue = countQueue();
	my $countRunning = countRunning();
	my $countJobs = $countQueue + $countRunning;
	# some vars
	$return .= "max torrents global : $limitGlobal \n";
	$return .= "max torrents per user : $limitUser \n";
	$return .= "max start-tries : $limitStartTries \n";
	$return .= "start-try-extra-sleep : $startTrySleep s\n";
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
	my $tempiStringy = FluxdCommon::niceTimeString($time);
	$return .= $tempiStringy.") ";
	$return .= "(".$globals{'main'}." cycles) \n";
	$return .= "started transfers : ".$globals{'started'}."\n";
	# return
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: countJobs                                                               #
# Parameters:	-                                                              #
# Return:	number of  Jobs                                                    #
#------------------------------------------------------------------------------#
sub countJobs {
	my $jobcount = 0;
	$jobcount += countQueue();
	$jobcount += countRunning();
	return $jobcount;
}

#------------------------------------------------------------------------------#
# Sub: countQueue                                                              #
# Parameters:	-                                                              #
# Return:	number of queued jobs                                              #
#------------------------------------------------------------------------------#
sub countQueue {
	return scalar((keys %{$jobs{"queued"}}));
}

#------------------------------------------------------------------------------#
# Sub: countRunning                                                            #
# Parameters:	-                                                              #
# Return:	number of queued jobs                                              #
#------------------------------------------------------------------------------#
sub countRunning {
	return scalar((keys %{$jobs{"running"}}));
}


#------------------------------------------------------------------------------#
# Sub: listQueue                                                               #
# Arguments: Null                                                              #
# Returns: List of queued torrents                                             #
#------------------------------------------------------------------------------#
sub listQueue {
	my $return = "";
	foreach my $queueEntry (@queue) {
		$return .= $queueEntry.".torrent\n";
	}
	return $return;
}

#------------------------------------------------------------------------------#
# Sub: add                                                                     #
# Arguments: torrent, user                                                     #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub add {
=for later
	# add a torrent to the queue
	my $AddIt = 0;
	# Verify that the arguments look good
	my $temp = shift;
	if (!(defined $temp)) {
		printUsage();
		return;
	}
	my $torrent = $temp;
	$torrent = StripTorrentName($torrent);
	$temp = shift;
	if (!(defined $temp)) {
		printUsage();
		return;
	}
	my $username = $temp;
	# Looks good, add it to the queue.
	USER: foreach my $user (@users) {
		foreach my $entry (@{$user->{'queue'}}) {
			if ($torrent eq $entry) {
				print "Qmgr: Job already exists : ".$torrent." (".$user->{'username'}.")" if ($LOGLEVEL);
				last USER;
			}
		}
		foreach my $entry (@{$user->{'running'}}) {
			if ($torrent eq $entry) {
				print "Qmgr: Job already exists : ".$torrent." (".$user->{'username'}.")" if ($LOGLEVEL);
				last USER;
			}
		}
	}
	$AddIt = 1;
	print "Qmgr: Adding job to queue : ".$torrent." (".$username.")";
	if ($AddIt == 1) {
		#push ({@users->[%names->{$username}]->{'queue'}}, $torrent);
	}
=cut



}

#------------------------------------------------------------------------------#
# Sub: remove                                                                  #
# Arguments: torrent, user                                                     #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub remove {
=for later
	# remove a torrent from the queue
	# Check arguments
	my $temp = shift;
	if (!(defined $temp)) {
		printUsage();
		return;
	}
	my $torrent = $temp;
	#$torrent = StripTorrentName();
	my $username = getTorrentOwner($torrent);
	print "Qmgr : Remove : Removing from queue : ".$torrent." (".$username.")";
	# Remove from queue stack
	my $index = 0;
	#REMOVE: foreach my $entry (@{$users->[$names->{$username}]->{'queue'}}) {
	#	if ($torrent eq $entry) {
	#		stack($index, \@{$users->[$names->{$username}]->{'queue'}});
	#		last REMOVE;
	#	}
	#	$index++;
	#}
=cut


}

#------------------------------------------------------------------------------#
# Sub: updateRunningTorrents                                                   #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub updateRunningTorrents {
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
			my $torrent = (substr ($pidFile, 0, (length($pidFile)) - 9));
			my $user = getTorrentOwner($torrent);
			if (!(defined $user)) {
				$jobs{"running"}{$torrent} = "unknown";
			} else {
				if (! exists $jobs{"running"}{$torrent}) {
					$jobs{"running"}{$torrent} = $user;
				}
			}
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: getTorrentOwner                                                         #
# Arguments: torrent                                                           #
# Returns: user                                                                #
#------------------------------------------------------------------------------#
sub getTorrentOwner {
	my $torrent = shift;
	if (!(defined $torrent)) {
		return undef;
	}
	my $statFile = $transfersDir.$torrent.".stat";
	if (-f $statFile) {
		open(STATFILE,"< $statFile");
		while (<STATFILE>) {
			if ($. == 6) {
				chomp;
				close STATFILE;
				return $_;
			}
		}
		close STATFILE;
	}
	return undef;
}

#------------------------------------------------------------------------------#
# Sub: startTransfer                                                           #
# Parameters:	transfer-name                                                  #
# Return:		int with return of start-call (0|1)                            #
#------------------------------------------------------------------------------#
sub startTransfer {
	my $transfer = shift;
	if (!(defined $transfer)) {
		return 0;
	}
	# start transfer
	if (Fluxd::fluxcli("start", $transfer) == 1) {
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

################################################################################
# make perl happy                                                              #
################################################################################
1;
