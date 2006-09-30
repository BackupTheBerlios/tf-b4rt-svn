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
no strict "refs";
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

my ( $time, $localtime, %globals );
my $PATH_QUEUE_FILE = $Fluxd::PATH_DATA_DIR."fluxd.queue";
my ( $MAX_SYS, $MAX_USR );
my $MAX_START_TRIES = 5;
my $START_TRIES_SLEEP = 10;

# references to the FluxDB @users and %names for use internally. Just makes
# everything look cleaner
my $users = @FluxDB::users;
my $names = %FluxDB::names;

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
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

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

	print "initializing Qmgr (interval: ".$interval.")\n"; # DEBUG

	# Create some time vars
	$time = time();
	$localtime = localtime();

	# Create and start the log file
	print "Qmgr : Starting : Qmgr\n";

	# initialize internal variables
	$MAX_SYS = FluxDB->getFluxConfig("fluxd_Qmgr_maxTotalTorrents");
	$MAX_USR = FluxDB->getFluxConfig("fluxd_Qmgr_maxUserTorrents");

	# initialize our globals hash
	$globals{'main'} = 0;
	$globals{'started'} = 0;

	#initialize the queue
	if (-f $PATH_QUEUE_FILE) {
		print "Qmgr : Loading Queue-file\n";
		# actually load the queue
		loadQueue();
	} else {
		print "Qmgr : Creating empty queue\n";
		foreach my $user (@FluxDB::users) {
			$user->{"queue"} = ();
			$user->{"running"} = ();
		}
	}

	# reset last run time
	$time_last_run = 0;

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

		# process queue
		#processQueue();
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
			return jobs();
		};
		/^count-queue/ && do {
			return queue();
		};
		/^list-queue/ && do {
			return list();
		};
		/^enqueue;(.*);(.*)/ && do {
			print "Qmgr : enqueue : \"".$1."\" (user : ".$2.")\n"; # DEBUG
			# TODO
			#return add($1,$2);
			return 1;
		};
		/^dequeue;(.*);(.*)/ && do {
			print "Qmgr : dequeue : \"".$1."\" (user : ".$2.")\n"; # DEBUG
			# TODO
			#return remove($1,$2);
			return 1;
		};
		/^set;(.*);(.*)/ && do {
			print "Qmgr : set : \"".$1."\"->\"".$2."\")\n"; # DEBUG
			return set($1,$2);
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
	UpdateRunningTorrents();

	# process queue
	my $jobcountq = Queue();
	my $notDoneProcessingQueue = 1;
	if ($jobcountq > 0) { # we have queued jobs

		USER: foreach my $user (@{$users}) {
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

					if ($queueId < (Queue()-1)) {
						# there is a next entry
						print "Qmgr : next queue-entry\n" if ($Fluxd::LOGLEVEL > 2);
						$queueId++;
					} else {
						# queue is empty
						print "Qmgr : last queue-entry for $nextUser\n" if ($Fluxd::LOGLEVEL > 2);
						next USER;
					}
				}
			}

			# ok, torrent isn't running, try to start it up

			# check to see if system max applies
			my $jobCount = running();
			if ($jobCount >= $MAX_SYS) {
				# Can't start it now.
				print "Qmgr : Max limit applies, skipping torrent $nextTorrent ($nextUser)\n" if ($Fluxd::LOGLEVEL);
				last USER;
			}

			# check to see if user max applies
			if (scalar($user->{'running'}) >= $MAX_USR) {
				# Can't start it now.
				print "Qmgr : User limit applies, skipping torrent $nextTorrent ($nextUser)\n" if ($Fluxd::LOGLEVEL);
				next USER;
			}

			# Neither limit applies, we can try to start the torrent
			print "Qmgr : starting torrent $nextTorrent ($nextUser)\n";
			if (Fluxd::fluxcli("start", $nextTorrent) == 1) {
				# reset start-tries counter
				$startTry = 0;

				# remove torrent from queue
				print "Qmgr : Removing $nextTorrent from queue\n" if ($Fluxd::LOGLEVEL);
				stack($queueId, \@{$user->{'queue'}});

				# slow it down now!
				sleep 1;
			} else {
				# how many times have we tried to start this thing?
				if ($startTry >= $MAX_START_TRIES) {
					# TODO : provide option to remove bogus torrents
					print "Qmgr : tried $MAX_START_TRIES to start $nextTorrent, skipping\n" if ($Fluxd::LOGLEVEL);
					next USER;
				} else {
					$startTry++;
					sleep $START_TRIES_SLEEP; # TODO : new looping code .. this should not be here any longer as it blocks main
				}
			}
		} # USER

		$globals{'main'} += 1;
	}
}

#-------------------------------------------------------------------------------
# Sub: printVersion
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printVersion {
	print "Qmgr.pm Revision ".$VERSION."\n";
}

#-----------------------------------------------------------------------------#
# Sub: loadQueue                                                              #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub loadQueue {
	# read from file into queue-array
	open(QUEUEFILE,"< $PATH_QUEUE_FILE");
	while (<QUEUEFILE>) {
		chomp;
		my ( $torrent, $username ) = split;
		push(@{$users->[$names->{$username}]->{'queue'}}, $torrent);
		#push(@{$FluxDB::users[$FluxDB::names{$username}]{'queue'}}, $torrent);
		#push(${users[$names->{$username}]{'queue'}}, $torrent);
	}
	close QUEUEFILE;
	# done loading, delete queue-file
	return unlink($PATH_QUEUE_FILE);
}

#-----------------------------------------------------------------------------#
# Sub: saveQueue                                                              #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub saveQueue {
	# open queue-file
	open(QUEUEFILE,">$PATH_QUEUE_FILE");
	# queued torrents
	foreach my $user (@{$users}) {
		foreach my $torrent (@{$user->{'queue'}}) {
			print QUEUEFILE $torrent." ".$user->{"username"}."\n";
		}
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
	my $countQueue = queue();
	my $countRunning = running();
	my $countJobs = $countQueue + $countRunning;
	# some vars
	$return .= "max torrents global : $MAX_SYS \n";
	$return .= "max torrents per user : $MAX_USR \n";
	$return .= "max start-tries : $MAX_START_TRIES \n";
	$return .= "start-try-extra-sleep : $START_TRIES_SLEEP s\n";
	# jobs total
	$return .= "jobs total : ".$countJobs."\n";
	# jobs queued
	$return .= "jobs queued : ".$countQueue."\n";
	foreach my $user (@{$users}) {
		foreach my $jobName (@{$user->{'queue'}}) {
			my $jobUser = $user->{'username'};
			$return .= "  * ".$jobName." (".$jobUser.")\n";
		}
	}
	# jobs running
	$return .= "jobs running : ".$countRunning."\n";
	foreach my $user (@{$users}) {
		foreach my $jobName (@{$user->{'running'}}) {
			my $jobUser = $user->{'username'};
			$return .= "  * ".$jobName." (".$jobUser.")\n";
		}
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

#-----------------------------------------------------------------------------#
# Sub: jobs                                                                   #
# Arguments: Null                                                             #
# Returns: Total number of jobs                                               #
#-----------------------------------------------------------------------------#
sub jobs {
	my $jobcount = 0;
	$jobcount += queue();
	$jobcount += running();
        return $jobcount;
}

#-----------------------------------------------------------------------------#
# Sub: queue                                                                  #
# Arguments: Null                                                             #
# Returns: Number of queued jobs                                              #
#-----------------------------------------------------------------------------#
sub queue {
	my $return = 0;
	foreach my $user (@{$users}) {
		$return += scalar(@{$user->{'queue'}});
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: running                                                                #
# Arguments: Null                                                             #
# Returns: Number of running jobs                                             #
#-----------------------------------------------------------------------------#
sub running{
	my $return = 0;
	foreach my $user (@{$users}) {
		$return += scalar(@{$user->{'running'}});
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: list                                                                   #
# Arguments: Null                                                             #
# Returns: List of queued torrents                                            #
#-----------------------------------------------------------------------------#
sub list {
	my $return = "";
	# return list
	foreach my $user (@{$users}) {
		foreach my $queueEntry (@{$user->{'queue'}}) {
			$return .= $queueEntry.".torrent\n";
		}
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: add                                                                    #
# Arguments: torrent, user                                                    #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub add {
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
	USER: foreach my $user (@{$users}) {
		foreach my $entry (@{$user->{'queue'}}) {
			if ($torrent eq $entry) {
				print "Qmgr: Job already exists : ".$torrent." (".$user->{'username'}.")" if ($Fluxd::LOGLEVEL);
				last USER;
			}
		}
		foreach my $entry (@{$user->{'running'}}) {
			if ($torrent eq $entry) {
				print "Qmgr: Job already exists : ".$torrent." (".$user->{'username'}.")" if ($Fluxd::LOGLEVEL);
				last USER;
			}
		}
	}
	$AddIt = 1;
	print "Qmgr: Adding job to queue : ".$torrent." (".$username.")";

	if ($AddIt == 1) {
		push (@{$users->[$names->{$username}]->{'queue'}}, $torrent);
	}
}

#-----------------------------------------------------------------------------#
# Sub: remove                                                                 #
# Arguments: torrent, user                                                    #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub remove {
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
	REMOVE: foreach my $entry (@{$users->[$names->{$username}]->{'queue'}}) {
		if ($torrent eq $entry) {
			stack($index, \@{$users->[$names->{$username}]->{'queue'}});
			last REMOVE;
		}
		$index++;
	}
}

#-----------------------------------------------------------------------------#
# Sub: updateRunningTorrents                                                  #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub updateRunningTorrents {
	# Get current list of running torrents
	# get running clients
	opendir(DIR, $Fluxd::PATH_TORRENT_DIR);
	my @pids = map { $_->[1] } # extract pathnames
	map { [ $_, "$_" ] } # no full paths
	grep { !/^\./ } # no dot-files
	grep { /.*\.pid$/ } # only .pid-files
	readdir(DIR);
	closedir(DIR);
	# flush running-jobs-hash
	foreach my $user (@FluxDB::users) {
		$user->{'running'} = ();
	}
	# refill hash
	if (scalar(@pids) > 0) {
		foreach my $pidFile (@pids) {
			my $torrent = (substr ($pidFile,0,(length($pidFile))-9));
			my $username = getTorrentOwner($torrent);
			push(@{$users->[$names->{$username}]->{'running'}}, $torrent);
		}
	}
}

#-----------------------------------------------------------------------------#
# Sub: getTorrentOwner                                                        #
# Arguments: torrent                                                          #
# Returns: user                                                               #
#-----------------------------------------------------------------------------#
sub getTorrentOwner {
	my $torrent = shift;
	if (!(defined $torrent)) {
		return undef;
	}
	my $statFile = $Fluxd::PATH_TORRENT_DIR.$torrent.".stat";
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
