/*******************************************************************************
 * $Id$
 *
 * Copyright (c) 2005-2007 Transmission authors and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 ******************************************************************************/

/*******************************************************************************
 *
 * transmissioncli.c - use transmission with torrentflux-b4rt
 * http://tf-b4rt.berlios.de/
 *
 ******************************************************************************/

// include header
#include "transmissioncli.h"

/*******************************************************************************
 * main
 ******************************************************************************/
int main(int argc, char ** argv) {

	// vars
	int i, error, nat;
	tr_handle_t * h;
	tr_torrent_t * tor;
	tr_stat_t * s;
	tr_info_t * info;
	double tf_sharing = 0.0;
	char tf_eta[80];
	int tf_seeders, tf_leechers;

	/* get options */
	if (parseCommandLine(argc, argv)) {
		printf(HEADER, VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0], TR_DEFAULT_PORT);
		return 1;
	}

	/* show help */
	if (showHelp) {
		printf(HEADER, VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0], TR_DEFAULT_PORT);
		return 0;
	}

	// verbose
	if (verboseLevel < 0)
		verboseLevel = 0;
	else if (verboseLevel > 9)
		verboseLevel = 9;
	if (verboseLevel) {
		static char env[11];
		sprintf(env, "TR_DEBUG=%d", verboseLevel);
		putenv(env);
	}

	// initialize libtransmission
	h = tr_init();

	// open and parse torrent file
	if (!(tor = tr_torrentInit(h, torrentPath, 0, &error))) {
		tf_print(sprintf(tf_message, "Failed opening torrent file '%s'\n", torrentPath));
		goto failed;
	}

	/* show info */
	if (showInfo) {
		// info
		info = tr_torrentInfo(tor);
		// stat
		s = tr_torrentStat(tor);
		// Print torrent info (quite à la btshowmetainfo)
		printf("hash:     ");
		for (i = 0; i < SHA_DIGEST_LENGTH; i++)
			printf("%02x", info->hash[i]);
		printf("\n");
        printf("tracker:  %s:%d\n",
				s->trackerAddress, s->trackerPort );
        printf("announce: %s\n", s->trackerAnnounce );
		printf("size:     %"PRIu64" (%"PRIu64" * %d + %"PRIu64")\n",
			info->totalSize, info->totalSize / info->pieceSize,
			info->pieceSize, info->totalSize % info->pieceSize);
		if (info->comment[0])
			printf("comment:  %s\n", info->comment);
		if (info->creator[0])
			printf("creator:  %s\n", info->creator);
		printf("file(s):\n");
		for (i = 0; i < info->fileCount; i++)
			printf(" %s (%"PRIu64")\n", info->files[i].name,
				info->files[i].length);
		// cleanup
		goto cleanup;
	}

	/* show scrape */
	if (showScrape) {
		int seeders, leechers, downloaded;
		if (tr_torrentScrape(tor, &seeders, &leechers, &downloaded))
			printf("Scrape failed.\n");
		else
			printf("%d seeder(s), %d leecher(s), %d download(s).\n",
					seeders, leechers, downloaded);
		// cleanup
		goto cleanup;
	}

	/* start up transmission */

	// check port
	if (bindPort < 1 || bindPort > 65535) {
		tf_print(sprintf(tf_message, "Invalid port '%d'\n", bindPort));
		// cleanup
		goto cleanup;
	}

	// check owner-arg
	if (tf_owner == NULL) {
		tf_print(sprintf(tf_message, "no owner supplied, using 'n/a'.\n"));
		tf_owner = malloc((4) * sizeof(char));
		if (tf_owner == NULL) {
			tf_print(sprintf(tf_message, "Error : not enough mem for malloc\n"));
			// cleanup
			goto cleanup;
		}
		strcpy(tf_owner, "n/a");
	}

	// check rate-args
	// up
	switch (uploadLimit) {
		case 0:
			uploadLimit = -1;
			break;
		case -2:
			uploadLimit = 0;
			break;
	}
	// down
	switch (downloadLimit) {
		case 0:
			downloadLimit = -1;
			break;
		case -2:
			downloadLimit = 0;
			break;
	}

	// print what we are starting up
	tf_print(sprintf(tf_message, "transmission starting up :\n"));
	tf_print(sprintf(tf_message, " - torrent : %s\n", torrentPath));
	tf_print(sprintf(tf_message, " - owner : %s\n", tf_owner));
	tf_print(sprintf(tf_message, " - seedLimit : %d\n", seedLimit));
	tf_print(sprintf(tf_message, " - bindPort : %d\n", bindPort));
	tf_print(sprintf(tf_message, " - uploadLimit : %d\n", uploadLimit));
	tf_print(sprintf(tf_message, " - downloadLimit : %d\n", downloadLimit));
	tf_print(sprintf(tf_message, " - natTraversal : %d\n", natTraversal));
	tf_print(sprintf(tf_message, " - displayInterval : %d\n", displayInterval));
	if (finishCall != NULL)
		tf_print(sprintf(tf_message, " - finishCall : %s\n", finishCall));

	// signals
	signal(SIGTERM, sigHandler);
	signal(SIGINT, sigHandler);

	// set port + rates
	tr_setBindPort(h, bindPort);
	tr_setGlobalUploadLimit(h, uploadLimit);
	tr_setGlobalDownloadLimit(h, downloadLimit);

	// nat-traversal
	if (natTraversal)
		tr_natTraversalEnable(h);
	else
		tr_natTraversalDisable(h);

	// set folder
	tr_torrentSetFolder(tor, ".");

	// start the torrent
	tr_torrentStart(tor);

	// info
	info = tr_torrentInfo(tor);

	// initialize status-facility
	if (tf_initializeStatusFacility() == 0) {
		tf_print(sprintf(tf_message, "Failed to init status-facility. exit.\n"));
		goto failed;
	}

	// initialize command-facility
	if (tf_initializeCommandFacility() == 0) {
		tf_print(sprintf(tf_message, "Failed to init command-facility. exit.\n"));
		goto failed;
	}

	// write pid
	if (tf_pidWrite() == 0) {
		tf_print(sprintf(tf_message, "Failed to write pid-file. exit.\n"));
		goto failed;
	}

	// print that we are done with startup
	tf_print(sprintf(tf_message, "transmission up and running.\n"));

	/* main-loop */
	while (!mustDie) {

		// torrent-stat
		s = tr_torrentStat(tor);

		if (s->status & TR_STATUS_CHECK) {                   /* --- CHECK --- */

			// write stat-file
			tf_stat_fp = fopen(tf_stat_file, "w+");
			if (tf_stat_fp != NULL) {
				fprintf(tf_stat_fp, "%d\n%.1f\n%s\n0 kB/s\n0 kB/s\n%s\n0\n0\n0.0\n%d\n0\n%" PRIu64 "\n%" PRIu64,
					1,                        /* State             */
					100.0 * s->progress,      /* checking progress */
					"Checking existing data", /* State text        */
					                          /* download speed    */
					                          /* upload speed      */
					tf_owner,                 /* owner             */
					                          /* seeds             */
					                          /* peers             */
					                          /* sharing           */
					seedLimit,                /* seedlimit         */
					                          /* uploaded bytes    */
					s->downloaded,            /* downloaded bytes  */
					info->totalSize);         /* global size       */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n", tf_stat_file));
			}

		} else if (s->status & TR_STATUS_DOWNLOAD) {      /* --- DOWNLOAD --- */

			// sharing
			if (s->downloaded != 0)
				tf_sharing =
					((double)(s->uploaded) / (double)(s->downloaded)) * 100;

			// seeders + leechers
			tf_seeders = (s->seeders < 0)
				? 0
				: s->seeders;
			tf_leechers = (s->leechers < 0)
				? 0
				: s->leechers;

			// eta
			if (s->eta != -1) {
				// sanity-check. value of eta >= 7 days is not really of use
				if (s->eta < 604800) {
					if ((s->eta / (24 * 60 * 60)) != 0) {
						sprintf(tf_eta, "%d:%02d:%02d:%02d",
							s->eta / (24 * 60 * 60),
							((s->eta) % (24 * 60 * 60)) / (60 * 60),
							((s->eta) % (60 * 60) / 60),
							s->eta % 60);
					} else if ((s->eta / (60 * 60)) != 0) {
						sprintf(tf_eta, "%d:%02d:%02d",
							(s->eta) / (60 * 60),
							((s->eta) % (60 * 60) / 60),
							s->eta % 60);
					} else {
						sprintf(tf_eta, "%d:%02d",
							(s->eta) / 60, s->eta % 60);
					}
				} else {
					sprintf(tf_eta, "-");
				}
			} else {
				sprintf(tf_eta, "-");
			}
			if ((s->seeders == -1) && (s->peersTotal == 0))
				sprintf(tf_eta, "Connecting to Peers");

			// write stat-file
			tf_stat_fp = fopen(tf_stat_file, "w+");
			if (tf_stat_fp != NULL) {
				fprintf(tf_stat_fp, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
					1,                                /* State            */
					100.0 * s->progress,              /* progress         */
					tf_eta,                           /* Estimated time   */
					s->rateDownload,                  /* download speed   */
					s->rateUpload,                    /* upload speed     */
					tf_owner,                         /* owner            */
					s->peersUploading, tf_seeders,    /* seeds            */
					s->peersDownloading, tf_leechers, /* peers            */
					tf_sharing,                       /* sharing          */
					seedLimit,                        /* seedlimit        */
					s->uploaded,                      /* uploaded bytes   */
					s->downloaded,                    /* downloaded bytes */
					info->totalSize);                 /* global size      */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n",
					tf_stat_file));
			}

		} else if (s->status & TR_STATUS_SEED) {              /* --- SEED --- */

			// sharing
			tf_sharing = (s->downloaded != 0)
				? (((double)(s->uploaded) / (double)(s->downloaded)) * 100)
				: (((double)(s->uploaded) / (double)(info->totalSize)) * 100);

			// die-on-seed-limit / die-when-done
			if (seedLimit == -1) {
				tf_print(sprintf(tf_message,
					"die-when-done set, setting shutdown-flag...\n"));
				mustDie = 1;
			} else if ((seedLimit != 0) &&
				(tf_sharing > (double)(seedLimit))) {
				tf_print(sprintf(tf_message,
					"seed-limit %d reached, setting shutdown-flag...\n",
					seedLimit));
				mustDie = 1;
			}

			// seeders + leechers
			tf_seeders = (s->seeders < 0)
				? 0
				: s->seeders;
			tf_leechers = (s->leechers < 0)
				? 0
				: s->leechers;

			// write stat-file
			tf_stat_fp = fopen(tf_stat_file, "w+");
			if (tf_stat_fp != NULL) {
				fprintf(tf_stat_fp, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
					1,                                /* State            */
					100.0 * s->progress,              /* progress         */
					"Download Succeeded!",            /* State text       */
					s->rateDownload,                  /* download speed   */
					s->rateUpload,                    /* upload speed     */
					tf_owner,                         /* owner             */
					s->peersUploading, tf_seeders,    /* seeds            */
					s->peersDownloading, tf_leechers, /* peers            */
					tf_sharing,                       /* sharing          */
					seedLimit,                        /* seedlimit        */
					s->uploaded,                      /* uploaded bytes   */
					s->downloaded,                    /* downloaded bytes */
					info->totalSize);                 /* global size      */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n",
					tf_stat_file));
			}

		} // end status-if

		// errors
		if (s->error & TR_ETRACKER)
			tf_print(sprintf(tf_message,
				"trackerError : %s\n", s->trackerError));

		// check if finished / finishCall / process command-stack / sleep
		if (tr_getFinished(tor)) {
			// finishCall
			if (finishCall != NULL)
				system(finishCall);
		} else {
			for (i = 0; i < displayInterval; i++) {
				// process command-stack
				if (tf_processCommandStack(h))
					break;
				// sleep
				sleep(1);
			}
		}

	} /* main-loop */

	// print that we are going down
	tf_print(sprintf(tf_message, "transmission shutting down...\n"));

	// mark torrent as stopped in tf-stat-file

	// torrent-stat
	s = tr_torrentStat(tor);

	// sharing
	tf_sharing = (s->downloaded != 0)
		? (((double)(s->uploaded) / (double)(s->downloaded)) * 100)
		: (((double)(s->uploaded) / (double)(info->totalSize)) * 100);

	// write stat-file
	tf_stat_fp = fopen(tf_stat_file, "w+");
	if (tf_stat_fp != NULL) {
		float progress;
		if (s->status & TR_STATUS_SEED) {
			sprintf(tf_eta, "Download Succeeded!");
			progress = 100;
		} else {
			sprintf(tf_eta, "Torrent Stopped");
			progress = -(1 + s->progress) * 100;
		}
		fprintf(tf_stat_fp, "%d\n%.1f\n%s\n\n\n%s\n\n\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
			0,                /* State            */
			progress,         /* progress         */
			tf_eta,           /* State text       */
							  /* download speed   */
							  /* upload speed     */
			tf_owner,         /* owner            */
							  /* seeds            */
							  /* peers            */
			tf_sharing,       /* sharing          */
			seedLimit,        /* seedlimit        */
			s->uploaded,      /* uploaded bytes   */
			s->downloaded,    /* downloaded bytes */
			info->totalSize); /* global size      */
		fclose(tf_stat_fp);
	} else {
		tf_print(sprintf(tf_message,
			"error opening stat-file for write : %s\n", tf_stat_file));
	}

	// Try for 5 seconds to notify the tracker that we are leaving
	// and to delete any port mappings for nat traversal
	tr_torrentStop(tor);
	tr_natTraversalDisable(h);
	for (i = 0; i < 10; i++) {
		s = tr_torrentStat(tor);
		nat = tr_natTraversalStatus(h);
		if (s->status & TR_STATUS_PAUSE && TR_NAT_TRAVERSAL_DISABLED == nat) {
			// The 'stopped' tracker message was sent
			// and port mappings were deleted
			break;
		}
		usleep(500000);
	}

	// remove pid file
	tf_pidDelete();

	// print exit
	tf_print(sprintf(tf_message, "transmission exit.\n"));

/*
 * cleanup
 */
cleanup:
tr_torrentClose(h, tor);

/*
 * failed
 */
failed:
tr_close(h);
return 0;

} // end main

/*******************************************************************************
 * tf_processCommandStack
 ******************************************************************************/
static int tf_processCommandStack(tr_handle_t *h) {
	// process command-file if exists
	tf_cmd_fp = NULL;
	tf_cmd_fp = fopen(tf_cmd_file, "r");
	return (tf_cmd_fp == NULL)
		? 0
		: tf_processCommandFile(h);
}

/*******************************************************************************
 * tf_processCommandFile
 ******************************************************************************/
static int tf_processCommandFile(tr_handle_t *h) {

	// local vars
	int commandCount = 0;
	int isNewline;
	long fileLen;
	long index;
	long startPos;
	long totalChars;
	char currentLine[128];
	char *fileBuffer;
	char *fileCurrentPos;

	// process file
	tf_print(sprintf(tf_message,
		"Processing command-file %s...\n", tf_cmd_file));

	// get length
	fseek(tf_cmd_fp, 0L, SEEK_END);
	fileLen = ftell(tf_cmd_fp);
	rewind(tf_cmd_fp);

	// calloc buffer
	fileBuffer = calloc(fileLen + 1, sizeof(char));
	if (fileBuffer == NULL) {
		tf_print(sprintf(tf_message,
			"Error : test_processCommandFile : not enough mem to read command-file\n"));
		return 0;
	}

	// read file to buffer
	fread(fileBuffer, fileLen, 1, tf_cmd_fp);

	// close file
	fclose(tf_cmd_fp);

	// remove file
	remove(tf_cmd_file);

	// null pointer
	tf_cmd_fp = NULL;

	// sanity-check if file contained "something"
	if (fileLen < 1) {
		tf_print(sprintf(tf_message, "No commands found.\n"));
		return 0;
	}

	// reset counter
	totalChars = 0L;

	// set current pos pointer to begin
	fileCurrentPos = fileBuffer;

	// process content
	while (*fileCurrentPos) {
		// reset counter and flags
		index = 0L;
		isNewline = 0;
		startPos = totalChars;
		while (*fileCurrentPos) {
			if (!isNewline) {
				// check for new-line, flag if found
				if (*fileCurrentPos == 10)
					isNewline = 1;
			} else if (*fileCurrentPos != 10) {
				// done with line
				break;
			}
			// add char and increment
			++totalChars;
			if (index < 128) {
				currentLine[index++] = *fileCurrentPos++;
			} else {
				fileCurrentPos++;
				break;
			}
		} // end line while loop
		if (index > 1) {
			// increment command-count
			commandCount++;
			// term string, chop it
			currentLine[index - 1] = '\0';
			// exec, early out when reading a quit-command
			if (tf_execCommand(h, currentLine))
				return 1;
		}
	} // end file while loop

	// print if no commands found
	if (commandCount == 0)
		tf_print(sprintf(tf_message, "No commands found.\n"));

	// return
	return 0;
}

/*******************************************************************************
 * tf_execCommand
 ******************************************************************************/
static int tf_execCommand(tr_handle_t *h, char *s) {

	// local vars
	int i;
	int len = strlen(s);
	char opcode;
	char workload[len];

	// parse command-string
	opcode = s[0];
	for (i = 0; i < len - 1; i++)
		workload[i] = s[i + 1];
	workload[len - 1] = '\0';

	// opcode-switch
	switch (opcode) {

		case 'q':
			tf_print(sprintf(tf_message,
				"command: stop-request, setting shutdown-flag...\n"));
			mustDie = 1;
			return 1;

		case 'u':
			uploadLimit = atoi(workload);
			tf_print(sprintf(tf_message,
				"command: setting upload-rate to %d\n", uploadLimit));
			tr_setGlobalUploadLimit(h, uploadLimit);
			return 0;

		case 'd':
			downloadLimit = atoi(workload);
			tf_print(sprintf(tf_message,
				"command: setting download-rate to %d\n", downloadLimit));
			tr_setGlobalDownloadLimit(h, downloadLimit);
			return 0;

		default:
			tf_print(sprintf(tf_message,
				"op-code unknown: %c\n", opcode));
			return 0;

	}
	return 0;
}

/*******************************************************************************
 * tf_initializeStatusFacility
 ******************************************************************************/
static int tf_initializeStatusFacility(void) {
	int i;
	int len = strlen(torrentPath) + 5;
	tf_stat_file = malloc((len + 1) * sizeof(char));
	if (tf_stat_file == NULL) {
		tf_print(sprintf(tf_message,
			"Error : tf_initializeStatusFacility : not enough mem for malloc\n"));
		return 0;
	}
	for (i = 0; i < len - 5; i++)
		tf_stat_file[i] = torrentPath[i];
	tf_stat_file[len - 5] = '.';
	tf_stat_file[len - 4] = 's';
	tf_stat_file[len - 3] = 't';
	tf_stat_file[len - 2] = 'a';
	tf_stat_file[len - 1] = 't';
	tf_stat_file[len] = '\0';
	tf_print(sprintf(tf_message,
			"initialized status-facility. (%s)\n" , tf_stat_file));
	return 1;
}

/*******************************************************************************
 * tf_initializeCommandFacility
 ******************************************************************************/
static int tf_initializeCommandFacility(void) {
	int i;
	int len = strlen(torrentPath) + 4;
	tf_cmd_file = malloc((len + 1) * sizeof(char));
	if (tf_cmd_file == NULL) {
		tf_print(sprintf(tf_message,
			"Error : tf_initializeCommandFacility : not enough mem for malloc\n"));
		return 0;
	}
	for (i = 0; i < len - 4; i++)
		tf_cmd_file[i] = torrentPath[i];
	tf_cmd_file[len - 4] = '.';
	tf_cmd_file[len - 3] = 'c';
	tf_cmd_file[len - 2] = 'm';
	tf_cmd_file[len - 1] = 'd';
	tf_cmd_file[len] = '\0';
	tf_print(sprintf(tf_message,
			"initialized command-facility. (%s)\n" , tf_cmd_file));
	// remove command-file if exists
	tf_cmd_fp = NULL;
	tf_cmd_fp = fopen(tf_cmd_file, "r");
	if (tf_cmd_fp != NULL) {
		// close file
		fclose(tf_cmd_fp);
		tf_print(sprintf(tf_message, "removing command-file %s...\n", tf_cmd_file));
		// remove file
		remove(tf_cmd_file);
		// null pointer
		tf_cmd_fp = NULL;
	}
	return 1;
}

/*******************************************************************************
 * tf_pidWrite
 ******************************************************************************/
static int tf_pidWrite(void) {
	int i;
	FILE * pidFile;
	pid_t currentPid = getpid();
	int len = strlen(torrentPath) + 4;
	char tf_pid_file[len + 1];
	for (i = 0; i < len - 4; i++)
		tf_pid_file[i] = torrentPath[i];
	tf_pid_file[len - 4] = '.';
	tf_pid_file[len - 3] = 'p';
	tf_pid_file[len - 2] = 'i';
	tf_pid_file[len - 1] = 'd';
	tf_pid_file[len] = '\0';
	pidFile = fopen(tf_pid_file, "w+");
	if (pidFile != NULL) {
		fprintf(pidFile, "%d", currentPid);
		fclose(pidFile);
		tf_print(sprintf(tf_message,
			"wrote pid-file : %s (%d)\n" , tf_pid_file , currentPid));
		return 1;
	} else {
		tf_print(sprintf(tf_message,
			"error opening pid-file for write : %s (%d)\n", tf_pid_file , currentPid));
		return 0;
	}
}

/*******************************************************************************
 * tf_pidDelete
 ******************************************************************************/
static int tf_pidDelete(void) {
	int i;
	int len = strlen(torrentPath) + 4;
	char tf_pid_file[len + 1];
	for (i = 0; i < len - 4; i++)
		tf_pid_file[i] = torrentPath[i];
	tf_pid_file[len - 4] = '.';
	tf_pid_file[len - 3] = 'p';
	tf_pid_file[len - 2] = 'i';
	tf_pid_file[len - 1] = 'd';
	tf_pid_file[len] = '\0';
	tf_print(sprintf(tf_message, "removing pid-file : %s\n", tf_pid_file));
	remove(tf_pid_file);
	return 1;
}

/*******************************************************************************
 * tf_print
 ******************************************************************************/
static int tf_print(int len) {
	time_t ct;
	struct tm * cts;
	time(&ct);
	cts = localtime(&ct);
	return fprintf(stderr, "[%4d/%02d/%02d - %02d:%02d:%02d] %s",
		cts->tm_year + 1900,
		cts->tm_mon + 1,
		cts->tm_mday,
		cts->tm_hour,
		cts->tm_min,
		cts->tm_sec,
		((tf_message != NULL) && (len > 0)) ? tf_message : "\n"
	);
}

/*******************************************************************************
 * sigHandler
 ******************************************************************************/
static void sigHandler(int signal) {
	switch (signal) {
		case SIGTERM:
			tf_print(sprintf(tf_message, "got SIGTERM, setting shutdown-flag...\n"));
			mustDie = 1;
		case SIGINT:
			tf_print(sprintf(tf_message, "got SIGINT, setting shutdown-flag...\n"));
			mustDie = 1;
			break;
		default:
			break;
	}
}

/*******************************************************************************
 * parseCommandLine
 ******************************************************************************/
static int parseCommandLine(int argc, char ** argv) {
	for(;;) {
		static struct option long_options[] =
		{ { "help",               no_argument,       NULL, 'h' },
		  { "info",               no_argument,       NULL, 'i' },
		  { "scrape",             no_argument,       NULL, 's' },
		  { "verbose",            required_argument, NULL, 'v' },
		  { "port",               required_argument, NULL, 'p' },
		  { "upload",             required_argument, NULL, 'u' },
		  { "download",           required_argument, NULL, 'd' },
		  { "finish",             required_argument, NULL, 'f' },
		  { "seedlimit",          required_argument, NULL, 'c' },
		  { "display_interval",   required_argument, NULL, 'e' },
		  { "owner",              required_argument, NULL, 'o' },
		  { "nat-traversal",      no_argument,       NULL, 'n' },
		  { 0, 0, 0, 0} };
		int c, optind = 0;
		c = getopt_long(argc, argv,
			"hisv:p:u:d:f:c:e:o:n", long_options, &optind);
		if (c < 0)
			break;
		switch(c) {
			case 'h':
				showHelp = 1;
				break;
			case 'i':
				showInfo = 1;
				break;
			case 's':
				showScrape = 1;
				break;
			case 'v':
				verboseLevel = atoi(optarg);
				break;
			case 'p':
				bindPort = atoi(optarg);
				break;
			case 'u':
				uploadLimit = atoi(optarg);
				break;
			case 'd':
				downloadLimit = atoi(optarg);
				break;
			case 'f':
				finishCall = optarg;
				break;
			case 'n':
				natTraversal = 1;
				break;
			case 'c':
				seedLimit = atoi(optarg);
				break;
			case 'e':
				displayInterval = atoi(optarg);
				break;
			case 'o':
				tf_owner = optarg;
				break;
			default:
				return 1;
		}
	}
	if (optind > argc - 1)
		return !showHelp;
	torrentPath = argv[optind];
	return 0;
}

