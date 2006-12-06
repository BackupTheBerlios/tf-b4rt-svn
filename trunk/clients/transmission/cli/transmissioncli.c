/*******************************************************************************
 * $Id$
 *
 * Copyright (c) 2005-2006 Transmission authors and contributors
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
 * Torrentflux integration history :
 *
 * 16/07/06 : b4rt   - changes due to move to berliOS. last history-entry here,
 *                     check svn-log on berliOS-svn from now on.
 * 15/07/06 : b4rt   - changes due to move to svn.
 * 08/07/06 : b4rt   - synced changes of official transmissioncli (r163-r310)
 *                   - changed statfile-output-format for "seeds" and "peers"
 *                     to have "tflux-format" (0) for "no seeds" and "no peers"
 *                     as transmission uses "-1" in that case.
 *                   - only print out version-info-string in usage+arg-error.
 * 03/07/06 : b4rt   - changes in statfile-output for "seeds" and "peers"
 * 02/07/06 : b4rt   - change to work with transmission 0.6.x codebase
 *                     (function tr_torrentInit has new argument)
 * 22/05/06 : Sylver - corrected output file when exiting transmission
 *                     (when download is not finished)
 *                   - revert default download speed back to 20 kb/s
 *                     (no need to change as torrenflux give wanted speed)
 * 22/05/06 : b4rt   - minor output-things. (just cosmetics~)
 *                   - standard-upload = 10 (like tornado)
 *                   - modified arg-conversion :
 *                     ~ applies for upload and download
 *                     ~ if user really wants to have a 0-arg (zero) he can
 *                       pass -2.
 * 21/05/06 : Sylver - When running torrentflux, download rate = 0 is
 *                     converted to -1 (no limit)
 *                   - option -z (--pid) added to log the PID in the
 *                     specified file.
 * 17/05/06 : Sylver - Corrected a bug causing segfault under FreeBSD
 *                     (was trying to close a file that wasn't open)
 ******************************************************************************/

/* defines */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <getopt.h>
#include <signal.h>
#include <transmission.h>
#include <sys/types.h>
#ifdef SYS_BEOS
#include <kernel/OS.h>
#define usleep snooze
#endif
#define USAGE \
"Usage: %s [options] file.torrent [options]\n\n" \
"Options:\n" \
"  -h, --help                     Print this help and exit\n" \
"  -i, --info                     Print metainfo and exit\n" \
"  -s, --scrape                   Print counts of seeders/leechers and exit\n" \
"  -v, --verbose <int>            Verbose level (0 to 2, default = 0)\n" \
"  -n, --nat-traversal            Attempt NAT traversal using NAT-PMP or UPnP IGD\n" \
"  -p, --port <int>               Port we should listen on (default = %d)\n" \
"  -u, --upload <int>             Maximum upload rate \n" \
"                                 (std : -1 = no limit, default = 20)\n" \
"                                 (tf  : -1|0 = no limit, -2 = null, default = 20)\n" \
"  -d, --download <int>           Maximum download rate \n" \
"                                 (std : -1 = no limit, default = -1)\n" \
"                                 (tf  : -1|0 = no limit, -2 = null, default = -1)\n" \
"  -f, --finish <shell script>    Command you wish to run on completion\n" \
"  -c, --seedlimit <int>          Seed to reach before exiting transmission\n" \
"                                 (0 = seed forever -1 = no seeding)\n" \
"  -e, --display_interval <int>   Time between updates of displayed information\n" \
"  -t, --torrentflux <file>       Name of the stat file shared with torrentflux\n" \
"  -w, --torrentflux-owner <file> Name of the TF owner of the torrent\n" \
"  -z, --pid <file>               File containing PID of transmission\n" \
"\n"

/* fields */
static int showHelp = 0;
static int showInfo = 0;
static int showScrape = 0;
static int verboseLevel = 0;
static int bindPort = TR_DEFAULT_PORT;
static int uploadLimit = 20;
static int downloadLimit = -1;
static char * torrentPath = NULL;
static volatile char mustDie = 0;
static int natTraversal = 0;
static int seedLimit = 0;
static int displayInterval = 1;
static char * finishCall = NULL;
// tfCLI
static char * tf_stat_file = NULL;
static FILE * tf_stat = NULL;
static char * tf_user = NULL;
static char * tf_pid = NULL;

/* functions */
static int parseCommandLine(int argc, char ** argv);
static void sigHandler(int signal);
static void fprintTimestamp(void);

/*******************************************************************************
 * main
 ******************************************************************************/
int main(int argc, char ** argv) {

	// vars
	int i, error, nat;
	tr_handle_t * h;
	tr_torrent_t * tor;
	tr_stat_t * s;
	double tf_sharing = 0.0;
	char tf_string[80];
	int tf_seeders, tf_leechers;

	/* Get options */
	if (parseCommandLine(argc, argv)) {
		printf("Transmission %s [%d] - tfCLI [%d]\nhttp://transmission.m0k.org/ - http://tf-b4rt.berlios.de/\n\n",
			VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0], TR_DEFAULT_PORT);
		return 1;
	}

	/* show help */
	if (showHelp) {
		printf("Transmission %s [%d] - tfCLI [%d]\nhttp://transmission.m0k.org/ - http://tf-b4rt.berlios.de/\n\n",
			VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);
		printf(USAGE, argv[0], TR_DEFAULT_PORT);
		return 0;
	}

	// verbose
	if (verboseLevel < 0) {
		verboseLevel = 0;
	} else if (verboseLevel > 9) {
		verboseLevel = 9;
	}
	if (verboseLevel) {
		static char env[11];
		sprintf(env, "TR_DEBUG=%d", verboseLevel);
		putenv(env);
	}

	// check port
	if (bindPort < 1 || bindPort > 65535) {
		printf("Invalid port '%d'\n", bindPort);
		return 1;
	}

	// Initialize libtransmission
	h = tr_init();

	// Open and parse torrent file
	if (!(tor = tr_torrentInit(h, torrentPath, 0, &error))) {
		printf("Failed opening torrent file `%s'\n", torrentPath);
		goto failed;
	}

	/* show info */
	if (showInfo) {
		// info
		tr_info_t * info = tr_torrentInfo(tor);
		// Print torrent info (quite � la btshowmetainfo)
		printf("hash:     ");
		for (i = 0; i < SHA_DIGEST_LENGTH; i++) {
			printf("%02x", info->hash[i]);
		}
		printf("\n");
		printf("tracker:  %s:%d\n", info->trackerAddress, info->trackerPort);
		printf("announce: %s\n", info->trackerAnnounce);
		printf("size:     %"PRIu64" (%"PRIu64" * %d + %"PRIu64")\n",
			info->totalSize, info->totalSize / info->pieceSize,
			info->pieceSize, info->totalSize % info->pieceSize);
		if (info->comment[0]) {
			printf("comment:  %s\n", info->comment);
		}
		if (info->creator[0]) {
			printf("creator:  %s\n", info->creator);
		}
		printf("file(s):\n");
		for (i = 0; i < info->fileCount; i++) {
			printf(" %s (%"PRIu64")\n", info->files[i].name,
				info->files[i].length);
		}
		// cleanup
		goto cleanup;
	}

	/* show scrape */
	if (showScrape) {
		int seeders, leechers, downloaded;
		if (tr_torrentScrape(tor, &seeders, &leechers, &downloaded)) {
			printf("Scrape failed.\n");
		} else {
			printf("%d seeder(s), %d leecher(s), %d download(s).\n",
					seeders, leechers, downloaded);
		}
		// cleanup
		goto cleanup;
	}

	/* start up transmission */

	// If running torrentflux, Download limit = 0 means no limit
	if (tf_stat_file != NULL) { /* tfCLI */
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
	}

	// print what we are starting up /* tfCLI */
	if ((tf_user != NULL) && (tf_stat_file != NULL) && (tf_pid != NULL)) {
		fprintTimestamp();
		fprintf(stderr, "transmission starting up :\n");
		fprintf(stderr, " - torrentPath : %s\n", torrentPath);
		fprintf(stderr, " - tf_user : %s\n", tf_user);
		fprintf(stderr, " - tf_stat_file : %s\n", tf_stat_file);
		fprintf(stderr, " - tf_pid : %s\n", tf_pid);
		fprintf(stderr, " - seedLimit : %d\n", seedLimit);
		fprintf(stderr, " - bindPort : %d\n", bindPort);
		fprintf(stderr, " - uploadLimit : %d\n", uploadLimit);
		fprintf(stderr, " - downloadLimit : %d\n", downloadLimit);
		fprintf(stderr, " - natTraversal : %d\n", natTraversal);
		if (finishCall != NULL) {
			fprintf(stderr, " - finishCall : %s\n", finishCall);
		}
	}

	// Create PID file if wanted by user
	if (tf_pid != NULL) {
		pid_t currentPid = getpid();
		FILE * pidFile;
		pidFile = fopen(tf_pid, "w+");
		if (pidFile != NULL) {
			fprintf(pidFile, "%d", currentPid);
			fclose(pidFile);
			fprintTimestamp();
			fprintf(stderr, "wrote pid-file : %s (%d)\n" ,tf_pid , currentPid);
		} else {
			fprintTimestamp();
			fprintf(stderr, "error opening pid-file for write : %s (%d)\n" ,
				tf_pid ,
				currentPid);
		}
	}

	// signal
	signal(SIGINT, sigHandler);

	// init some things
	tr_setBindPort(h, bindPort);
	tr_setUploadLimit(h, uploadLimit);
	tr_setDownloadLimit(h, downloadLimit);

	// nat-traversal
	if (natTraversal) {
		tr_natTraversalEnable(h);
	} else {
		tr_natTraversalDisable(h);
	}

	// set folder
	tr_torrentSetFolder(tor, ".");

	// start the torrent
	tr_torrentStart(tor);

	// print that we are done with startup
	if (tf_stat_file != NULL) {
		fprintTimestamp();
		fprintf(stderr, "transmission up and running.\n");
	}

	/* main-loop */
	while (!mustDie) {

		// status-string
		char string[80];
		int chars = 0;

		// result
		int result;

		// sleep
		sleep(displayInterval);

		// Check if we must stop
		if (tf_stat_file != NULL) { /* tfCLI */
			tf_stat = fopen(tf_stat_file, "r");
			if (tf_stat != NULL) {
				// stat-state
				int stat_state = 1;
				// Get state
				stat_state = fgetc(tf_stat);
				// Close the file
				fclose(tf_stat);
				// Torrentflux asked to shutdown the torrent, set flag
				if (stat_state == '0') {
					mustDie = 1;
				}
			} else {
				fprintTimestamp();
				fprintf(stderr, "error opening stat-file for read : %s\n",
					tf_stat_file);
			}
		}

		// torrent-stat
		s = tr_torrentStat(tor);

		if (s->status & TR_STATUS_CHECK) { /* --- CHECK --- */

			if (tf_stat_file == NULL) { /* standalone */

				// status-string
				chars = snprintf(string, 80,
					"Checking files... %.2f %%", 100.0 * s->progress );

			} else { /* tfCLI */

				// write tf-stat-file
				tr_info_t * info = tr_torrentInfo(tor);
				tf_stat = fopen(tf_stat_file, "w+");
				if (tf_stat != NULL) {
					fprintf(tf_stat, "%d\n%.1f\n%s\n0 kB/s\n0 kB/s\n%s\n0\n0\n0.0\n%d\n0\n%" PRIu64 "\n%" PRIu64,
						1,                        /* State             */
						100.0 * s->progress,      /* checking progress */
						"Checking existing data", /* State text        */
						                          /* download speed    */
						                          /* upload speed      */
						tf_user,                  /* user              */
						                          /* seeds             */
						                          /* peers             */
						                          /* sharing           */
						seedLimit,                /* seedlimit         */
						                          /* uploaded bytes    */
						s->downloaded,            /* downloaded bytes  */
						info->totalSize);         /* global size       */
					fclose(tf_stat);
				} else {
					fprintTimestamp();
					fprintf(stderr, "error opening stat-file for write : %s\n",
						tf_stat_file);
				}

			}

		} else if (s->status & TR_STATUS_DOWNLOAD) { /* --- DOWNLOAD --- */

			if (tf_stat_file == NULL) { /* standalone */

				// status-string
				chars = snprintf(string, 80,
					"Progress: %.2f %%, %d peer%s, dl from %d (%.2f KB/s), "
					"ul to %d (%.2f KB/s)", 100.0 * s->progress,
					s->peersTotal, ( s->peersTotal == 1 ) ? "" : "s",
					s->peersUploading, s->rateDownload,
					s->peersDownloading, s->rateUpload);

			} else { /* tfCLI */

				// sharing
				if (s->downloaded != 0) {
					tf_sharing =
						((double)(s->uploaded) / (double)(s->downloaded)) * 100;
				}

				// seeders + leechers
				if (s->seeders < 0) {
					tf_seeders = 0;
				} else {
					tf_seeders = s->seeders;
				}
				if (s->leechers < 0) {
					tf_leechers = 0;
				} else {
					tf_leechers = s->leechers;
				}

				// eta
				if (s->eta != -1) {
					// sanity-check. value of eta >= 7 days is not really of use
					if (s->eta < 604800) {
						if ((s->eta / (24 * 60 * 60)) != 0) {
							sprintf(tf_string,"%d:%02d:%02d:%02d",
								s->eta / (24 * 60 * 60),
								((s->eta) % (24 * 60 * 60)) / (60 * 60),
								((s->eta) % (60 * 60) / 60),
								s->eta % 60);
						} else if ((s->eta / (60 * 60)) != 0) {
							sprintf(tf_string, "%d:%02d:%02d",
								(s->eta) / (60 * 60),
								((s->eta) % (60 * 60) / 60),
								s->eta % 60);
						} else {
							sprintf(tf_string, "%d:%02d",
								(s->eta) / 60, s->eta % 60);
						}
					} else {
						sprintf(tf_string,"-");
					}
				} else {
					sprintf(tf_string,"-");
				}
				if ((s->seeders == -1) && (s->peersTotal == 0)) {
					sprintf(tf_string,"Connecting to Peers");
				}

				// write tf-stat-file
				tf_stat = fopen(tf_stat_file, "w+");
				if (tf_stat != NULL) {
					tr_info_t * info = tr_torrentInfo( tor );
					fprintf(tf_stat, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
						1,                                /* State            */
						100.0 * s->progress,              /* progress         */
						tf_string,                        /* Estimated time   */
						s->rateDownload,                  /* download speed   */
						s->rateUpload,                    /* upload speed     */
						tf_user,                          /* user             */
						s->peersUploading, tf_seeders,    /* seeds            */
						s->peersDownloading, tf_leechers, /* peers            */
						tf_sharing,                       /* sharing          */
						seedLimit,                        /* seedlimit        */
						s->uploaded,                      /* uploaded bytes   */
						s->downloaded,                    /* downloaded bytes */
						info->totalSize);                 /* global size      */
					fclose(tf_stat);
				} else {
					fprintTimestamp();
					fprintf(stderr, "error opening stat-file for write : %s\n",
						tf_stat_file);
				}
			}

		} else if (s->status & TR_STATUS_SEED) { /* --- SEED --- */

			// info
			tr_info_t * info = tr_torrentInfo(tor);

			if (tf_stat_file == NULL) { /* standalone */

				// status-string
				chars = snprintf(string, 80,
					"Seeding, uploading to %d of %d peer(s), %.2f KB/s",
					s->peersDownloading, s->peersTotal,
					s->rateUpload);

			} else { /* tfCLI */

				// sharing
				if (s->downloaded != 0) {
					tf_sharing =
						((double)(s->uploaded) /
							(double)(s->downloaded)) * 100;
				} else {
					tf_sharing =
						((double)(s->uploaded) /
							(double)(info->totalSize)) * 100;
				}

				// die-on-seed-limit / die-when-done
				if (seedLimit == -1) {
					fprintTimestamp();
					fprintf(stderr,
						"die-when-done set, setting shutdown-flag...\n");
					mustDie = 1;
				} else if ((seedLimit != 0) &&
					(tf_sharing > (double)(seedLimit))) {
					fprintTimestamp();
					fprintf(stderr,
						"seed-limit %d reached, setting shutdown-flag...\n",
						seedLimit);
					mustDie = 1;
				}

				// seeders + leechers
				if (s->seeders < 0) {
					tf_seeders = 0;
				} else {
					tf_seeders = s->seeders;
				}
				if (s->leechers < 0) {
					tf_leechers = 0;
				} else {
					tf_leechers = s->leechers;
				}

				// write tf-stat-file
				tf_stat = fopen(tf_stat_file, "w+");
				if (tf_stat != NULL) {
					fprintf(tf_stat, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
						1,                                /* State            */
						100.0 * s->progress,              /* progress         */
						"Download Succeeded!",            /* State text       */
						s->rateDownload,                  /* download speed   */
						s->rateUpload,                    /* upload speed     */
						tf_user,                          /* user             */
						s->peersUploading, tf_seeders,    /* seeds            */
						s->peersDownloading, tf_leechers, /* peers            */
						tf_sharing,                       /* sharing          */
						seedLimit,                        /* seedlimit        */
						s->uploaded,                      /* uploaded bytes   */
						s->downloaded,                    /* downloaded bytes */
						info->totalSize);                 /* global size      */
					fclose(tf_stat);
				} else {
					fprintTimestamp();
					fprintf(stderr, "error opening stat-file for write : %s\n",
						tf_stat_file);
				}
			}

		}

		// status-string
		if (tf_stat_file == NULL) { /* standalone */
			memset( &string[chars], ' ', 79 - chars );
			string[79] = '\0';
			// print status to stderr
			fprintf(stderr, "\r%s", string);
		}

		// errors
		if (s->error & TR_ETRACKER) {
			if (tf_stat_file == NULL) { /* standalone */
				// print errors to stderr
				fprintf(stderr, "\n%s\n", s->trackerError);
			} else { /* tfCLI */
				// print errors to stderr
				fprintTimestamp();
				fprintf(stderr, "trackerError : %s\n", s->trackerError);
				// append errors to stat-file
				tf_stat = fopen(tf_stat_file, "a+");
				if (tf_stat != NULL) {
					fprintf(tf_stat, "\n%s\n", s->trackerError);
					fclose(tf_stat);
				}
			}
		} else if (verboseLevel > 0) {
			if (tf_stat_file == NULL) { /* standalone */
				// stderr
				fprintf(stderr, "\n");
			}
		}

		// finishCall
		if (tr_getFinished(tor)) {
			result = system(finishCall);
		}

	} /* main-loop */

	// print that we are going down
	if (tf_stat_file != NULL) { /* tfCLI */
		fprintTimestamp();
		fprintf(stderr, "transmission shutting down...\n");
	}

	// mark torrent as stopped in tf-stat-file
	if (tf_stat_file != NULL) { /* tfCLI */

		// info
		tr_info_t * info = tr_torrentInfo(tor);

		// sharing
		if (s->downloaded != 0) {
			tf_sharing =
				((double)(s->uploaded) / (double)(s->downloaded)) * 100;
		} else {
			tf_sharing =
				((double)(s->uploaded) / (double)(info->totalSize)) * 100;
		}

		// write tf-stat-file
		tf_stat = fopen(tf_stat_file, "w+");
		if (tf_stat != NULL) {
			float progress;
			if (s->status & TR_STATUS_SEED) {
				sprintf(tf_string,"Download Succeeded!");
				progress = 100;
			} else {
				sprintf(tf_string,"Torrent Stopped");
				progress = -(1 + s->progress) * 100;
			}
			fprintf(tf_stat, "%d\n%.1f\n%s\n\n\n%s\n\n\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
				0,                /* State            */
				progress,         /* progress         */
				tf_string,        /* State text       */
				                  /* download speed   */
				                  /* upload speed     */
				tf_user,          /* user             */
				                  /* seeds            */
                                  /* peers            */
				tf_sharing,       /* sharing          */
				seedLimit,        /* seedlimit        */
				s->uploaded,      /* uploaded bytes   */
				s->downloaded,    /* downloaded bytes */
				info->totalSize); /* global size      */
			fclose(tf_stat);
		} else {
			fprintTimestamp();
			fprintf(stderr, "error opening stat-file for write : %s\n",
				tf_stat_file);
		}
	}

	// stderr
	if (tf_stat_file == NULL) { /* standalone */
		fprintf(stderr, "\n");
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

	// Remove PID file if created !
	if (tf_pid != NULL) {
		fprintTimestamp();
		fprintf(stderr, "removing pid-file : %s\n", tf_pid);
		remove(tf_pid);
	}

	// print exit
	if (tf_stat_file != NULL) { /* tfCLI */
		fprintTimestamp();
		fprintf(stderr, "transmission exit.\n");
	}

cleanup:
	tr_torrentClose(h, tor);

failed:
	tr_close(h);

	return 0;
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
		  { "torrentflux",        required_argument, NULL, 't' },
		  { "torrentflux-owner",  required_argument, NULL, 'w' },
		  { "pid",                required_argument, NULL, 'z' },
		  { "nat-traversal",      no_argument,       NULL, 'n' },
		  { 0, 0, 0, 0} };
		int c, optind = 0;
		c = getopt_long(argc, argv,
			"hisv:p:u:d:f:c:e:t:w:z:n", long_options, &optind);
		if (c < 0) {
			break;
		}
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
			case 't':
				tf_stat_file = optarg;
				break;
			case 'w':
				tf_user = optarg;
				break;
			case 'z':
				tf_pid = optarg;
				break;
			default:
				return 1;
		}
	}
	if (optind > argc - 1) {
		return !showHelp;
	}
	torrentPath = argv[optind];
	return 0;
}

/*******************************************************************************
 * sigHandler
 ******************************************************************************/
static void sigHandler(int signal) {
	switch(signal) {
		case SIGINT:
			if (tf_stat_file != NULL) { /* tfCLI */
				fprintTimestamp();
				fprintf(stderr, "got SIGINT, setting shutdown-flag...\n");
			}
			mustDie = 1;
			break;
		default:
			break;
	}
}

/*******************************************************************************
 * fprintTimestamp
 ******************************************************************************/
static void fprintTimestamp(void) {
	time_t ct;
	struct tm * cts;
	time(&ct);
	cts = localtime(&ct);
	fprintf(stderr, "[%4d/%02d/%02d - %02d:%02d:%02d] ",
		cts->tm_year + 1900,
		cts->tm_mon + 1,
		cts->tm_mday,
		cts->tm_hour,
		cts->tm_min,
		cts->tm_sec
	);
}
