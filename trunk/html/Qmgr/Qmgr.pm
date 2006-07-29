# $Id$
package Qmgr;
use strict;
no strict "refs";
use IO::Socket;
use IO::Select;

# Hash of internal variables
our %QmgrVars = (
	PATH_TORRENT_DIR	=> ".torrents/",
	PATH_DATA_DIR		=> ".Qmgr/",
	PATH_QUEUE_FILE		=> "Qmgr.queue",
	PATH_PID_FILE		=> "Qmgr.pid",
	PATH_LOG_FILE		=> "Qmgr.log",
	PATH_PHP		=> "/usr/bin/php",
	MAX_TORRENTS_USR	=> 2,
	MAX_TORRENTS_SYS	=> 5,
	MAX_START_TRIES		=> 5,
	START_TRIES_SLEEP	=> 10,
	SLEEP_MIN		=> 5,
	SLEEP_MAX		=> 20,
	sleepDelta		=> 0,
	FLUXCLI			=> "fluxcli.php",
	LOGLEVEL		=> 0,
	host			=> "localhost",
	port			=> 3150,
	queue			=> (),
	globals			=> {},
	jobs			=> { 'queued' => {},  'running' => {} },
	time			=> undef,
	localtime		=> undef,
	listen			=> undef,
	select			=> undef
);

# revision in a var
our $REVISION = do { my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

#-----------------------------------------------------------------------------#
# Sub: Set                                                                    #
# Arguments: $Key, [$Value]                                                   #
# Returns: info string on $QmgrVars{$Key}'s value                             #
#-----------------------------------------------------------------------------#
sub Set {
	my $Key = shift;
	my $Return = "";
	if (defined $QmgrVars{$Key}) {
		my $Value = shift;
		if ( (defined $Value) and ($Value ne "") ) {
			$Return .= "Changing config - $Key to $Value";
			if ($QmgrVars{'LOGLEVEL'} > 0) {
				WriteLog("Set : ".$Return);
			}
			$QmgrVars{$Key} = $Value;
		} else {
			$Return .= "$Key is currently set to $QmgrVars{$Key}";
			if ($QmgrVars{'LOGLEVEL'} > 0) {
				WriteLog("Set : ".$Return);
			}
		}
	} else {
		if ($QmgrVars{'LOGLEVEL'} > 0) {
			WriteLog("Set : got bogus variable name : ".$Key);
		}
		$Return .= "$Key is not a valid variable name I can configure";
	}
	return $Return;
}

#-----------------------------------------------------------------------------#
# Constructor Method                                                          #
# Arguments: path, MAX_TORRENTS_SYS, MAX_TORRENTS_USR, LOGLEVEL, php-path, [host], [port] #
# Returns: Object                                                             #
#-----------------------------------------------------------------------------#
sub new {

	my $objclass = shift;
	# Initialize the server
	# check arguments

	my $Temp = shift;
	if (!(defined $Temp)) {
		PrintUsage();
		exit;
	}
	InitPaths($Temp);

	WriteLog("New : Initializing Qmgr");

	$Temp = shift;
	if (!(defined $Temp)) {
		PrintUsage();
		exit;
	}
	$QmgrVars{'MAX_TORRENTS_SYS'} = $Temp;

	$Temp = shift;
	if (!(defined $Temp)) {
		PrintUsage();
		exit;
	}
	$QmgrVars{'MAX_TORRENTS_USR'} = $Temp;

	$Temp = shift;
	if (!(defined $Temp)) {
		PrintUsage();
		exit;
	}
	$QmgrVars{'LOGLEVEL'} = $Temp;

	$Temp = shift;
	if(!(defined $Temp)) {
		PrintUsage();
		exit;
	}
	$QmgrVars{'PATH_PHP'} = $Temp;

	$QmgrVars{'host'} = shift if @_;
	$QmgrVars{'port'} = shift if @_;

	# Check if we're already running
	# Do this in the UI

	# Create some time vars
	$QmgrVars{'time'} = time();
	$QmgrVars{'localtime'} = localtime();

	# Create and start the log file
	WriteLog("New : Starting : Main");

	# Write the pid file
	WriteLog("New : writing parent pid into ".$QmgrVars{'PATH_PID_FILE'});
	WritePid();

	# Set up our signal handlers
	$SIG{INT} = \&GotSigInt;
	$SIG{QUIT} = \&GotSigQuit;

	# Initialize our globals hash
	$QmgrVars{'globals'}{'main'} = 0;
	$QmgrVars{'globals'}{'started'} = 0;
	$QmgrVars{'globals'}{'worker_running'} = 1;
	$QmgrVars{'globals'}{'client_running'} = 0;

	#Initialize the queue
	if (-f $QmgrVars{'PATH_QUEUE_FILE'}) {
		WriteLog("New : Loading Queue-file");
		# actually load the queue
		LoadQueue();
	} else {
		WriteLog("New : Creating empty queue");
		@{$QmgrVars{'queue'}} = ();
	}

	# Initialize listen socket
	$QmgrVars{'listen'} = IO::Socket::INET->new(
		Listen    => 1,
		Proto     => 'tcp',
		LocalAddr => $QmgrVars{'host'},
		LocalPort => $QmgrVars{'port'},
		Reuse     => 1 ) or die "Can't Create Listening Socket: $!";
	$QmgrVars{'select'} = new IO::Select( $QmgrVars{'listen'} );

	my $self = {};
	bless ($self, $objclass);
	return $self;
}

#-------------------------------------------------------------------------------#
# Sub: ProcessQueue                                                             #
# Arguments: Null                                                               #
# Returns: Null                                                                 #
#-------------------------------------------------------------------------------#
sub ProcessQueue {
	# hold cycle-start-time
	my $timeStart = time;

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

	# juhu it is idle-time ;)
	my $timeDelta = time() - $timeStart;
	if ($timeDelta > $QmgrVars{'SLEEP_MAX'}) { # long cycle this was... use min-time
		$QmgrVars{'sleepDelta'} = $QmgrVars{'SLEEP_MIN'};
	} elsif ($timeDelta < $QmgrVars{'SLEEP_MAX'}) {
		$QmgrVars{'sleepDelta'} = $QmgrVars{'SLEEP_MAX'} - $timeDelta;
		if ($QmgrVars{'sleepDelta'} < $QmgrVars{'SLEEP_MIN'}) { # sleep-delta too short, use min-time
			$QmgrVars{'sleepDelta'} = $QmgrVars{'SLEEP_MIN'};
		}
	} else { # lol
		$QmgrVars{'sleepDelta'} = $QmgrVars{'SLEEP_MIN'};
	}

	## increment number of cycles
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
# Sub: PrintUsage                                                             #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub PrintUsage {

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
	open(QUEUEFILE,"< $QmgrVars{'PATH_QUEUE_FILE'}");
	while (<QUEUEFILE>) {
		chomp;
		push(@{$QmgrVars{'queue'}}, $_);
	}
	close QUEUEFILE;
	# fill job-hash
	foreach my $torrent (@{$QmgrVars{'queue'}}) {
		my $user = getTorrentOwner($torrent);
		if (!(defined $user)) {
			$QmgrVars{'jobs'}{'queued'}{$torrent} = "unknown";
		} else {
			if (! exists $QmgrVars{'jobs'}{'queued'}{$torrent}) {
				$QmgrVars{'jobs'}{'queued'}{$torrent} = $user;
			}
		}
  	}
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
	open(QUEUEFILE,">$QmgrVars{'PATH_QUEUE_FILE'}");
	# queued torrents
	foreach my $queueEntry (@{$QmgrVars{'queue'}}) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#-----------------------------------------------------------------------------#
# Sub: Status                                                                 #
# Arguments: Null                                                             #
# Returns: Status string                                                      #
#-----------------------------------------------------------------------------#
sub Status {
	my $return = "Status info\n";
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
		PrintUsage();
		return;
	}
	my $torrent = $temp;
	$torrent = StripTorrentName($torrent);

	$temp = shift;
	if (!(defined $temp)) {
		PrintUsage();
		return;
	}
	my $user = $temp;

	# Looks good, add it to the queue.
	if ((! exists $QmgrVars{'jobs'}{'queued'}{$torrent}) && (! exists $QmgrVars{'jobs'}{'running'}{$torrent})) {
		$AddIt = 1;
		WriteLog("Main : Adding job to queue : ".$torrent." (".$user.")");
		$QmgrVars{'jobs'}{'queued'}{$torrent} = $user;
	} else {
		if ($QmgrVars{'LOGLEVEL'} > 1) {
			WriteLog("Main : Job already exists : ".$torrent." (".$user.")");
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
		PrintUsage();
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
# Sub: CheckConnections                                                       #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub CheckConnections {
	SOCKET: while ( my @ready = $QmgrVars{'select'}->can_read($QmgrVars{'sleepDelta'}) ) {
		foreach my $socket (@ready) {
			if ($socket == $QmgrVars{'listen'}) {
				# Create a new socket
				my $new = $QmgrVars{'listen'}->accept;
				$QmgrVars{'select'}->add($new);
			} else {
				# Process the socket
				my $buf = <$socket>;
				if($buf) {
					my $return = ProcessRequest($buf);
					send($socket, $return, 0);
					$QmgrVars{'select'}->remove($socket);
					$socket->close;
					last SOCKET;
				} else {
					# Client has closed connection
					$QmgrVars{'select'}->remove($socket);
					$socket->close;
				}
			}
		}
	}
}

#-----------------------------------------------------------------------------#
# Sub: ProcessRequest                                                         #
# Arguments: Command, [torrent, [user] ] [host, port]                         #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub ProcessRequest {
	# verify that arguments look right

	my $temp = shift;
	print $temp."\n";

	split(/ /, $temp);

	$temp = shift;
	if (!(defined $temp)) {
		PrintUsage();
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
			$return = Status();
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
			$return = Set($torrent, $user);
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
# Sub: InitPaths                                                              #
# Agruments: Path                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub InitPaths {
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
	WriteLog("InitPaths : Paths initialized");
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

#-----------------------------------------------------------------------------#
# Sub: Stop                                                                   #
# Arguments: Null                                                             #
# Returns: Null                                                               #
#-----------------------------------------------------------------------------#
sub Stop {
	# Stop the server

	# Set the global var, so everyone will know we're shutting down
	$QmgrVars{'globals'}{'worker_running'} = 0;

	# Delete Pid file
	unlink($QmgrVars{'PATH_PID_FILE'});
	WriteLog("Shutdown : deleted Pid file");

	# save Queue
	if ( Queue() ) {
		SaveQueue();
		WriteLog("Shutdown : saved current queue");
	}
	exit;
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
		PrintUsage();
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
		PrintUsage();
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
		PrintUsage();
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
		PrintUsage();
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
		PrintUsage();
		return;
	}

	@_ = split(m!/!, $torrent);

	$torrent = pop;
	if ($torrent =~ /\.torrent/) {
		$torrent = substr($torrent, 0, -8);
	}
	return $torrent;
}
