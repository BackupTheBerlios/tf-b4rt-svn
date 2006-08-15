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

my ( $time, $localtime, %globals );
my $PATH_QUEUE_FILE = $Fluxd::PATH_DATA_DIR."fluxd.queue";
my ( $MAX_TORRENTS_SYS, $MAX_TORRENTS_USR );

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

	print "initializing Qmgr\n"; # DEBUG

	# Create some time vars
	$time = time();
	$localtime = localtime();

	# Create and start the log file
	print "Qmgr : Starting : Qmgr";

	# initialize our globals hash
	$globals{'main'} = 0;
	$globals{'started'} = 0;

	#initialize the queue
	if (-f $PATH_QUEUE_FILE) {
		print "Qmgr : Loading Queue-file";
		# actually load the queue
		LoadQueue();
	} else {
		print "Qmgr : Creating empty queue";
		foreach my $user (@FluxDB::users) {
			$user{"queue"} = ();
			$user{"running"} = ();
		}
	}

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

#-------------------------------------------------------------------------------#
# Sub: ProcessQueue                                                             #
# Arguments: Null                                                               #
# Returns: Null                                                                 #
#-------------------------------------------------------------------------------#
sub ProcessQueue {
	# hold cycle-start-time
	my $timeStart = time();

	# update running torrents
	UpdateRunningTorrents();

	# process queue
	my $jobcountq = Queue();
	$notDoneProcessingQueue = 1;
	if ($jobcountq > 0) { # we have queued jobs

	USER: foreach my $user (@FluxDB::users) {
		# initilize some variables for this user
		my $queueId = 0
		my $startTry = 0;

		# Grab the next torrent
		my $nextTorrent = $user{'queue'}[queueId];
		my $nextUser = $user{'username'};

		# check to ensure that we aren't already running this torrent
		foreach my $torrent ($user{'Running'} {
			if ($torrent eq $nextTorrent) {
				print "Qmgr : removing already running job from queue $nextTorrent ($nextUser)\n";
				stack($queueId, \@{user{'queue'}});

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
		my $jobCount = Running();
		if ($jobCount >= $MAX_SYS) {
			# Can't start it now.
			print "Qmgr : Max limit applies, skipping torrent $nextTorrent ($nextUser)\n" if ($Fluxd::LOGLEVEL);
			last USER;
		}

		# check to see if user max applies
		if (scalar($user{'Running'} >= $MAX_USR) {
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
			stack($queueId, \@{user{'queue'}});

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
				sleep $START_TRIES_SLEEP;
			}
		}
	} # USER

	$globals{'main'} += 1;
}

#-------------------------------------------------------------------------------
# Sub: printVersion
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printVersion {
	print "Qmgr.pm Revision ".$REVISION."\n";
}

#-----------------------------------------------------------------------------#
# Sub: LoadQueue                                                              #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub LoadQueue {
	# read from file into queue-array
	open(QUEUEFILE,"< $PATH_QUEUE_FILE");
	while (<QUEUEFILE>) {
		chomp;
		my ( $torrent, $username ) = split;
		push($users[$names{$username}]{'queue'}, $torrent);
	}
	close QUEUEFILE;
	# done loading, delete queue-file
	return unlink($QmgrVars{'PATH_QUEUE_FILE'});
}

#-----------------------------------------------------------------------------#
# Sub: SaveQueue                                                              #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub SaveQueue {
	# open queue-file
	open(QUEUEFILE,">$PATH_QUEUE_FILE");
	# queued torrents
	foreach my $user (@FluxDB::users) {
		foreach my $torrent (@{user{'queue'}}) {
			print QUEUEFILE $torrent." ".$user{"username"}."\n";
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
	my $return = "status info\n";
	$return .= "\n-= Qmgrd Revision ".$QmgrVars{'REVISION'}." =-\n\n";

	# get count-vars
	my $countQueue = Queue();
	my $countRunning = Running();
	my $countJobs = $countQueue + $countRunning;

	# some vars
	$return .= "min sleep-time worker \t: $QmgrVars{'SLEEP_MIN'} s \n";
	$return .= "max sleep-time worker \t: $QmgrVars{'SLEEP_MAX'} s \n";
	$return .= "max torrents global \t: $QmgrVars{'MAX_TORRENTS_SYS'} \n";
	$return .= "max torrents per user \t: $QmgrVars{'MAX_TORRENTS_USR'} \n";
	$return .= "max start-tries    \t: $QmgrVars{'MAX_START_TRIES'} \n";
	$return .= "start-try-extra-sleep \t: $QmgrVars{'START_TRIES_SLEEP'} s\n\n";

	# jobs total
	$return .= "jobs total \t: ".$countJobs."\n";

	# jobs queued
	$return .= "jobs queued \t: ".$countQueue."\n";
	foreach my $jobName (sort keys %{$QmgrVars{'jobs'}{'queued'}}) {
		my $jobUser = $QmgrVars{'jobs'}{'queued'}{$jobName};
		$return .= "  * ".$jobName." (".$jobUser.")\n";
	}

	# jobs running
	$return .= "jobs running \t: ".$countRunning."\n";
	foreach my $jobName (sort keys %{$QmgrVars{'jobs'}{'running'}}) {
		my $jobUser = $QmgrVars{'jobs'}{'running'}{$jobName};
		$return .= "  * ".$jobName." (".$jobUser.")\n";
	}

	# misc stats
	$return .= "\nQmgr Daemon up since $QmgrVars{'localtime'} (";
	my $tempiStringy = niceTimeString($QmgrVars{'time'});
	$return .= $tempiStringy.") ";
	$return .= "(".$QmgrVars{'globals'}{'main'}." cycles) \n";
	$return .= "Qmgr Daemon started ".$QmgrVars{'globals'}{'started'}." torrents \n\n";

	# dump path vars on debug
	if ($QmgrVars{'LOGLEVEL'} > 3) {
		$return .= "PATH_DATA_DIR : ".$QmgrVars{'PATH_DATA_DIR'}."\n";
		$return .= "PATH_QUEUE_FILE : ".$QmgrVars{'PATH_QUEUE_FILE'}."\n";
		$return .= "LOG_FILE : ".$QmgrVars{'PATH_LOG_FILE'}."\n";
		$return .= "PID_FILE : ".$QmgrVars{'PATH_PID_FILE'}."\n";
		$return .= "PATH_TORRENT_DIR : ".$QmgrVars{'PATH_TORRENT_DIR'}."\n\n";
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: Jobs                                                                   #
# Arguments: Null                                                             #
# Returns: Total number of jobs                                               #
#-----------------------------------------------------------------------------#
sub Jobs {
	my $jobcount = 0;
	$jobcount += Queue();
	$jobcount += Running();
        return $jobcount;
}

#-----------------------------------------------------------------------------#
# Sub: Queue                                                                  #
# Arguments: Null                                                             #
# Returns: Number of queued jobs                                              #
#-----------------------------------------------------------------------------#
sub Queue {
	my $return = 0;
	foreach my $user (@FluxDB::users) {
		return += scalar($user{'queue'});
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: Running                                                                #
# Arguments: Null                                                             #
# Returns: Number of running jobs                                             #
#-----------------------------------------------------------------------------#
sub Running{
	my $return = 0;
	foreach my $user (@FluxDB::users) {
		return += scalar($user{'running'});
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: List                                                                   #
# Arguments: Null                                                             #
# Returns: List of queued torrents                                            #
#-----------------------------------------------------------------------------#
sub List {
	my $return = "";
	# return list
	foreach my $user (@FluxDB::users) {
		foreach my $queueEntry (@{user{'queue'}}) {
			$return .= $queueEntry.".torrent\n";
		}
	}
	return $return;
}

#-----------------------------------------------------------------------------#
# Sub: Add                                                                    #
# Arguments: torrent, user                                                    #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub Add {
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
	my $user = $temp;

	# Looks good, add it to the queue.
	if ((! exists $QmgrVars{'jobs'}{'queued'}{$torrent}) && (! exists $QmgrVars{'jobs'}{'running'}{$torrent})) {
		$AddIt = 1;
		WriteLog("main : Adding job to queue : ".$torrent." (".$user.")");
		$QmgrVars{'jobs'}{'queued'}{$torrent} = $user;
	} else {
		if ($QmgrVars{'LOGLEVEL'} > 1) {
			WriteLog("main : Job already exists : ".$torrent." (".$user.")");
		}
	}

	if ($AddIt == 1) {
		push(@{$QmgrVars{'queue'}}, $torrent);
	}
}

#-----------------------------------------------------------------------------#
# Sub: Remove                                                                 #
# Arguments: torrent, user                                                    #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub Remove {
	# remove a torrent from the queue

	# Check arguments
	my $temp = shift;
	if (!(defined $temp)) {
		printUsage();
		return;
	}
	my $torrent = $temp;
	#$torrent = StripTorrentName();

	my $user = getTorrentOwner($torrent);

	WriteLog("Remove : Removing from queue : ".$torrent." (".$user.")");
	delete($QmgrVars{'jobs'}{'queued'}{$torrent});

	# Remove from queue stack
	my $ID = 0;
	LOOP: foreach my $Entry (@{$QmgrVars{'queue'}}) {
		last LOOP if ($Entry eq $torrent);
		$ID++;
	}

	if ($ID > 0) {
		my @stack;
		for (my $i = 0; $i < $ID; $i++) {
			push(@stack, (shift @{$QmgrVars{'queue'}}));
		}
		shift @{$QmgrVars{'queue'}};
		for (my $i = 0; $i < $ID; $i++) {
			push(@{$QmgrVars{'queue'}}, (shift @stack));
		}
		$ID--;
	} else {
		shift (@{$QmgrVars{'queue'}});
	}
}

#-----------------------------------------------------------------------------#
# Sub: UpdateRunningTorrents                                                  #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub UpdateRunningTorrents {
	# Get current list of running torrents
	# get running clients
	opendir(DIR, $QmgrVars{'PATH_TORRENT_DIR'});
	my @pids = map { $_->[1] } # extract pathnames
	map { [ $_, "$_" ] } # no full paths
	grep { !/^\./ } # no dot-files
	grep { /.*\.pid$/ } # only .pid-files
	readdir(DIR);
	closedir(DIR);
	# flush running-jobs-hash
	foreach my $jobName (keys %{$QmgrVars{'jobs'}{'running'}}) {
		# delete job
		delete($QmgrVars{'jobs'}{'running'}{$jobName});
	}
	# refill hash
	if (scalar(@pids) > 0) {
		foreach my $pidFile (@pids) {
			my $torrent = (substr ($pidFile,0,(length($pidFile))-9));
			my $user = getTorrentOwner($torrent);
			if (!(defined $user)) {
				$QmgrVars{'jobs'}{'running'}{$torrent} = "unknown";
			} else {
				if (! exists $QmgrVars{'jobs'}{'running'}{$torrent}) {
					$QmgrVars{'jobs'}{'running'}{$torrent} = $user;
				}
			}
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
	my $statFile = $QmgrVars{'PATH_TORRENT_DIR'}.$torrent.".stat";
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

#-------------------------------------------------------------------------------
# Sub: niceTimeString
# Parameters:   start-time
# Return:               nice Time String
#-------------------------------------------------------------------------------
sub niceTimeString {
        my $startTime = shift;
        my ($dura,$duration,$days,$hours,$mins,$secs,$rest);
        $dura = ((time)-$startTime);
        $rest = $dura;
        $days = $hours = $mins = $secs = 0;
        $duration = "";
        if ($dura >= (24*60*60)) { # days
                $days = int((($rest/60)/60)/24);
                $duration .= $days."d ";
                $rest = ($dura-($days*60*60*24));
        }
        if ($dura >= (60*60)) { # hours
                $hours = int(($rest/60)/60);
                $duration .= $hours."h ";
                $rest = ($dura-($hours*60*60)-($days*60*60*24));
        }
        if ($rest >= 60) { # mins
                $mins = int($rest/60);
                $duration .= $mins."m ";
                $rest = ($dura-($mins*60)-($hours*60*60)-($days*60*60*24));
        }
        if ($rest > 0) { # secs
                $duration .= $rest."s";
        }
        return $duration;
}

#-----------------------------------------------------------------------------#
# Sub: MoveUp                                                                 #
# Arguments: Torrent-id                                                       #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub MoveUp {
	my $torrent = shift;
	if (!(defined $torrent)) {
		printUsage();
		return;
	}
	my $index = 0;
	LOOP: foreach my $entry (@{$QmgrVars{'queue'}}) {
		if ($entry eq $torrent) {
			if ($index == 0) { # Torrent is already number one!
				WriteLog("MoveUp : Can't move torrent up, already at the top");
			} else {
				# Swap $torrent and the one before it
				my $temp = ${$QmgrVars{'queue'}}[$index - 1];
				${$QmgrVars{'queue'}}[$index - 1] = $torrent;
				${$QmgrVars{'queue'}}[$index] = $temp;
				last LOOP;
			}
			$index += 1;
		}
	}
}

#-----------------------------------------------------------------------------#
# Sub: MoveDown                                                               #
# Arguments: Torrent-id                                                       #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub MoveDown {
	my $torrent = shift;
	if (!(defined $torrent)) {
		printUsage();
		return;
	}
	my $index = 0;
	LOOP: foreach my $entry (@{$QmgrVars{'queue'}}) {
		if ($entry eq $torrent) {
			if ( $index == scalar(@{$QmgrVars{'queue'}}) ) {
				# We're already the last torrent!
				WriteLog("MoveDown : Can't move torrent down, already at the bottom");
			} else {
				# Swap $torrent and the one below it
				my $temp = ${$QmgrVars{'queue'}}[$index + 1];
				${$QmgrVars{'queue'}}[$index + 1] = $torrent;
				${$QmgrVars{'queue'}}[$index] = $temp;
				last LOOP;
			}
			$index += 1;
		}
	}
}

#-----------------------------------------------------------------------------#
# Sub: MoveTop                                                                #
# Arguments: Torrent-id                                                       #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub MoveTop {
	my $torrent = shift;
	if (!(defined $torrent)) {
		printUsage();
		return;
	}
	while ( ${$QmgrVars{'queue'}}[0] ne $torrent ) {
		MoveUp($torrent);
		print ${$QmgrVars{'queue'}}[0]."\n";
		print $torrent."\n";
	}
}

#-----------------------------------------------------------------------------#
# Sub: MoveBottom                                                             #
# Arguments: Torrent-id                                                       #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub MoveBottom {
	my $torrent = shift;
	if (!(defined $torrent)) {
		printUsage();
		return;
	}
	while ( ${$QmgrVars{'queue'}}[Queue()] ne $torrent ) {
		MoveDown($torrent);
	}
}

#-----------------------------------------------------------------------------#
# Sub: StripTorrentName                                                       #
# Arguments: Torrent-id                                                       #
# Returns: fixed Torrent-id                                                   #
#-----------------------------------------------------------------------------#
sub StripTorrentName {
	my $torrent = shift;
	if (!(defined $torrent)) {
		printUsage();
		return;
	}

	@_ = split(m!/!, $torrent);

	$torrent = pop;
	if ($torrent =~ /\.torrent/) {
		$torrent = substr($torrent, 0, -8);
	}
	return $torrent;
}

#------------------------------------------------------------------------------#
# Sub: stack                                                                   #
# Arguments: integer, array ref                                                #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub stack {
	my $index = shift;
	my @array = @{shift};

	if ($index) {
		my @stack;
		for (my $i = 0; $i < $index; $i++) {
			push(@stack, (shift @array));
		}
		shift @array;
		for (my $i = 0; $i < $index; $i++) {
			push(@array, (shift@stack));
		}
		$index--;
	} else {
		shift @array;
	}
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
