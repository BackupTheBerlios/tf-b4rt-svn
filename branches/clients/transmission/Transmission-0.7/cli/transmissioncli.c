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
	int i, error;
	tr_handle_t * h;
	tr_stat_t * s;
	tr_info_t * info;
	double tf_sharing = 0.0;
	char tf_eta[80];
	int tf_seeders, tf_leechers;

	/* get options */
	if (parseCommandLine(argc, argv)) {
		printf(HEADER, VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0],
			verboseLevel, natTraversal, TR_DEFAULT_PORT,
			uploadLimit, downloadLimit,
			tf_dieWhenDone, tf_seedLimit, tf_displayInterval
		);
		return EXIT_FAILURE;
	}

	/* show help */
	if (showHelp) {
		printf(HEADER, VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0],
			verboseLevel, natTraversal, TR_DEFAULT_PORT,
			uploadLimit, downloadLimit,
			tf_dieWhenDone, tf_seedLimit, tf_displayInterval
		);
		return EXIT_SUCCESS;
	}

	// verbose
	if (verboseLevel < 0)
		verboseLevel = 0;
	else if (verboseLevel > 9)
		verboseLevel = 9;
	if (verboseLevel) {
		static char env[11];
		snprintf(env, sizeof env, "TR_DEBUG=%d", verboseLevel);
		putenv(env);
	}

	// check bin-port
	if (bindPort < 1 || bindPort > 65535) {
		tf_print(sprintf(tf_message, "Invalid port '%d'\n", bindPort));
		return EXIT_FAILURE;
	}

	// initialize libtransmission
	h = tr_init("cli");

	// open and parse torrent file
	if (!(tor = tr_torrentInit(h, torrentPath, NULL, 0, &error))) {
		tf_print(sprintf(tf_message,
			"Failed opening torrent file '%s'\n", torrentPath));
		tr_close(h);
		return EXIT_FAILURE;
	}

	/* show info */
	if (showInfo) {
		tf_showInfo();
		goto cleanup;
	}

	/* show scrape */
	if (showScrape) {
		tf_showScrape();
		goto cleanup;
	}

	/* start up transmission */

	// check owner-arg
	if (tf_owner == NULL) {
		tf_print(sprintf(tf_message, "no owner supplied, using 'n/a'.\n"));
		tf_owner = malloc((4) * sizeof(char));
		if (tf_owner == NULL) {
			tf_print(sprintf(tf_message,
				"Error : not enough mem for malloc\n"));
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
	tf_print(sprintf(tf_message,
		"transmission starting up :\n"));
	tf_print(sprintf(tf_message,
		" - torrent : %s\n", torrentPath));
	tf_print(sprintf(tf_message,
		" - owner : %s\n", tf_owner));
	tf_print(sprintf(tf_message,
		" - dieWhenDone : %d\n", tf_dieWhenDone));
	tf_print(sprintf(tf_message,
		" - seedLimit : %d\n", tf_seedLimit));
	tf_print(sprintf(tf_message,
		" - bindPort : %d\n", bindPort));
	tf_print(sprintf(tf_message,
		" - uploadLimit : %d\n", uploadLimit));
	tf_print(sprintf(tf_message,
		" - downloadLimit : %d\n", downloadLimit));
	tf_print(sprintf(tf_message,
		" - natTraversal : %d\n", natTraversal));
	tf_print(sprintf(tf_message,
		" - displayInterval : %d\n", tf_displayInterval));
	if (finishCall != NULL)
		tf_print(sprintf(tf_message,
			" - finishCall : %s\n", finishCall));

	// signals
	signal(SIGHUP, sigHandler);
	signal(SIGINT, sigHandler);
	signal(SIGTERM, sigHandler);
	signal(SIGQUIT, sigHandler);

	// set port + rates
	tr_setBindPort(h, bindPort);
	tr_setGlobalUploadLimit(h, uploadLimit);
	tr_setGlobalDownloadLimit(h, downloadLimit);

	// nat-traversal
	tr_natTraversalEnable(h, natTraversal);

	// set folder
	tr_torrentSetFolder(tor, ".");

	// start the torrent
	tr_torrentStart(tor);

	// info
	info = tr_torrentInfo(tor);

	// initialize status-facility
	if (tf_initializeStatusFacility() == 0) {
		tf_print(sprintf(tf_message,
			"Failed to init status-facility. exit.\n"));
		goto failed;
	}

	// initialize command-facility
	if (tf_initializeCommandFacility() == 0) {
		tf_print(sprintf(tf_message,
			"Failed to init command-facility. exit.\n"));
		goto failed;
	}

	// write pid
	if (tf_pidWrite() == 0) {
		tf_print(sprintf(tf_message,
			"Failed to write pid-file. exit.\n"));
		goto failed;
	}

	// print that we are done with startup
	tf_print(sprintf(tf_message, "transmission up and running.\n"));

	/* main-loop */
	for (;;) {

		// check for signal
		if (gotsig) {

			// reset flag
			gotsig = 0;

			// set shutdown-flag
			tf_shutdown = 1;
		}

		/* init shutdown */
		if (tf_shutdown == 1) {

			// set shutdown-flag
			tf_shutdown = 2;

			// stop torrent
			tr_torrentStop(tor);

		/* in shutdown */
		} else if (tf_shutdown == 2) {

			// sleep 0.5 secs
			usleep(500000);

		/* normal running */
		} else {

			// internal loop
			for (i = 0; i < tf_displayInterval; i++) {

				// process command-stack
				if (tf_processCommandStack(h)) {

					// set shutdown-flag
					tf_shutdown = 1;

					// break internal for loop
					break;
				}

				// sleep
				sleep(1);
			}

			// continue to init shutdown asap
			if (tf_shutdown == 1) {
				continue;
			}

		}

		// torrent-stat
		s = tr_torrentStat(tor);

		/* --- PAUSE --- */
		if (s->status & TR_STATUS_PAUSE) {

			// break
			break;

		/* --- CHECK --- */
		} else if (s->status & TR_STATUS_CHECK) {

			// write stat-file
			tf_stat_fp = fopen(tf_stat_file, "w+");
			if (tf_stat_fp != NULL) {
				fprintf(tf_stat_fp,
					"%d\n%.1f\n%s\n0 kB/s\n0 kB/s\n%s\n0\n0\n0.0\n%d\n0\n%" PRIu64 "\n%" PRIu64,
					1,                        /* State             */
					100.0 * s->progress,      /* checking progress */
					"Checking existing data", /* State text        */
					                          /* download speed    */
					                          /* upload speed      */
					tf_owner,                 /* owner             */
					                          /* seeds             */
					                          /* peers             */
					                          /* sharing           */
					tf_seedLimit,             /* seedlimit         */
					                          /* uploaded bytes    */
					s->downloaded,            /* downloaded bytes  */
					info->totalSize);         /* global size       */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n", tf_stat_file));
			}

		/* --- DOWNLOAD --- */
		} else if (s->status & TR_STATUS_DOWNLOAD) {

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
					if ((s->eta / (86400)) != 0) {
						sprintf(tf_eta, "%d:%02d:%02d:%02d",
							s->eta / (86400),
							((s->eta) % (86400)) / (3600),
							((s->eta) % (3600) / 60),
							s->eta % 60);
					} else if ((s->eta / (3600)) != 0) {
						sprintf(tf_eta, "%d:%02d:%02d",
							(s->eta) / (3600),
							((s->eta) % (3600) / 60),
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
				fprintf(tf_stat_fp,
					"%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
					1,                                /* State            */
					100.0 * s->progress,              /* progress         */
					tf_eta,                           /* Estimated time   */
					s->rateDownload,                  /* download speed   */
					s->rateUpload,                    /* upload speed     */
					tf_owner,                         /* owner            */
					s->peersUploading, tf_seeders,    /* seeds            */
					s->peersDownloading, tf_leechers, /* peers            */
					tf_sharing,                       /* sharing          */
					tf_seedLimit,                     /* seedlimit        */
					s->uploaded,                      /* uploaded bytes   */
					s->downloaded,                    /* downloaded bytes */
					info->totalSize);                 /* global size      */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n",
					tf_stat_file));
			}

		/* --- SEED --- */
		} else if (s->status & TR_STATUS_SEED) {

			// sharing
			tf_sharing = (s->downloaded != 0)
				? (((double)(s->uploaded) / (double)(s->downloaded)) * 100)
				: (((double)(s->uploaded) / (double)(info->totalSize)) * 100);

			// die-when-done / die-on-seed-limit
			if (tf_dieWhenDone == 1) {
				tf_print(sprintf(tf_message,
					"die-when-done set, setting shutdown-flag...\n"));
				tf_shutdown = 1;
			} else {
				if (tf_seedLimit == -1) {
					tf_print(sprintf(tf_message,
						"sharekill set to -1, setting shutdown-flag...\n"));
					tf_shutdown = 1;
				} else if ((tf_seedLimit > 0) &&
					(tf_sharing > (double)(tf_seedLimit))) {
					tf_print(sprintf(tf_message,
						"seed-limit %d reached, setting shutdown-flag...\n",
						tf_seedLimit));
					tf_shutdown = 1;
				}
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
				fprintf(tf_stat_fp,
					"%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
					1,                                /* State            */
					100.0 * s->progress,              /* progress         */
					"Download Succeeded!",            /* State text       */
					s->rateDownload,                  /* download speed   */
					s->rateUpload,                    /* upload speed     */
					tf_owner,                         /* owner            */
					s->peersUploading, tf_seeders,    /* seeds            */
					s->peersDownloading, tf_leechers, /* peers            */
					tf_sharing,                       /* sharing          */
					tf_seedLimit,                     /* seedlimit        */
					s->uploaded,                      /* uploaded bytes   */
					s->downloaded,                    /* downloaded bytes */
					info->totalSize);                 /* global size      */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n",
					tf_stat_file));
			}

		/* --- STOPPING --- */
		} else if (s->status & TR_STATUS_STOPPING) {

			// write stat-file
			tf_stat_fp = fopen(tf_stat_file, "w+");
			if (tf_stat_fp != NULL) {
				fprintf(tf_stat_fp,
					"%d\n%.1f\n%s\n\n\n%s\n\n\n\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
					0,                   /* State            */
					100.0 * s->progress, /* progress         */
					"Stopping...",       /* State text       */
									     /* download speed   */
									     /* upload speed     */
					tf_owner,            /* owner            */
									     /* seeds            */
									     /* peers            */
									     /* sharing          */
					tf_seedLimit,        /* seedlimit        */
					s->uploaded,         /* uploaded bytes   */
					s->downloaded,       /* downloaded bytes */
					info->totalSize);    /* global size      */
				fclose(tf_stat_fp);
			} else {
				tf_print(sprintf(tf_message,
					"error opening stat-file for write : %s\n",
					tf_stat_file));
			}

		} // end status-if

		// errors
		if (s->error)
			tf_print(sprintf(tf_message,
				"errorString : %s\n", s->errorString));

		// check if finished / finishCall / process command-stack / sleep
		if (tr_getFinished(tor)) {
			// finishCall
			if (finishCall != NULL)
				system(finishCall);
		}

	} /* main-loop */

	// shutdown
	tf_torrentStop(h, info);

/*
 * cleanup
 */
cleanup:
	tr_torrentClose(h, tor);
	tr_close(h);
	return EXIT_SUCCESS;

/*
 * failed
 */
failed:
	tr_torrentClose(h, tor);
	tr_close(h);
	return EXIT_FAILURE;

} // end main

/*******************************************************************************
 * tf_showInfo
 ******************************************************************************/
static void tf_showInfo(void) {

	// vars
	int i;
	tr_info_t * info;
	tr_stat_t * s;

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
			s->tracker->address, s->tracker->port);
	printf("announce: %s\n", s->tracker->announce);
	printf("size:     %"PRIu64" (%"PRIu64" * %d + %"PRIu64")\n",
		info->totalSize, info->totalSize / info->pieceSize,
		info->pieceSize, info->totalSize % info->pieceSize);
	if (info->comment[0])
		printf("comment:  %s\n", info->comment);
	if (info->creator[0])
		printf("creator:  %s\n", info->creator);
	if (TR_FLAG_PRIVATE & info->flags)
		printf("private flag set\n");
	printf("file(s):\n");
	for (i = 0; i < info->fileCount; i++)
		printf(" %s (%"PRIu64")\n", info->files[i].name,
			info->files[i].length);
}

/*******************************************************************************
 * tf_showScrape
 ******************************************************************************/
static void tf_showScrape(void) {
	int seeders, leechers, downloaded;
	if (tr_torrentScrape(tor, &seeders, &leechers, &downloaded))
		printf("Scrape failed.\n");
	else
		printf("%d seeder(s), %d leecher(s), %d download(s).\n",
				seeders, leechers, downloaded);
}

/*******************************************************************************
 * tf_torrentStop
 ******************************************************************************/
static void tf_torrentStop(tr_handle_t * h, tr_info_t * info) {

	// vars
	int i;
	tr_stat_t * s;
	tr_handle_status_t * hstat;
	double tf_sharing;
	char tf_eta[80];
	float progress;

	// print that we are going down
	tf_print(sprintf(tf_message, "transmission shutting down...\n"));

	// torrent-stat
	s = tr_torrentStat(tor);

	// sharing
	tf_sharing = (s->downloaded != 0)
		? (((double)(s->uploaded) / (double)(s->downloaded)) * 100)
		: (((double)(s->uploaded) / (double)(info->totalSize)) * 100);

	// write stat-file
	tf_stat_fp = fopen(tf_stat_file, "w+");
	if (tf_stat_fp != NULL) {
		if (s->status & TR_STATUS_SEED) {
			sprintf(tf_eta, "Download Succeeded!");
			progress = 100;
		} else {
			sprintf(tf_eta, "Torrent Stopped");
			progress = -(1 + s->progress) * 100;
		}
		fprintf(tf_stat_fp,
			"%d\n%.1f\n%s\n\n\n%s\n\n\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
			0,                /* State            */
			progress,         /* progress         */
			tf_eta,           /* State text       */
			                  /* download speed   */
			                  /* upload speed     */
			tf_owner,         /* owner            */
			                  /* seeds            */
			                  /* peers            */
			tf_sharing,       /* sharing          */
			tf_seedLimit,     /* seedlimit        */
			s->uploaded,      /* uploaded bytes   */
			s->downloaded,    /* downloaded bytes */
			info->totalSize); /* global size      */
		fclose(tf_stat_fp);
	} else {
		tf_print(sprintf(tf_message,
			"error opening stat-file for write : %s\n", tf_stat_file));
	}

	// Try for 5 seconds to delete any port mappings for nat traversal
	tr_natTraversalEnable(h, 0);
	for (i = 0; i < 10; i++) {
		hstat = tr_handleStatus(h);
		if (TR_NAT_TRAVERSAL_DISABLED == hstat->natTraversalStatus) {
			/* Port mappings were deleted */
			break;
		}
		usleep(500000);
	}

	// remove pid file
	tf_pidDelete();

	// print exit
	tf_print(sprintf(tf_message, "transmission exit.\n"));
}

/*******************************************************************************
 * tf_processCommandStack
 ******************************************************************************/
static int tf_processCommandStack(tr_handle_t *h) {

	// process command-file if exists
	tf_cmd_fp = NULL;
	tf_cmd_fp = fopen(tf_cmd_file, "r");

	// return
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

	// check for file-len
	if (fileLen > TF_CMDFILE_MAXLEN) {
		tf_print(sprintf(tf_message,
			"Size of command-file too big, skip. (max-size: %d)\n",
			TF_CMDFILE_MAXLEN));
		// remove file
		remove(tf_cmd_file);
		// return
		return 0;
	}

	// check if file contains "something"
	if (fileLen < 1) {
		tf_print(sprintf(tf_message,
			"No commands found in command-file.\n"));
		// remove file
		remove(tf_cmd_file);
		// return
		return 0;
	}

	// calloc buffer
	fileBuffer = calloc(fileLen + 1, sizeof(char));
	if (fileBuffer == NULL) {
		tf_print(sprintf(tf_message,
			"Error : not enough mem to read command-file\n"));
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
			if (tf_execCommand(h, currentLine)) {
				// free buffer
				free(fileBuffer);
				// return
				return 1;
			}
		}

	} // end file while loop

	// print if no commands found
	if (commandCount == 0)
		tf_print(sprintf(tf_message,
			"No commands found in command-file.\n"));

	// free buffer
	free(fileBuffer);

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

		// q
		case 'q':
			tf_print(sprintf(tf_message,
				"command: stop-request, setting shutdown-flag...\n"));
			return 1;

		// u
		case 'u':
			if (strlen(workload) < 1) {
				tf_print(sprintf(tf_message,
					"invalid rate.\n"));
				return 0;
			}
			uploadLimit = atoi(workload);
			tf_print(sprintf(tf_message,
				"command: setting upload-rate to %d...\n", uploadLimit));
			tr_setGlobalUploadLimit(h, uploadLimit);
			return 0;

		// d
		case 'd':
			if (strlen(workload) < 1) {
				tf_print(sprintf(tf_message,
					"invalid rate.\n"));
				return 0;
			}
			downloadLimit = atoi(workload);
			tf_print(sprintf(tf_message,
				"command: setting download-rate to %d...\n", downloadLimit));
			tr_setGlobalDownloadLimit(h, downloadLimit);
			return 0;

		// r
		case 'r':
			if (strlen(workload) < 1) {
				tf_print(sprintf(tf_message,
					"invalid runtime-code.\n"));
				return 0;
			}
			switch (workload[0]) {
				case '0':
					tf_print(sprintf(tf_message,
						"command: command: setting die-when-done to 0\n"));
					tf_dieWhenDone = 0;
					return 0;
				case '1':
					tf_print(sprintf(tf_message,
						"command: command: setting die-when-done to 1\n"));
					tf_dieWhenDone = 1;
					return 0;
				default:
					tf_print(sprintf(tf_message,
						"runtime-code unknown: %c\n", workload[0]));
					return 0;
			}
			return 0;

		// s
		case 's':
			if (strlen(workload) < 1) {
				tf_print(sprintf(tf_message,
					"invalid sharekill.\n"));
				return 0;
			}
			tf_print(sprintf(tf_message,
				"command: setting sharekill to %d...\n", atoi(workload)));
			tf_seedLimit = atoi(workload);
			return 0;

		// default
		default:
			tf_print(sprintf(tf_message,
				"op-code unknown: %c\n", opcode));
			return 0;

	}
	// return
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
			"Error : tf_initializeStatusFacility: not enough mem for malloc\n"));
		// return
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
	// return
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
			"Error : tf_initializeCommandFacility: not enough mem for malloc\n"));
		// return
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
		tf_print(sprintf(tf_message,
			"removing command-file %s...\n", tf_cmd_file));
		// remove file
		remove(tf_cmd_file);
		// null pointer
		tf_cmd_fp = NULL;
	}

	// return
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
			"wrote pid-file : %s (%d)\n",
			tf_pid_file , currentPid));
		// return
		return 1;
	} else {
		tf_print(sprintf(tf_message,
			"error opening pid-file for write : %s (%d)\n",
			tf_pid_file , currentPid));
		// return
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

	// remove file
	remove(tf_pid_file);

	// return
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

	// return
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

	// signal-switch
	switch (signal) {

		// HUP
		case SIGHUP:
			tf_print(sprintf(tf_message,
				"got SIGHUP, ignoring...\n"));
			break;

		// INT
		case SIGINT:
			tf_print(sprintf(tf_message,
				"got SIGINT, setting shutdown-flag...\n"));
			gotsig = 1;
			break;

		// TERM
		case SIGTERM:
			tf_print(sprintf(tf_message,
				"got SIGTERM, setting shutdown-flag...\n"));
			gotsig = 1;
			break;

		// QUIT
		case SIGQUIT:
			tf_print(sprintf(tf_message,
				"got SIGQUIT, setting shutdown-flag...\n"));
			gotsig = 1;
			break;
	}
}

/*******************************************************************************
 * parseCommandLine
 ******************************************************************************/
static int parseCommandLine(int argc, char ** argv) {
	for (;;) {
		static struct option long_options[] =
		{ { "help",               no_argument,       NULL, 'h' },
		  { "info",               no_argument,       NULL, 'i' },
		  { "scrape",             no_argument,       NULL, 's' },
		  { "verbose",            required_argument, NULL, 'v' },
		  { "port",               required_argument, NULL, 'p' },
		  { "upload",             required_argument, NULL, 'u' },
		  { "download",           required_argument, NULL, 'd' },
		  { "finish",             required_argument, NULL, 'f' },
		  { "die-when-done",      required_argument, NULL, 'r' },
		  { "seedlimit",          required_argument, NULL, 'c' },
		  { "display-interval",   required_argument, NULL, 'e' },
		  { "owner",              required_argument, NULL, 'o' },
		  { "nat-traversal",      no_argument,       NULL, 'n' },
		  { 0, 0, 0, 0} };
		int c, optind = 0;
		c = getopt_long(argc, argv,
			"hisv:p:u:d:f:r:c:e:o:n", long_options, &optind);
		if (c < 0)
			break;
		switch (c) {
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
			case 'r':
				tf_dieWhenDone = atoi(optarg);
				break;
			case 'c':
				tf_seedLimit = atoi(optarg);
				break;
			case 'e':
				tf_displayInterval = atoi(optarg);
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
