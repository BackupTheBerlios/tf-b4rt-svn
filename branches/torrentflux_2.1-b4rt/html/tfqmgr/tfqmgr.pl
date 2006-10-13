#!/usr/bin/perl
################################################################################
# $Id$
# $Revision$
# $Date$
# $Author$
#------------------------------------------------------------------------------#
# tfqmgr.pl                                                                    #
#------------------------------------------------------------------------------#
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
use strict;
################################################################################

#-------------------------------------------------------------------------------
# Config-Vars
#-------------------------------------------------------------------------------

# loglevel # (0|1|2)
my $LOGLEVEL = 0;

# sys-bins
my %BINS = (
	'mkfifo'  => 'mkfifo',
	'whoami'  => 'whoami',
	'ipcs'    => 'ipcs',
	'ipcrm'   => 'ipcrm'
);

# fluxcli-name
my $BIN_FLUXCLI = "fluxcli.php";

# sleep-times worker
my $SLEEP_MIN = 5;
my $SLEEP_MAX = 20;

# limits for bogus torrents
my $MAX_START_TRIES = 5;
my $START_TRIES_SLEEP = 10;

#-------------------------------------------------------------------------------
# Internal Vars
#-------------------------------------------------------------------------------
my $BIN_PHP = "/usr/bin/php";
my $PATH_TORRENTDIR = ".torrents";
my $PATH_DATADIR = ".tfqmgr";
my $PATH_COMMAND_FIFO="COMMAND";
my $PATH_TRANSPORT_FIFO="TRANSPORT";
my $PATH_QUEUE_FILE="tfqmgr.queue";
my $LOGFILE = "tfqmgr.log";
my $PIDFILE = "tfqmgr.pid";
my ( $REVISION, $DIR, $PROG, $EXTENSION );
my ( $parentPid, $childReader, $time_human, $time_unix );
my ( %jobs, @queue, %globals );
my ( $handleJobs, $handleQueue, $handleGlobals );

#-------------------------------------------------------------------------------
# "Main"
#-------------------------------------------------------------------------------

# initialize
initialize();

# main-"switch"
SWITCH: {
	$_ = shift;
	/^start$/ && do {
		# load modules + start
		if (loadModules() == 1) {
			startDaemon(shift,shift,shift,shift,shift);
		}
	};
	/^stop$/ && do {
		stopDaemon(shift);
		exit;
	};
	/^status$/ && do {
		processExternalCommand("status",shift);
		exit;
	};
	/^count-jobs$/ && do {
		processExternalCommand("count-jobs",shift);
		exit;
	};
	/^count-queue$/ && do {
		processExternalCommand("count-queue",shift);
		exit;
	};
	/^list-queue$/ && do {
		processExternalCommand("list-queue",shift);
		exit;
	};
	/^set$/ && do {
		setConfig(shift,shift,shift);
		exit;
	};
	/^check$/ && do {
		checkRequirements();
		exit;
	};
	/^cleanup$/ && do {
		cleanup(shift);
		exit;
	};
	/.*(help|-h).*/ && do {
		printUsage();
		exit;
	};
	printUsage();
	exit;
}

################################################################################
# Subs
################################################################################

#-------------------------------------------------------------------------------
# Sub: startDaemon
# Parameters:	$pathArg,$MAX_TORRENTS,$MAX_TORRENTS_PER_USER,$pathToPHP
# Return:		-
#-------------------------------------------------------------------------------
sub startDaemon {

	# check args + init vars
	my $argTemp = shift;
	if (!(defined $argTemp)) {
		printUsage();
		exit;
	}
	initPaths($argTemp);
	$argTemp = shift;
	if (!(defined $argTemp)) {
		printUsage();
		exit;
	}
	my $MAX_TORRENTS = $argTemp;
	$argTemp = shift;
	if (!(defined $argTemp)) {
		printUsage();
		exit;
	}
	my $MAX_TORRENTS_PER_USER = $argTemp;
	$argTemp = shift;
	if (!(defined $argTemp)) {
		printUsage();
		exit;
	}
	$BIN_PHP = $argTemp;

	# sanity-checks to prevent double-start
	if (-f $PIDFILE) {
		print "pid-file exists. tfqmgr already up ? \n";
		exit;
	}
	if (-p $PATH_COMMAND_FIFO) {
		print "command-fifo present. tfqmgr already up ? \n";
		exit;
	}
	if (-p $PATH_TRANSPORT_FIFO) {
		print "transport-fifo present. tfqmgr already up ? \n";
		exit;
	}

	# init start-times
	$time_human = localtime;
	$time_unix = time;

	# lets go
	doLog("START : MAIN");
	print "Starting tfqmgr...\n";

	# write out pid-file
	doLog("MAIN : writing pid ".$parentPid." into pid-file ".$PIDFILE);
	writePidFile();

	# init var-handles
	$handleJobs = tie %jobs, 'IPC::Shareable', undef, { destroy => 1};
	$handleQueue = tie @queue, 'IPC::Shareable', undef, { destroy => 1};
	$handleGlobals = tie %globals, 'IPC::Shareable', undef, { destroy => 1};

	# sigs
	# INT
	$SIG{INT} = \&gotSigInt;
	# QUIT
	$SIG{QUIT} = \&gotSigQuit;

	# init globals
	$handleGlobals->shlock();
	$globals{"main"} = 0;
	$globals{"started"} = 0;
	$globals{"worker_running"} = 1;
	$globals{"reader_running"} = 1;
	$globals{"max_torrents"} = $MAX_TORRENTS;
	$globals{"max_torrents_per_user"} = $MAX_TORRENTS_PER_USER;
	$handleGlobals->shunlock();

	# init queue
	if (-f $PATH_QUEUE_FILE) {
		doLog("MAIN : loading queue-file");
		# load queue file
		daemonLoadQueue();
	} else {
		doLog("MAIN : initializing fresh queue");
		# fresh queue
		$handleQueue->shlock();
		@queue = qw();
		$handleQueue->shunlock();
	}

	# transport-fifo
	doLog("MAIN : creating transport-fifo $PATH_TRANSPORT_FIFO");
	system($BINS{'mkfifo'},$PATH_TRANSPORT_FIFO);

	## fork here ##

	# Reader-child
	unless ($childReader = fork) {
		die "Error on fork: $!" unless defined $childReader;
		childReaderMain();
		exit;
	}

	# parent
	parentMain();
}

#-------------------------------------------------------------------------------
# Sub: stopDaemon
# Parameters:	$pathArg
# Return:		-
#-------------------------------------------------------------------------------
sub stopDaemon {
	my $pathArg = shift;
	if (!(defined $pathArg)) {
		printUsage();
		exit;
	}
	initPaths($pathArg);
	if (-p $PATH_COMMAND_FIFO) {
		print "Sending tfqmgr stop-command...";
		my $stopResult = doDaemonCall("stop",0);
		if ($stopResult == 1) {
			print "done.\n";
			# turn on autoflush
			$| = 1;
			print "tfqmgr shutting down";
			while (-p $PATH_TRANSPORT_FIFO) {
				print ".";
				sleep 1;
			}
			print "done.\n";
		} else {
			print "\nError sending command. check log-file.\n";
		}
	} else {
		print "tfqmgr not running. \n";
	}
	exit;
}

#-------------------------------------------------------------------------------
# Sub: processExternalCommand
# Parameters:	$command
# Return:		-
#-------------------------------------------------------------------------------
sub processExternalCommand {
	my $command = shift;
	my $pathArg = shift;
	if (!(defined $pathArg)) {
		printUsage();
		exit;
	}
	initPaths($pathArg);
	if (-p $PATH_COMMAND_FIFO) {
		my $dCallRes = doDaemonCall($command,1);
		if (defined $dCallRes) {
			print $dCallRes;
		} else {
			print "tfqmgr not running. \n";
		}
	} else {
		print "tfqmgr not running. \n";
	}
	exit;
}

#-------------------------------------------------------------------------------
# Sub: setConfig
# Parameters: pathArg, keyArg, valArg
# Return:		-
#-------------------------------------------------------------------------------
sub setConfig {
	my $pathArg = shift;
	if (!(defined $pathArg)) {
		printUsage();
		exit;
	}
	initPaths($pathArg);
	my $keyArg = shift;
	if (!(defined $keyArg)) {
		printUsage();
		exit;
	}
	my $valArg = shift;
	if (!(defined $valArg)) {
		printUsage();
		exit;
	}
	my $command = "set:".$keyArg.":".$valArg;
	if (-p $PATH_COMMAND_FIFO) {
		open(COMMAND, ">$PATH_COMMAND_FIFO");
		print COMMAND $command."\n";
		close (COMMAND);
	} else {
		print "tfqmgr not running. \n";
	}
	exit;
}

#-------------------------------------------------------------------------------
# Sub: parentMain
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub parentMain {

	# main-loop
	while ($globals{"worker_running"}) {

		# hold cycle-start-time
		my $timeStart = time;

		## queue-loop
		my $queueIdx = 0;
		my $notDoneProcessingQueue = 1;
		my $startTry = 0;
		while ($notDoneProcessingQueue) {

			# update running torrents
			daemonUpdateRunningTorrents();

			# process queue
			my $jobcountq = scalar(@queue);
			$notDoneProcessingQueue = 1;
			if ($jobcountq > 0) { # we have queued jobs

				# next job
				my $nextTorrent = $queue[$queueIdx];
				my $nextUser = $jobs{"queued"}{$nextTorrent};

				# check if this queue-entry exists in running-jobs. dont start what is
				# running. this may be after a restart or torrent was started outside.
				if (exists $jobs{"running"}{$nextTorrent}) { # torrent already running
					# remove job from queue
					if ($LOGLEVEL > 0) {
						doLog("MAIN : removing already running job from queue : ".$nextTorrent." (".$nextUser.")");
					}
					$handleQueue->shlock();
					if ($queueIdx > 0) { # not first entry, stack-action
						my @stack;
						for (my $i = 0; $i < $queueIdx; $i++) {
							push(@stack,(shift @queue));
						}
						shift @queue;
						for (my $i = 0; $i < $queueIdx; $i++) {
							push(@queue, (shift @stack));
						}
						$queueIdx--;
					} else { # first entry, just shift
						shift @queue;
					}
					$handleQueue->shunlock();
					# remove job from jobs
					if ($LOGLEVEL > 0) {
						doLog("MAIN : removing already running job from jobs queued : ".$nextTorrent." (".$nextUser.")");
					}
					$handleJobs->shlock();
					delete($jobs{"queued"}{$nextTorrent});
					$handleJobs->shunlock();
					#
					if ($queueIdx < (countQueue()-1)) { # there is a next entry
						if ($LOGLEVEL > 1) {
							doLog("MAIN : next queue-entry");
						}
						$queueIdx++;
					} else { # no more in queue
						if ($LOGLEVEL > 1) {
							doLog("MAIN : last queue-entry");
						}
						$notDoneProcessingQueue = 0;
					}
				} else { # torrent already running

					my @jobAry = (keys %{$jobs{"running"}});
					my $jobcount = scalar(@jobAry);

					# lets see if max limit applies
					if ($jobcount < $globals{"max_torrents"}) { # max limit does not apply
						# lets see if per user limit applies
						my $userCtr = 0;
						foreach my $anJob (@jobAry) {
							if ($jobs{"running"}{$anJob} eq $nextUser) {
								$userCtr++;
							}
						}

						if ($userCtr < $globals{"max_torrents_per_user"}) { # user limit does not apply
							# startup the thing
							doLog("MAIN : starting torrent : ".$nextTorrent." (".$nextUser.")");
							if (daemonStartTorrent($nextTorrent) == 1) { # start torrent succeeded

                  				# reset start-counter-var
                  				$startTry = 0;

								# remove job from queue
								if ($LOGLEVEL > 0) {
									doLog("MAIN : removing job from queue : ".$nextTorrent." (".$nextUser.")");
								}
								$handleQueue->shlock();
								if ($queueIdx > 0) { # not first entry, stack-action
									my @stack;
									for (my $i = 0; $i < $queueIdx; $i++) {
										push(@stack,(shift @queue));
									}
									shift @queue;
									for (my $i = 0; $i < $queueIdx; $i++) {
										push(@queue, (shift @stack));
									}
									$queueIdx--;
								} else { # first entry, just shift
									shift @queue;
								}
								$handleQueue->shunlock();

								# remove job from jobs
								if ($LOGLEVEL > 0) {
									doLog("MAIN : removing job from jobs queued : ".$nextTorrent." (".$nextUser.")");
								}
								$handleJobs->shlock();
								delete($jobs{"queued"}{$nextTorrent});
								$handleJobs->shunlock();

								# add job to jobs running (not nec. is don in-loop anyway)
								if ($LOGLEVEL > 0) {
									doLog("MAIN : adding job to jobs running : ".$nextTorrent." (".$nextUser.")");
								}
								$handleJobs->shlock();
								$jobs{"running"}{$nextTorrent} = $nextUser;
								$handleJobs->shunlock();

								# done with queue ?
								$jobcountq = scalar(@queue);
								if ($jobcountq > 0) { # more jobs in queue
									$queueIdx = 0;
								# dont hurry too much when processing queue
								sleep 1;
								} else { # nothing more in queue
									$notDoneProcessingQueue = 0;
								}
								
							} else { # start torrent failed
							
								# already tried max-times to start this thing ?
								if ($startTry == $MAX_START_TRIES) {
									$startTry = 0;
									# TODO : give an option to remove bogus torrents
									if ($queueIdx < (countQueue()-1)) { # there is a next entry
										if ($LOGLEVEL > 0) {
											doLog("MAIN : $MAX_START_TRIES errors when starting, skipping job : ".$nextTorrent." (".$nextUser.") (next queue-entry)");
										}
										$queueIdx++;
									} else { # no more in queue
										if ($LOGLEVEL > 0) {
											doLog("MAIN : $MAX_START_TRIES errors when starting, skipping job : ".$nextTorrent." (".$nextUser.") (last queue-entry)");
										}
										$notDoneProcessingQueue = 0;
									}
								} else {
									$startTry++;
									sleep $START_TRIES_SLEEP;
								}

							} # start torrent failed

						} else { # user-limit for this user applies, check next queue-entry if one exists
							if ($queueIdx < (countQueue()-1)) { # there is a next entry
								if ($LOGLEVEL > 0) {
									doLog("MAIN : user limit applies, skipping job : ".$nextTorrent." (".$nextUser.") (next queue-entry)");
								}
								$queueIdx++;
							} else { # no more in queue
								if ($LOGLEVEL > 0) {
									doLog("MAIN : user limit applies, skipping job : ".$nextTorrent." (".$nextUser.") (last queue-entry)");
								}
								$notDoneProcessingQueue = 0;
							}
						}
            
					} else { # max limit does apply
						if ($LOGLEVEL > 0) {
							doLog("MAIN : max limit applies, skipping job : ".$nextTorrent." (".$nextUser.")");
						}
						$notDoneProcessingQueue = 0;
					}
					
				} # else already runnin

			} else { # no queued jobs
				if ($LOGLEVEL > 1) {
					doLog("MAIN : empty queue... sleeping...");
				}
				$notDoneProcessingQueue = 0;
			}
		} # queue-while-loop

		# juhu it is idle-time ;)
		my $timeDelta = time - $timeStart;
		if ($timeDelta > $SLEEP_MAX) { # long cycle this was... use min-time
			sleep $SLEEP_MIN;
		} elsif ($timeDelta < $SLEEP_MAX) {
			my $sleepDelta = $SLEEP_MAX - $timeDelta;
			if ($sleepDelta < $SLEEP_MIN) { # sleep-delta too short, use min-time
				sleep $SLEEP_MIN;
			} else { # use sleep-delta
				sleep $sleepDelta;
			}
		} else { # lol
			sleep $SLEEP_MIN;
		}

		## increment main-loop-count
		$handleGlobals->shlock();
		$globals{"main"} += 1;
		$handleGlobals->shunlock();

	} # main-while-loop

	# we are going down !
	parentStop();
	
} # end sub

#-------------------------------------------------------------------------------
# Sub: childReaderMain
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub childReaderMain {
	doLog("START : CHILD - READER");

	# command-fifo
	doLog("CHILD - READER : creating command-fifo $PATH_COMMAND_FIFO");
	system($BINS{'mkfifo'},$PATH_COMMAND_FIFO);

	# loop
	while ($globals{"reader_running"}) {

		# open command-fifo
		open(COMMAND,"<$PATH_COMMAND_FIFO");

		# read command
		my $command = <COMMAND>;
		next unless defined $command;
		chomp $command;
		if ($LOGLEVEL > 0) {
			doLog("CHILD - READER : read command from fifo : ".$command);
		}

		# process command
		eval { processCommand($command); };

		# close command-fifo
		close(COMMAND);

	} # end while loop

	# we are going down !
	childReaderStop();
	
} # end sub

#-------------------------------------------------------------------------------
# Sub: parentStop
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub parentStop {
	# we are going down
	doLog("STOP - PARENT");
	my $jobcount = countQueue();
	if ($jobcount > 0) {
		doLog("MAIN : jobs queued : ".$jobcount.". writing queue-file...");
		# save queue
		daemonSaveQueue();
	}
	# cleanup shared mem
	cleanupSharedMem();
	# remove transport-fifo
	my $call_Ret = unlink($PATH_TRANSPORT_FIFO);
	doLog("MAIN : removing transport-fifo : ".$call_Ret);
	# remove pid-file
	doLog("MAIN : removing pid-file : " . deletePidFile());
	# terminate
	doLog("MAIN - terminate");
	exit;
}

#-------------------------------------------------------------------------------
# Sub: childReaderStop
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub childReaderStop {
	# we are going down
	doLog("STOP - CHILD - READER");
	# remove command-fifo
	my $call_Ret = unlink($PATH_COMMAND_FIFO);
	doLog("CHILD - READER : removing command-fifo : ".$call_Ret);
	# terminate
	doLog("CHILD - READER : terminate");
	exit;
}

#-------------------------------------------------------------------------------
# Sub: gotSigInt
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub gotSigInt {
	if ($childReader != 0) { # parent
		doLog("MAIN : got SIG-INT");
		parentStop();
	} else { # child
		doLog("CHILD - READER : got SIG-INT");
		childReaderStop();
	}
}

#-------------------------------------------------------------------------------
# Sub: gotSigQuit
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub gotSigQuit {
	if ($childReader != 0) { # parent
		doLog("MAIN : got SIG-QUIT");
		parentStop();
	} else { # child
		doLog("CHILD - READER : got SIG-QUIT");
		childReaderStop();
	}
}

################################################################################
# daemon subs
################################################################################

#-------------------------------------------------------------------------------
# Sub: daemonStartTorrent
# Parameters:	torrent-name
# Return:		int with return of start-call (0|1)
#-------------------------------------------------------------------------------
sub daemonStartTorrent {
	my $torrent = shift;
	if (!(defined $torrent)) {
		return 0;
	}
	# start torrent
	my $startCommand = $BIN_PHP." ".$BIN_FLUXCLI." start ".$torrent.".torrent &> /dev/null";
	if ($LOGLEVEL > 1) {
		doLog("MAIN : start-command : ".$startCommand);
	}
	eval { system($startCommand); };
	if ($@) {
		return 0;
	}
	$handleGlobals->shlock();
	$globals{"started"} += 1;
	$handleGlobals->shunlock();
	return 1;
}

#-------------------------------------------------------------------------------
# Sub: daemonUpdateRunningTorrents
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub daemonUpdateRunningTorrents {
	# get runnin clients
	opendir(DIR, $PATH_TORRENTDIR);
	my @pids = map { $_->[1] } # extract pathnames
	map { [ $_, "$_" ] } # no full paths
	grep { !/^\./ } # no dot-files
	grep { /.*\.pid$/ } # only .pid-files
	readdir(DIR);
	closedir(DIR);
	# flush running-jobs-hash
	$handleJobs->shlock();
	foreach my $jobName (keys %{$jobs{"running"}}) {
		# delete job
		delete($jobs{"running"}{$jobName});
	}
	$handleJobs->shunlock();
	# refill hash
	if (scalar(@pids) > 0) {
		foreach my $pidFile (@pids) {
			my $torrent = (substr ($pidFile,0,(length($pidFile))-9));
			my $user = getTorrentOwner($torrent);
			if (!(defined $user)) {
				$handleJobs->shlock();
				$jobs{"running"}{$torrent} = "unknown";
				$handleJobs->shunlock();
			} else {
				if (! exists $jobs{"running"}{$torrent}) {
					$handleJobs->shlock();
					$jobs{"running"}{$torrent} = $user;
					$handleJobs->shunlock();
				}
			}
		}
	}
}

#-------------------------------------------------------------------------------
# Sub: daemonLoadQueue
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub daemonLoadQueue {
	# read from file into queue-array
	open(QUEUEFILE,"< $PATH_QUEUE_FILE");
	$handleQueue->shlock();
	while (<QUEUEFILE>) {
		chomp;
		push(@queue,$_);
	}
	$handleQueue->shunlock();
	close QUEUEFILE;
	# fill job-hash
	$handleJobs->shlock();
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
	$handleJobs->shunlock();
	# done loading, delete queue-file
	return unlink($PATH_QUEUE_FILE);
}

#-------------------------------------------------------------------------------
# Sub: daemonSaveQueue
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub daemonSaveQueue {
	# open queue-file
	open(QUEUEFILE,">$PATH_QUEUE_FILE");
	# queued torrents
	foreach my $queueEntry (@queue) {
		print QUEUEFILE $queueEntry."\n";
	}
	# close queue-file
	close(QUEUEFILE);
}

#-------------------------------------------------------------------------------
# Sub: daemonDumpQueue
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub daemonDumpQueue {
	# open queue-file
	open(QUEUEFILE,">$PATH_QUEUE_FILE");
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

################################################################################
# more subs
################################################################################

#-------------------------------------------------------------------------------
# Sub: processCommand
# Parameters:	command
# Return:		-
#-------------------------------------------------------------------------------
sub processCommand {
	my $command = shift;
	my ($tempo1, $tempo2, $tempo3) = split(/:/,$command);
  
	if ("add" eq $tempo1) {
		my $addIt = 0;
		$handleJobs->shlock();
		if ((! exists $jobs{"queued"}{$tempo2}) && (! exists $jobs{"running"}{$tempo2})) {
			$addIt = 1;
			doLog("CHILD - READER : adding job to jobs queued : ".$tempo2." (".$tempo3.")");
			$jobs{"queued"}{$tempo2} = $tempo3;
		} else {
			if ($LOGLEVEL > 0) {
				doLog("CHILD - READER : job already present in jobs : ".$tempo2." (".$tempo3.")");
			}
		}
		$handleJobs->shunlock();
		if ($addIt == 1) {
			$handleQueue->shlock();
			push(@queue,$tempo2);
			$handleQueue->shunlock();
		}

	} elsif ("remove" eq $tempo1) {
		doLog("CHILD - READER : remove job from jobs queued : ".$tempo2);
		# remove from job-hash
		$handleJobs->shlock();
		delete($jobs{"queued"}{$tempo2});
		$handleJobs->shunlock();
		# remove from queue-stack
		my $Idx = 0;
		LOOP: foreach my $queueEntry (@queue) {
			last LOOP if ($queueEntry eq $tempo2);
			$Idx++;
		}
		$handleQueue->shlock();
		if ($Idx > 0) { # not first entry, stack-action
			my @stack;
			for (my $i = 0; $i < $Idx; $i++) {
				push(@stack,(shift @queue));
			}
			shift @queue;
			for (my $i = 0; $i < $Idx; $i++) {
				push(@queue, (shift @stack));
			}
			$Idx--;
		} else { # first entry, just shift
			shift @queue;
		}
		$handleQueue->shunlock();
    
	} elsif ("set" eq $tempo1) {
		doLog("CHILD - READER : reconf setting : ".$tempo2." : ".$tempo3);
		if ("MAX_TORRENTS" eq $tempo2) {
			$handleGlobals->shlock();
			$globals{"max_torrents"} = $tempo3;
			$handleGlobals->shunlock();
		} elsif ("MAX_TORRENTS_PER_USER" eq $tempo2) {
			$handleGlobals->shlock();
			$globals{"max_torrents_per_user"} = $tempo3;
			$handleGlobals->shunlock();
		}
    
	} elsif ("!" eq $tempo1) {
		if ($LOGLEVEL > 0) {
			doLog("CHILD - READER : got internal command : ".$command);
		}
		processInternalCommand($command);
    
	} else {
		doLog("CHILD - READER : got bogus command : ".$command);
	}
  
}

#-------------------------------------------------------------------------------
# Sub: processInternalCommand
# Parameters:	command
# Return:		-
#-------------------------------------------------------------------------------
sub processInternalCommand {
	my $command = shift;
	my (@commandAry)=split(/:/,$command);
	if ("status" eq $commandAry[1]) {
		if ($LOGLEVEL > 0) {
			doLog("exec internal command : status");
		}
		internalCommandStatus();
	} elsif ("stop" eq $commandAry[1]) {
		if ($LOGLEVEL > 0) {
			doLog("exec internal command : stop");
		}
		internalCommandStop();
	} elsif ("count-jobs" eq $commandAry[1]) {
		if ($LOGLEVEL > 0) {
			doLog("exec internal command : count-jobs");
		}
		internalCommandJobcount();
	} elsif ("count-queue" eq $commandAry[1]) {
		if ($LOGLEVEL > 0) {
			doLog("exec internal command : count-queue");
		}
		internalCommandQueuecount();
	} elsif ("list-queue" eq $commandAry[1]) {
		if ($LOGLEVEL > 0) {
			doLog("exec internal command : list-queue");
		}
		internalCommandQueuelist();
	} else {
		doLog("unknown internal command : ".$commandAry[1]);
	}
}

#-------------------------------------------------------------------------------
# Sub: internalCommandStop
# Parameters:
# Return:		-
#-------------------------------------------------------------------------------
sub internalCommandStop {
	# set flags
	$handleGlobals->shlock();
	$globals{"worker_running"} = 0;
	$globals{"reader_running"} = 0;
	$handleGlobals->shunlock();
	# send a SIGQUIT to parent, we are in hurry ~
	my $ppid = loadPidFile();
	if (defined($ppid) && (!($ppid eq ""))) {
		kill 'QUIT', $ppid;
	}
}

#-------------------------------------------------------------------------------
# Sub: internalCommandJobcount
# Parameters:
# Return:		-
#-------------------------------------------------------------------------------
sub internalCommandJobcount {
	# open transport-fifo
	if (! -p $PATH_TRANSPORT_FIFO) {
		return undef;
	}
	open(TRANSPORT, ">$PATH_TRANSPORT_FIFO");
	# get + write value to fifo
	my $jobcount = countJobs();
	print TRANSPORT $jobcount;
	close(TRANSPORT);
}

#-------------------------------------------------------------------------------
# Sub: internalCommandQueuecount
# Parameters:
# Return:		-
#-------------------------------------------------------------------------------
sub internalCommandQueuecount {
	# open transport-fifo
	if (! -p $PATH_TRANSPORT_FIFO) {
		return undef;
	}
	open(TRANSPORT, ">$PATH_TRANSPORT_FIFO");
	# get + write value to fifo
	my $jobcount = countQueue();
	print TRANSPORT $jobcount;
	close(TRANSPORT);
}

#-------------------------------------------------------------------------------
# Sub: internalCommandQueuelist
# Parameters:
# Return:		-
#-------------------------------------------------------------------------------
sub internalCommandQueuelist {
	# open transport-fifo
	if (! -p $PATH_TRANSPORT_FIFO) {
		return undef;
	}
	open(TRANSPORT, ">$PATH_TRANSPORT_FIFO");
	# write list to fifo
	foreach my $queueEntry (@queue) {
		print TRANSPORT $queueEntry.".torrent\n";
	}
	close(TRANSPORT);
}

#-------------------------------------------------------------------------------
# Sub: internalCommandStatus
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub internalCommandStatus {

	# open transport-fifo
	if (! -p $PATH_TRANSPORT_FIFO) {
		return undef;
	}
	open(TRANSPORT, ">$PATH_TRANSPORT_FIFO");

	# head
	print TRANSPORT "\n-= tfqmgr Revision ".$REVISION." =-\n\n";

	# get count-vars
	my $countQueue = countQueue();
	my $countRunning = countRunning();
	my $countJobs = $countQueue + $countRunning;

	# some vars
	print TRANSPORT "min sleep-time worker \t: $SLEEP_MIN s \n";
	print TRANSPORT "max sleep-time worker \t: $SLEEP_MAX s \n";
	print TRANSPORT "max torrents global \t: ".$globals{"max_torrents"}."\n";
	print TRANSPORT "max torrents per user \t: ".$globals{"max_torrents_per_user"}."\n";
	print TRANSPORT "max start-tries    \t: $MAX_START_TRIES \n";
	print TRANSPORT "start-try-extra-sleep \t: $START_TRIES_SLEEP s\n\n";

	# jobs total
	print TRANSPORT "jobs total \t: ".$countJobs."\n";

	# jobs queued
	print TRANSPORT "jobs queued \t: ".$countQueue."\n";
	foreach my $jobName (sort keys %{$jobs{"queued"}}) {
		my $jobUser = $jobs{"queued"}{$jobName};
		print TRANSPORT "  * ".$jobName." (".$jobUser.")\n";
	}

	# jobs running
	print TRANSPORT "jobs running \t: ".$countRunning."\n";
	foreach my $jobName (sort keys %{$jobs{"running"}}) {
		my $jobUser = $jobs{"running"}{$jobName};
		print TRANSPORT "  * ".$jobName." (".$jobUser.")\n";
	}

	# misc stats
	print TRANSPORT "\ntfqmgr up since $time_human (";
	my $tempiStringy = niceTimeString($time_unix);
	print TRANSPORT $tempiStringy.") ";
	print TRANSPORT "(".$globals{"main"}." cycles) \n";
	print TRANSPORT "tfqmgr started ".$globals{"started"}." torrents \n\n";

	# dump path vars on debug
	if ($LOGLEVEL > 1) {
		print TRANSPORT "BIN_PHP : ".$BIN_PHP."\n";
		print TRANSPORT "PATH_DATADIR : ".$PATH_DATADIR."\n";
		print TRANSPORT "PATH_COMMAND_FIFO : ".$PATH_COMMAND_FIFO."\n";
		print TRANSPORT "PATH_TRANSPORT_FIFO : ".$PATH_TRANSPORT_FIFO."\n";
		print TRANSPORT "PATH_QUEUE_FILE : ".$PATH_QUEUE_FILE."\n";
		print TRANSPORT "LOGFILE : ".$LOGFILE."\n";
		print TRANSPORT "PIDFILE : ".$PIDFILE."\n";
		print TRANSPORT "PATH_TORRENTDIR : ".$PATH_TORRENTDIR."\n\n";
	}

	# close handle
	close(TRANSPORT);
}

#-------------------------------------------------------------------------------
# Sub:  doDaemonCall
# Parameters:	String with DaemonCall, int if result should be read. (1|0)
# Return:	String with return of DaemonCall or 1 if no result is read.
#         returns undef on error.
#-------------------------------------------------------------------------------
sub doDaemonCall {
	my $callString = shift;
	my $hasResult = shift;
	if ((-p $PATH_COMMAND_FIFO) && (-p $PATH_TRANSPORT_FIFO)) {
		# send command
		open(COMMAND, ">$PATH_COMMAND_FIFO");
		print COMMAND "!:".$callString."\n";
		close (COMMAND);
		# read result
		if ($hasResult == 1) {
			my $retVal = "";
			open(TRANSPORT,"<$PATH_TRANSPORT_FIFO");
			while (<TRANSPORT>) {
				$retVal .= $_;
			}
			close(TRANSPORT);
			# return result
			return $retVal;
		} else {
			return 1;
		}
	} else {
		return undef;
	}
	return undef;
}

#-------------------------------------------------------------------------------
# Sub:  initPaths
# Parameters:	String path-param
# Return:		-
#-------------------------------------------------------------------------------
sub initPaths {
	my $pathVar = shift;
	if (!((substr $pathVar, -1) eq "/")) {
		$pathVar .= "/";
	}
	$PATH_TORRENTDIR = $pathVar.$PATH_TORRENTDIR."/";
	$PATH_DATADIR = $pathVar.$PATH_DATADIR."/";
	$PATH_COMMAND_FIFO = $PATH_DATADIR.$PATH_COMMAND_FIFO;
	$PATH_TRANSPORT_FIFO = $PATH_DATADIR.$PATH_TRANSPORT_FIFO;
	$PATH_QUEUE_FILE = $PATH_DATADIR.$PATH_QUEUE_FILE;
	$LOGFILE = $PATH_DATADIR.$LOGFILE;
	$PIDFILE = $PATH_DATADIR.$PIDFILE;
	# check if our main-dir exists. try to create if it doesnt
	if (! -d $PATH_DATADIR) {
		mkdir($PATH_DATADIR,0700);
	}
}

#-------------------------------------------------------------------------------
# Sub: getTorrentOwner
# Parameters:	torrent-name
# Return:		-
#-------------------------------------------------------------------------------
sub getTorrentOwner {
	my $torrent = shift;
	if (!(defined $torrent)) {
		return undef;
	}
	my $statFile = $PATH_TORRENTDIR.$torrent.".stat";
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
# Sub: countJobs
# Parameters:	-
# Return:	number of  Jobs
#-------------------------------------------------------------------------------
sub countJobs {
	my $jobcount = 0;
	$jobcount += countQueue();
	$jobcount += countRunning();
	return $jobcount;
}

#-------------------------------------------------------------------------------
# Sub: countQueue
# Parameters:	-
# Return:	number of queued jobs
#-------------------------------------------------------------------------------
sub countQueue {
	return scalar((keys %{$jobs{"queued"}}));
}

#-------------------------------------------------------------------------------
# Sub: countRunning
# Parameters:	-
# Return:	number of queued jobs
#-------------------------------------------------------------------------------
sub countRunning {
	return scalar((keys %{$jobs{"running"}}));
}

#-------------------------------------------------------------------------------
# Sub: doSysCall
# Parameters:	sys-call-string
# Return:		-
#-------------------------------------------------------------------------------
sub doSysCall {
	my $doCall = shift;
	#if ($LOGLEVEL > 1) { doLog($doCall); }
	system($doCall);
}

#-------------------------------------------------------------------------------
# Sub: doLog
# Parameters:	The String to log
# Return:		-
#-------------------------------------------------------------------------------
sub doLog {
	my $outString = shift;
	$time_human = localtime;
	open(LOGFILE,">>$LOGFILE");
	print LOGFILE $time_human." - ".$outString."\n";
	close(LOGFILE);
}

#-------------------------------------------------------------------------------
# Sub: niceTimeString
# Parameters:	start-time
# Return:		nice Time String
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

#-------------------------------------------------------------------------------
# Sub: initialize
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub initialize {
	# windows not supported.
	if ("$^O" =~ /win32/i) {
		print "\r\nWin32 not supported.\r\n";
		exit;
	}
	# init some vars
	$REVISION = do { my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
	($DIR=$0) =~ s/([^\/\\]*)$//;
	($PROG=$1) =~ s/\.([^\.]*)$//;
	$EXTENSION=$1;
	# hold pid of parent
	$parentPid = $$;
}

#-------------------------------------------------------------------------------
# Sub: writePidFile
# Parameters:	int with pid
# Return:		-
#-------------------------------------------------------------------------------
sub writePidFile {
	my $pid = shift;
	if (!(defined $pid)) {
		$pid = $$;
	}
	open(PIDFILE,">$PIDFILE");
	print PIDFILE $pid."\n";
	close(PIDFILE);
}

#-------------------------------------------------------------------------------
# Sub: loadPidFile
# Parameters:	-
# Return: int with pid of parent
#-------------------------------------------------------------------------------
sub loadPidFile {
	open(PIDFILE,"< $PIDFILE");
	my $pid = <PIDFILE>;
	close(PIDFILE);
	return (chomp $pid);
}

#-------------------------------------------------------------------------------
# Sub: deletePidFile
# Parameters:	-
# Return: return-val of delete
#-------------------------------------------------------------------------------
sub deletePidFile {
	return unlink($PIDFILE);
}

#-------------------------------------------------------------------------------
# Sub: cleanupSharedMem
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub cleanupSharedMem {
	doLog("MAIN : cleanup shared mem...");
	# untie vars
	untie %jobs;
	untie @queue;
	untie %globals;
	# undef vars
	undef $handleJobs;
	undef $handleQueue;
	undef $handleGlobals;
	undef %jobs;
	undef @queue;
	undef %globals;
	# whoami
	my $me = `$BINS{'whoami'}`;
	chomp $me;
	# sem
	my $qCall = $BINS{'ipcs'} . " -s";
	foreach my $line (grep(/$me/,qx($qCall))) {
		my ($id) = (split(/\s+/,$line,3))[1];
		chomp $id;
		my $result = "";
		eval {
			$result = `$BINS{'ipcrm'} sem $id 2> /dev/null`;
		};
		chomp $result;
		if ($LOGLEVEL > 1) { doLog("MAIN : removing sem : $id : $result"); }
	}
	# shm
	$qCall = $BINS{'ipcs'} . " -m";
	foreach my $line (grep(/$me/,qx($qCall))) {
		my ($id) = (split(/\s+/,$line,3))[1];
		chomp $id;
		my $result = `$BINS{'ipcrm'} shm $id 2> /dev/null`;
		chomp $result;
		if ($LOGLEVEL > 1) { doLog("MAIN : removing shm : $id : $result"); }
	}
}

#-------------------------------------------------------------------------------
# Sub: cleanup
# Parameters:	username
# Return:		-
#-------------------------------------------------------------------------------
sub cleanup {
	my $me = shift;
	if (!(defined $me)) {
		printUsage();
		exit;
	}
	chomp $me;
	# sem
	my $qCall = $BINS{'ipcs'} . " -s";
	foreach my $line (grep(/$me/,qx($qCall))) {
		my ($id) = (split(/\s+/,$line,3))[1];
		chomp $id;
		my $result = "";
		eval {
			$result = `$BINS{'ipcrm'} sem $id 2> /dev/null`;
		};
		chomp $result;
		print "removing sem for user $me : $id : $result \n";
	}
	# shm
	$qCall = $BINS{'ipcs'} . " -m";
	foreach my $line (grep(/$me/,qx($qCall))) {
		my ($id) = (split(/\s+/,$line,3))[1];
		chomp $id;
		my $result = `$BINS{'ipcrm'} shm $id 2> /dev/null`;
		chomp $result;
		print "removing shm for user $me : $id : $result \n";
	}
}

#-------------------------------------------------------------------------------
# Sub: loadModules
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub loadModules {
	my $mod = 'IPC::Shareable';
	# load mod
	if (eval "require $mod")  {
		return 1;
	} else {
		print "Fatal Error : cant load module \"".$mod."\"\n";
		# turn on autoflush
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

#-------------------------------------------------------------------------------
# Sub: checkRequirements
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub checkRequirements {
	print "\nChecking Requirements...\n";
	# check bins
	print "- bins : \n";
	foreach my $binName (sort keys %BINS) {
		my $tempo = `which $BINS{$binName}`;
		if (!(defined($tempo))) {
			print "Fatal Error : cant find ".$binName." defined as ".$BINS{$binName}."\n\n";
			exit;
		} else {
			chomp $tempo;
			if ($tempo eq "") {
				print "Fatal Error : cant find ".$binName." defined as ".$BINS{$binName}."\n\n";
				exit;
			} else {
				if (!(-x $tempo)) {
					print "Fatal Error : ".$binName." defined as ".$BINS{$binName}." is not executable\n\n";
					exit;
				}
			}
		}
	}
	print "  OK. \n";
	# check modules
	print "- modules : \n";
	if (loadModules() == 1) {
		print "  OK. \n";
	}
	print "looks good. \n\n";
}


#-------------------------------------------------------------------------------
# Sub: printUsage
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printUsage {
	print <<"USAGE";

$PROG.$EXTENSION Revision $REVISION

Usage: $PROG.$EXTENSION <start|stop|status|count-jobs|count-queue|list-queue|set> PATH [extra-args]
       $PROG.$EXTENSION <check>
       $PROG.$EXTENSION <cleanup> USERNAME

       <start>         : start daemon. extra-args :
                         1. max running torrents
                         2. max running torrents per user
                         3. path to php-binary

       <stop>          : stop daemon.
       <status>        : shows status of running daemon.
       <count-jobs>    : shows total number of jobs.
       <count-queue>   : shows number of queued jobs.
       <list-queue>    : shows list of queued jobs.

       <set>           : change a daemon-setting. extra-args :
                         1. setting-key (MAX_TORRENTS|MAX_TORRENTS_PER_USER)
                         2. setting-value

       <check>         : check Requirements.

       <cleanup>       : cleanup sem+shm. only do this if you must do it !

       PATH            : path defined inside Flux. (/usr/local/torrent)
       USERNAME        : the user running tfqmgr.pl (only needed for cleanup)

Note:
All commands except start-commands, check and cleanup are "proxied" commands.
The perl-script will write the command to the command-fifo, read the result from
the transport-fifo and then write it to stdout. (so it looks like the result is
coming from the invoked script itself but actually the called script
communicates with 2 different processes (of itself) in that case)
Programs should not use this proxy but read/write directly from/to the fifos.

Examples:
$PROG.$EXTENSION start /usr/local/torrent 5 2 /usr/bin/php
$PROG.$EXTENSION stop /usr/local/torrent
$PROG.$EXTENSION status /usr/local/torrent
$PROG.$EXTENSION count-jobs /usr/local/torrent
$PROG.$EXTENSION count-queue /usr/local/torrent
$PROG.$EXTENSION list-queue /usr/local/torrent
$PROG.$EXTENSION set /usr/local/torrent MAX_TORRENTS 5
$PROG.$EXTENSION set /usr/local/torrent MAX_TORRENTS_PER_USER 2
$PROG.$EXTENSION check
$PROG.$EXTENSION cleanup www-data

USAGE

}

# EOF