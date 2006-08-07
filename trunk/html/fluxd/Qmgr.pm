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
		#foreach my $user (@users) {
		#	$user{"queue"} = ();
		#	$user{"running"} = ();
		#}
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

	## queue-loop
	my $queueIdx = 0;
	my $notDoneProcessingQueue = 1;
	my $startTry = 0;
	while ($notDoneProcessingQueue) {

		# update running torrents
		UpdateRunningTorrents();

		# process queue
		my $jobcountq = Queue();
		$notDoneProcessingQueue = 1;
		if ($jobcountq > 0) { # we have queued jobs


	# major re-write!!!#
			# next job
			my $nextTorrent = $QmgrVars{'queue'}[$queueIdx];
			my $nextUser = $QmgrVars{'jobs'}{'queued'}{$nextTorrent};

			# check if this queue-entry exists in running-jobs. dont start what is
			# running. this may be after a restart or torrent was started outside.
			if (exists $QmgrVars{'jobs'}{'running'}{$nextTorrent}) { # torrent already running
				# remove job from queue
				if ($QmgrVars{'LOGLEVEL'} > 1) {
					WriteLog("MAIN : removing already running job from queue : ".$nextTorrent." (".$nextUser.")");
				}
				if ($queueIdx > 0) { # not first entry, stack-action
					my @stack;
					for (my $i = 0; $i < $queueIdx; $i++) {
						push(@stack,(shift @{$QmgrVars{'queue'}}));
					}
					shift @{$QmgrVars{'queue'}};
					for (my $i = 0; $i < $queueIdx; $i++) {
						push(@{$QmgrVars{'queue'}}, (shift @stack));
					}
					$queueIdx--;
				} else { # first entry, just shift
					shift @{$QmgrVars{'queue'}};
				}
				# remove job from jobs
				if ($QmgrVars{'LOGLEVEL'} > 1) {
					WriteLog("MAIN : removing already running job from jobs queued : ".$nextTorrent." (".$nextUser.")");
				}
				delete($QmgrVars{'jobs'}{'queued'}{$nextTorrent});
				#
				if ($queueIdx < (countQueue()-1)) { # there is a next entry
					if ($QmgrVars{'LOGLEVEL'} > 2) {
						WriteLog("MAIN : next queue-entry");
					}
					$queueIdx++;
				} else { # no more in queue
					if ($QmgrVars{'LOGLEVEL'} > 2) {
						WriteLog("MAIN : last queue-entry");
					}
					$notDoneProcessingQueue = 0;
				}
			} else {

				my @jobAry = (keys %{$QmgrVars{'jobs'}{'running'}});
				my $jobcount = scalar(@jobAry);

				# lets see if max limit applies
				if ($jobcount < $QmgrVars{'MAX_TORRENTS_SYS'}) { # max limit does not apply
					# lets see if per user limit applies
					my $userCtr = 0;
					foreach my $anJob (@jobAry) {
						if ($QmgrVars{'jobs'}{'running'}{$anJob} eq $nextUser) {
							$userCtr++;
						}
					}
					sleep 1;
					if ($userCtr < $QmgrVars{'MAX_TORRENTS_USR'}) { # user limit does not apply
						# startup the thing
						WriteLog("MAIN : starting torrent : ".$nextTorrent." (".$nextUser.")");
						if (StartTorrent($nextTorrent) == 1) { # start torrent succeeded

							# reset start-counter-var
							$startTry = 0;

							# remove job from queue
							if ($QmgrVars{'LOGLEVEL'} > 1) {
								WriteLog("MAIN : removing job from queue : ".$nextTorrent." (".$nextUser.")");
							}
							if ($queueIdx > 0) { # not first entry, stack-action
								my @stack;
								for (my $i = 0; $i < $queueIdx; $i++) {
									push(@stack,(shift @{$QmgrVars{'queue'}}));
								}
								shift @{$QmgrVars{'queue'}};
								for (my $i = 0; $i < $queueIdx; $i++) {
									push(@{$QmgrVars{'queue'}}, (shift @stack));
								}
								$queueIdx--;
							} else { # first entry, just shift
								shift @{$QmgrVars{'queue'}};
							}

							# remove job from jobs
							if ($QmgrVars{'LOGLEVEL'} > 1) {
								WriteLog("MAIN : removing job from jobs queued : ".$nextTorrent." (".$nextUser.")");
							}
							delete($QmgrVars{'jobs'}{'queued'}{$nextTorrent});

							# add job to jobs running (not nec. is don in-loop anyway)
							if ($QmgrVars{'LOGLEVEL'} > 1) {
								WriteLog("MAIN : adding job to jobs running : ".$nextTorrent." (".$nextUser.")");
							}
							sleep 1;
							$QmgrVars{'jobs'}{'running'}{$nextTorrent} = $nextUser;

							# done with queue ?
							$jobcountq = scalar(@{$QmgrVars{'queue'}});
							if ($jobcountq > 0) { # more jobs in queue
								$queueIdx = 0;
								# dont hurry too much when processing queue
								sleep 1;
							} else { # nothing more in queue
								$notDoneProcessingQueue = 0;
							}
						} else { # start torrent failed
							# already tried max-times to start this thing ?
							if ($startTry == $QmgrVars{'MAX_START_TRIES'}) {
								$startTry = 0;
								# TODO : give an option to remove bogus torrents
								if ($queueIdx < (countQueue()-1)) { # there is a next entry
									if ($QmgrVars{'LOGLEVEL'} > 1) {
										WriteLog("MAIN : $QmgrVars{'MAX_START_TRIES'} errors when starting, skipping job : ".$nextTorrent." (".$nextUser.")");
									}
								$queueIdx++;
								} else { # no more in queue
									if ($QmgrVars{'LOGLEVEL'} > 1) {
										WriteLog("MAIN : $QmgrVars{'MAX_START_TRIES'} errors when starting, skipping job : ".$nextTorrent." (".$nextUser.")");
									}
									$notDoneProcessingQueue = 0;
								}
							} else {
								$startTry++;
								sleep $QmgrVars{'START_TRIES_SLEEP'};
							}
						}

					} else { # user-limit for this user applies, check next queue-entry if one exists
						if ($queueIdx < (Queue()-1)) { # there is a next entry
							if ($QmgrVars{'LOGLEVEL'} > 1) {
								WriteLog("MAIN : user limit applies, skipping job : ".$nextTorrent." (".$nextUser.") (next queue-entry)");
							}
							$queueIdx++;
						} else { # no more in queue
							if ($QmgrVars{'LOGLEVEL'} > 1) {
								WriteLog("MAIN : user limit applies, skipping job : ".$nextTorrent." (".$nextUser.") (last queue-entry)");
							}
							$notDoneProcessingQueue = 0;
						}
					}
				} else { # max limit does apply
					if ($QmgrVars{'LOGLEVEL'} > 1) {
						WriteLog("MAIN : max limit applies, skipping job : ".$nextTorrent." (".$nextUser.")");
					}
					$notDoneProcessingQueue = 0;
				}

			} # else already runnin

		} else { # no queued jobs
			if ($QmgrVars{'LOGLEVEL'} > 2) {
				WriteLog("MAIN : empty queue... sleeping...");
			}
			$notDoneProcessingQueue = 0;
		}
	} # queue-while-loop

	$QmgrVars{'globals'}{'main'} += 1;
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
# Sub: printUsage                                                             #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub printUsage {

my $PROG = "Qmgr";
my $DAEMON = "Qmgrd";
my $EXTENSION = "pl";

	print <<"USAGE";

$DAEMON.$EXTENSION Revision $REVISION

Usage:
  To start the Qmgr Daemon:
	$DAEMON.$EXTENSION </path/to/downloads> <Max torrents> <Max user torrents>

  To run the client script:
	$PROG.$EXTENSION <stop|status|jobs|queue|list|add|remove|worker>

	<stop>		: Stop the Daemon
	<status>	: Print Status information
	<jobs>		: Print the total number of jobs
	<queue>		: Print the number of queued jobs
	<list>		: List queued jobs
	<add>		: Add a torrent to the queue, required args -
				torrent id
				user name to run torent as
	<remove>	: Remove a torrent from the queue, required args -
				torrent id
	<worker>	: returns a boolean to determine if the daemon script is
			  still running as normal (true) or is trying to shut down (false)
	<set>		: Allows you to set certain config variables witout restarting the
			  daemon. Required args -
				Key (variable name to change)
				[Value] Optional - value to set variable to. If left out
					will just return the current value.

	Anything else given as an initial arguement will dislpay his help page

Note: Both scripts can take optional arguments for host and port to bind to (in the case
      of the daemon script) or connect to (for the client script). However, these arguments
      must be the last two arguments passed in, meaning that if you wanted to start a Qmgr
      at 192.168.2.250:9999, you'd issue the command
	$DAEMON.$EXTENSION /path/to/downloads int int 192.168.2.250 9999
      if you wanted to add add foo.toorent to that server you'd use
	$PROG.$EXTENSION add foo.toorent user 192.168.2.250 9999
      However, neither of these are working as of yet. The default is to bind to
      127.0.0.1:2606. You can change the default by editing the new{ ... } sub in Qmgr.pm
      to change the daemon's binding. Remember to also edit the $PROG.$EXTENSION script to
      change where it looks for connections

Examples:
$DAEMON.$EXTENSION /usr/local/torrent 5 2

$PROG.$EXTENSION  stop
$PROG.$EXTENSION  status
$PROG.$EXTENSION  jobs
$PROG.$EXTENSION  queue
$PROG.$EXTENSION  list
$PROG.$EXTENSION  add foo.torrent username
$PROG.$EXTENSION  remove foo.torrent
$PROG.$EXTENSION  worker
$PROG.$EXTENSION  set MAX_TORRENTS_USR 5
$PROG.$EXTENSION  set LOGLEVEL 1

USAGE
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
		my $torrent = shift;
		my $username = shift;
		push($users[$names{$username}]{queue}, $torrent);
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
	foreach my $user (@users) {
		foreach my $torrent (@{
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
	return $return
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
	return scalar((keys %{$QmgrVars{'jobs'}{'queued'}}));
}

#-----------------------------------------------------------------------------#
# Sub: Running                                                                #
# Arguments: Null                                                             #
# Returns: Number of running jobs                                             #
#-----------------------------------------------------------------------------#
sub Running{
	return scalar((keys %{$QmgrVars{'jobs'}{'running'}}));
}

#-----------------------------------------------------------------------------#
# Sub: List                                                                   #
# Arguments: Null                                                             #
# Returns: List of queued torrents                                            #
#-----------------------------------------------------------------------------#
sub List {
	my $return = "";
	# return list
	foreach my $queueEntry (@{$QmgrVars{'queue'}}) {
		$return .= $queueEntry.".torrent\n";
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
# Sub: StartTorrent                                                           #
# Arguments: torrent                                                          #
# Returns: Bool on if the torrent started                                     #
#-----------------------------------------------------------------------------#
sub StartTorrent {
	# Start a torrent
	my $torrent = shift;
	if (!(defined $torrent)) {
		return 0;
	}
	my $StartCommand = $QmgrVars{'PATH_PHP'}." ".$QmgrVars{'FLUXCLI'}." start ".$torrent.".torrent &> /dev/null";
	if ($QmgrVars{'LOGLEVEL'} > 2) {
		WriteLog("StartTorrent : start-command : ".$StartCommand);
	}
	eval { system($StartCommand); };
	if ($@) {
		return 0;
	}
	$QmgrVars{'globals'}{'started'} += 1;
	return 1;
}

#-----------------------------------------------------------------------------#
# Sub: processRequest                                                         #
# Arguments: Command, [torrent, [user] ] [host, port]                         #
# Returns: Return value from called sub                                       #
#-----------------------------------------------------------------------------#
sub processRequest {
	# verify that arguments look right

	my $temp = shift;
	print $temp."\n";

	split(/ /, $temp);

	$temp = shift;
	if (!(defined $temp)) {
		printUsage();
		return;
	}
	my $Command = $temp;

	my $torrent = shift;
	my $user = shift;
	my $host = shift || 'localhost';
	my $port = shift || 2606;

	# Should be ok, let's get to it.

	my $return = "";

	SWITCH: {
		$_ = $Command;

		/^stop$/ && do {
			$return = Stop();
			last SWITCH;
		};
		/^status$/ && do {
			$return = status();
                        last SWITCH;
		};
		/^jobs$/ && do {
			$return = Jobs();
                        last SWITCH;
		};
		/^queue$/ && do {
			$return = Queue();
                        last SWITCH;
		};
		/^list$/ && do {
			$return = List();
                        last SWITCH;
		};
		/^add$/ && do {
			$return = Add($torrent, $user);
                        last SWITCH;
		};
		/^remove$/ && do {
			$return = Remove($torrent);
                        last SWITCH;
		};
		/^set$/ && do {
			$return = set($torrent, $user);
			last SWITCH;
		};
		/^move-up$/ && do {
			$return = MoveUp($torrent);
			last SWITCH;
		};
		/^move-down$/ && do {
			$return = MoveDown($torrent);
			last SWITCH;
		};
		/^move-top$/ && do {
			$return = MoveTop($torrent);
			last SWITCH;
		};
		/^move-bottom$/ && do {
			$return = MoveBottom($torrent);
			last SWITCH;
		};
		/^worker$/ && do {
			$return = $QmgrVars{'globals'}{'worker_running'};
                        last SWITCH;
		};
	}
	return $return."\n";
}

#-----------------------------------------------------------------------------#
# Sub: initPaths                                                              #
# Agruments: Path                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub initPaths {
	my $pathVar = shift;
	if (!((substr $pathVar, -1) eq "/")) {
		$pathVar .= "/";
	}
	$QmgrVars{'PATH_TORRENT_DIR'} = $pathVar.$QmgrVars{'PATH_TORRENT_DIR'};
	$QmgrVars{'PATH_DATA_DIR'} = $pathVar.$QmgrVars{'PATH_DATA_DIR'};
	$QmgrVars{'PATH_QUEUE_FILE'} = $QmgrVars{'PATH_DATA_DIR'}.$QmgrVars{'PATH_QUEUE_FILE'};
	$QmgrVars{'PATH_LOG_FILE'} = $QmgrVars{'PATH_DATA_DIR'}.$QmgrVars{'PATH_LOG_FILE'};
	$QmgrVars{'PATH_PID_FILE'} = $QmgrVars{'PATH_DATA_DIR'}.$QmgrVars{'PATH_PID_FILE'};
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $QmgrVars{'PATH_DATA_DIR'}) {
		mkdir($QmgrVars{'PATH_DATA_DIR'},0700);
	}
	WriteLog("initPaths : Paths initialized");
}

#-----------------------------------------------------------------------------#
# Sub: WriteLog                                                               #
# Arguments: String to write to log                                           #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub WriteLog {
	my $outString = shift;
	my $time_human = localtime;
	open(LOGFILE,">>$QmgrVars{'PATH_LOG_FILE'}");
        print LOGFILE $time_human." - ".$outString."\n";
        close(LOGFILE);
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
# Sub: GotSigInt                                                              #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub GotSigInt{
	WriteLog("Got SigInt - Shutting down");
	Stop();
}

#-----------------------------------------------------------------------------#
# Sub: GotSigQuit                                                             #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub GotSigQuit {
	WriteLog("Got SigQuit - Shutting down");
	Stop()
}

#-----------------------------------------------------------------------------#
# Sub: WritePid                                                               #
# Parameters: Int with pid                                                    #
# Return: Null                                                                #
#-----------------------------------------------------------------------------#
sub WritePid {
	my $pid = shift;
	if (!(defined $pid)) {
		$pid = $$;
	}
	open(PIDFILE,">$QmgrVars{'PATH_PID_FILE'}");
	print PIDFILE $pid."\n";
	close(PIDFILE);
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

################################################################################
# make perl happy                                                              #
################################################################################
1;
