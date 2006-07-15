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
 * Torrentflux integration history :
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <getopt.h>
#include <signal.h>
#include <transmission.h>
#ifdef SYS_BEOS
#include <kernel/OS.h>
#define usleep snooze
#endif

/* b4rt */
#define USAGE \
"Usage: %s [options] file.torrent [options]\n\n" \
"Options:\n" \
"  -h, --help                     Print this help and exit\n" \
"  -i, --info                     Print metainfo and exit\n" \
"  -s, --scrape                   Print counts of seeders/leechers and exit\n" \
"  -v, --verbose <int>            Verbose level (0 to 2, default = 0)\n" \
"  -p, --port <int>               Port we should listen on (default = %d)\n" \
"  -u, --upload <int>             Maximum upload rate \n" \
"                                 (std : -1 = no limit, default = 20)\n" \
"                                 (tf  : -1|0 = no limit, -2 = null, default = 20)\n" \
"  -d, --download <int>           Maximum download rate \n" \
"                                 (std : -1 = no limit, default = -1)\n" \
"                                 (tf  : -1|0 = no limit, -2 = null, default = -1)\n" \
"  -c, --seedlimit <int>          Seed to reach before exiting transmission\n" \
"                                 (0 = seed forever -1 = no seeding)\n" \
"  -f, --finish <shell script>    Command you wish to run on completion\n" \
"  -e, --display_interval <int>   Time between updates of displayed information\n" \
"  -t, --torrentflux <file>       Name of the stat file shared with torrentflux\n" \
"  -w, --torrentflux-owner <file> Name of the TF owner of the torrent\n" \
"  -z, --pid <file>               File containing PID of transmission\n" \
"\n"

static int             showHelp      = 0;
static int             showInfo      = 0;
static int             showScrape    = 0;
static int             verboseLevel  = 0;
static int             bindPort      = TR_DEFAULT_PORT;
static int             uploadLimit   = 20;
static int             downloadLimit = -1;
static char            * torrentPath = NULL;
static volatile char   mustDie       = 0;
static int             seedLimit = 0;
static int             displayInterval = 1;

/* Changes for torrentflux integration by Sylver */
static char          * tf_stat_file = NULL;
static FILE          * tf_stat      = NULL;
static char          * tf_user      = NULL;
static char          * tf_pid       = NULL;
/* End torrentflux integration */

static char            * finishCall   = NULL;

static int  parseCommandLine ( int argc, char ** argv );
static void sigHandler       ( int signal );

int main( int argc, char ** argv )
{
    int i, error;
    tr_handle_t  * h;
    tr_torrent_t * tor;
    tr_stat_t    * s;
    double tf_sharing=0.0;
    char   tf_string[80];

	/* b4rt */
    int tf_seeders, tf_leechers;

    /* Get options */
    if( parseCommandLine( argc, argv ) )
    {
		/* b4rt */
		printf( "\nTransmission %s [%d] - TorrentFlux-cli [%d]\nhttp://transmission.m0k.org/ - http://www.torrentflux.com/\n\n",
		VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);

        printf( USAGE, argv[0], TR_DEFAULT_PORT );
        return 1;
    }

	/* show help */
    if( showHelp )
    {
		/* b4rt */
		printf( "\nTransmission %s [%d] - TorrentFlux-cli [%d]\nhttp://transmission.m0k.org/ - http://www.torrentflux.com/\n\n",
		VERSION_STRING, VERSION_REVISION, VERSION_REVISION_CLI);

		printf( USAGE, argv[0], TR_DEFAULT_PORT );
		return 0;
    }

    if( verboseLevel < 0 )
    {
        verboseLevel = 0;
    }
    else if( verboseLevel > 9 )
    {
        verboseLevel = 9;
    }
    if( verboseLevel )
    {
        static char env[11];
        sprintf( env, "TR_DEBUG=%d", verboseLevel );
        putenv( env );
    }

    if( bindPort < 1 || bindPort > 65535 )
    {
        printf( "Invalid port '%d'\n", bindPort );
        return 1;
    }

    /* Initialize libtransmission */
    h = tr_init();

    /* Open and parse torrent file */
	/* b4rt */
    if( !( tor = tr_torrentInit( h, torrentPath, 0, &error ) ) )
    {
        printf( "Failed opening torrent file `%s'\n", torrentPath );
        goto failed;
    }

	/* show info */
    if( showInfo )
    {
        tr_info_t * info = tr_torrentInfo( tor );

        /* Print torrent info (quite à la btshowmetainfo) */
        printf( "hash:     " );
        for( i = 0; i < SHA_DIGEST_LENGTH; i++ )
        {
            printf( "%02x", info->hash[i] );
        }
        printf( "\n" );
        printf( "tracker:  %s:%d\n",
                info->trackerAddress, info->trackerPort );
        printf( "announce: %s\n", info->trackerAnnounce );
        printf( "size:     %"PRIu64" (%"PRIu64" * %d + %"PRIu64")\n",
                info->totalSize, info->totalSize / info->pieceSize,
                info->pieceSize, info->totalSize % info->pieceSize );
        printf( "file(s):\n" );
        for( i = 0; i < info->fileCount; i++ )
        {
            printf( " %s (%"PRIu64")\n", info->files[i].name,
                    info->files[i].length );
        }

        goto cleanup;
    }

	/* show scrape */
    if( showScrape )
    {
        int seeders, leechers;

        if( tr_torrentScrape( tor, &seeders, &leechers ) )
        {
            printf( "Scrape failed.\n" );
        }
        else
        {
            printf( "%d seeder(s), %d leecher(s).\n", seeders, leechers );
        }

        goto cleanup;
    }

    signal( SIGINT, sigHandler );

    /* b4rt : modified arg-conversion */
	/* If running torrentflux, Download limit = 0 means no limit */
    if (tf_stat_file != NULL) {
        // up
        switch(uploadLimit)
        {
            case 0:
                uploadLimit = -1;
                break;
            case -2:
                uploadLimit = 0;
                break;
        }
        // down
        switch(downloadLimit)
        {
            case 0:
                downloadLimit = -1;
                break;
            case -2:
                downloadLimit = 0;
                break;
        }
    }

    /* Create PID file if wanted by user */
    if (tf_pid != NULL)
    {
        FILE * pid_file;
        pid_file = fopen(tf_pid, "w+");
        if ( pid_file != NULL)
        {
            fprintf( pid_file, "%d", getpid());
            fclose ( pid_file );
        }
    }

    tr_setBindPort( h, bindPort );
    tr_setUploadLimit( h, uploadLimit );
    tr_setDownloadLimit( h, downloadLimit );

    tr_torrentSetFolder( tor, "." );
    tr_torrentStart( tor );

    while( !mustDie )
    {
        char   string[80];
        int    chars = 0;
        int    result;
        int    stat_state;

        sleep( displayInterval );

        /* Check if we must stop */
        if(tf_stat_file != NULL)
        {
            tf_stat = fopen(tf_stat_file, "r");
            if (tf_stat != NULL)
            {
                /* Get state */
                stat_state=fgetc(tf_stat);

                /* Close the file */
                fclose(tf_stat);

                /* Torrentflux asked to shutdown the torrent */
                if (stat_state == '0')
                {
                    mustDie = 1;
                }
            }
        }

        s = tr_torrentStat( tor );

        if( s->status & TR_STATUS_CHECK )
        {
            chars = snprintf( string, 80,
                "Checking files... %.2f %%", 100.0 * s->progress );
            /* Changes for torrentflux integration by Sylver */
            if(tf_stat_file != NULL)
            {
                tr_info_t * info = tr_torrentInfo( tor );

                tf_stat = fopen(tf_stat_file, "w+");
                if (tf_stat != NULL)
                {
                    fprintf(tf_stat, "%d\n%.1f\n%s\n0 kB/s\n0 kB/s\n%s\n0\n0\n0.0\n%d\n0\n%" PRIu64 "\n%" PRIu64,
                            1,                          /* State             */
                            100.0 * s->progress,        /* checking progress */
                            "Checking existing data",   /* State text        */
                                                        /* download speed    */
                                                        /* upload speed      */
                            tf_user,                    /* user              */
                                                        /* seeds             */
                                                        /* peers             */
                                                        /* sharing           */
                            seedLimit,                  /* seedlimit         */
                                                        /* uploaded bytes    */
                            s->downloaded,              /* downloaded bytes  */
                            info->totalSize);           /* global size       */
                    fclose(tf_stat);
                }
            }
            /* End torrentflux integration */
        }
        else if( s->status & TR_STATUS_DOWNLOAD )
        {
            chars = snprintf( string, 80,
                "Progress: %.2f %%, %d peer%s, dl from %d (%.2f KB/s), "
                "ul to %d (%.2f KB/s)", 100.0 * s->progress,
                s->peersTotal, ( s->peersTotal == 1 ) ? "" : "s",
                s->peersUploading, s->rateDownload,
                s->peersDownloading, s->rateUpload );
            /* Changes for torrentflux integration by Sylver */
            if(tf_stat_file != NULL)
            {

                if (s->downloaded != 0)
                {
                    tf_sharing = ((double)(s->uploaded) / (double)(s->downloaded))*100;
                }

                if (s->eta != -1)
                {
                    if ((s->eta/(24*60*60))!=0)
                    {
                        sprintf (tf_string,"%d day %02d hour %02d min %02d sec",s->eta/(24*60*60),
                                                                                ((s->eta)%(24*60*60))/(60*60),
                                                                                ((s->eta)%(60*60)/60),
                                                                                s->eta%60);
                    }
                    else if ((s->eta/(60*60)) != 0)
                    {
                        sprintf (tf_string,"%d hour %02d min %02d sec",(s->eta)/(60*60),
                                                                       ((s->eta)%(60*60)/60),
                                                                       s->eta%60);
                    }
                    else
                    {
                        sprintf (tf_string,"%d min %02d sec",(s->eta)/60,s->eta%60);
                    }
                }
                else
                {
                    sprintf (tf_string,"&#8734"); /* &#8734 = infinite symbol in html */
                }

				/* b4rt */
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

                if ((s->seeders == -1) && (s->peersTotal == 0))
                {
                    sprintf (tf_string,"Connecting to Peers");
                }

                tf_stat = fopen(tf_stat_file, "w+");
                if (tf_stat != NULL)
                {
                    tr_info_t * info = tr_torrentInfo( tor );
					/* b4rt */
					fprintf(tf_stat, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
                            1,                                  /* State                */
                            100.0 * s->progress,                /* downloading progress */
                            tf_string,                          /* Estimated time       */
                            s->rateDownload,                    /* download speed       */
                            s->rateUpload,                      /* upload speed         */
                            tf_user,                            /* user                 */
                            s->peersUploading, tf_seeders,      /* seeds                */
                            s->peersDownloading, tf_leechers,   /* peers                */
                            tf_sharing,                         /* sharing              */
                            seedLimit,                          /* seedlimit            */
                            s->uploaded,                        /* uploaded bytes       */
                            s->downloaded,                      /* downloaded bytes     */
                            info->totalSize);                   /* global size          */
                    fclose(tf_stat);
                }
            }
            /* End torrentflux integration */
        }
        else if( s->status & TR_STATUS_SEED )
        {
            tr_info_t * info = tr_torrentInfo( tor );

            chars = snprintf( string, 80,
                "Seeding, uploading to %d of %d peer(s), %.2f KB/s",
                s->peersDownloading, s->peersTotal,
                s->rateUpload );

            if (s->downloaded != 0)
            {
                tf_sharing = ((double)(s->uploaded) / (double)(s->downloaded))*100;
            }
            else
            {
                tf_sharing = ((double)(s->uploaded) / (double)(info->totalSize))*100;
            }

            /* If we reached the seeding limit, we have to quit transmission */
            if ((seedLimit != 0) &&
               ((tf_sharing > (double)(seedLimit)) || (seedLimit == -1)))
            {
                mustDie = 1;
            }

			/* b4rt */
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

            /* Changes for torrentflux integration by Sylver */
            if(tf_stat_file != NULL)
            {
                tf_stat = fopen(tf_stat_file, "w+");
				/* b4rt */
				fprintf(tf_stat, "%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
                        1,                                  /* State            */
                        100.0 * s->progress,                /* progress         */
                        "Download Succeeded!",              /* State text       */
                        s->rateDownload,                    /* download speed   */
                        s->rateUpload,                      /* upload speed     */
                        tf_user,                            /* user             */
						s->peersUploading, tf_seeders,      /* seeds			*/
						s->peersDownloading, tf_leechers,   /* peers			*/
                        tf_sharing,                         /* sharing          */
                        seedLimit,                          /* seedlimit        */
                        s->uploaded,                        /* uploaded bytes   */
                        s->downloaded,                      /* downloaded bytes */
                        info->totalSize);                   /* global size      */
                fclose(tf_stat);
            }
            /* End torrentflux integration */
        }
        memset( &string[chars], ' ', 79 - chars );
        string[79] = '\0';
        fprintf( stderr, "\r%s", string );

        if( s->error & TR_ETRACKER )
        {
            fprintf( stderr, "\n%s\n", s->trackerError );
        }
        else if( verboseLevel > 0 )
        {
            fprintf( stderr, "\n" );
        }

        if( tr_getFinished( tor ) )
        {
            result = system(finishCall);
        }
    }
    fprintf( stderr, "\n" );

    /* Changes for torrentflux integration by Sylver */
    /* Mark the torrent as beeing stopped */
    if(tf_stat_file != NULL)
    {
        tr_info_t * info = tr_torrentInfo( tor );

        if (s->downloaded != 0)
        {
            tf_sharing = ((double)(s->uploaded) / (double)(s->downloaded))*100;
        }
        else
        {
            tf_sharing = ((double)(s->uploaded) / (double)(info->totalSize))*100;
        }

        tf_stat = fopen(tf_stat_file, "w+");
        if (tf_stat != NULL)
        {
            float progress;
            if( s->status & TR_STATUS_SEED )
            {
                sprintf(tf_string,"Download Succeeded!");
                progress = 100;
            }
            else
            {
                sprintf(tf_string,"Torrent Stopped");
                progress = -(1 + s->progress) * 100;
            }

            fprintf(tf_stat, "%d\n%.1f\n%s\n\n\n%s\n\n\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
                    0,                          /* State            */
                    progress,                   /* progress         */
                    tf_string,                  /* State text       */
                                                /* download speed   */
                                                /* upload speed     */
                    tf_user,                    /* user             */
                                                /* seeds            */
                                                /* peers            */
                    tf_sharing,                 /* sharing          */
                    seedLimit,                  /* seedlimit        */
                    s->uploaded,                /* uploaded bytes   */
                    s->downloaded,              /* downloaded bytes */
                    info->totalSize);           /* global size      */
            fclose(tf_stat);
        }
    }
    /* End torrentflux integration */

    /* Remove PID file if created ! */
    if (tf_pid != NULL)
    {
        remove(tf_pid);
    }

    /* Try for 5 seconds to notice the tracker that we are leaving */
    tr_torrentStop( tor );
    for( i = 0; i < 10; i++ )
    {
        s = tr_torrentStat( tor );
        if( s->status & TR_STATUS_PAUSE )
        {
            /* The 'stopped' message was sent */
            break;
        }
        usleep( 500000 );
    }

cleanup:
    tr_torrentClose( h, tor );

failed:
    tr_close( h );

    return 0;
}

static int parseCommandLine( int argc, char ** argv )
{
    for( ;; )
    {
        static struct option long_options[] =
          { { "help",               no_argument,       NULL, 'h' },
            { "info",               no_argument,       NULL, 'i' },
            { "scrape",             no_argument,       NULL, 's' },
            { "verbose",            required_argument, NULL, 'v' },
            { "port",               required_argument, NULL, 'p' },
            { "upload",             required_argument, NULL, 'u' },
            { "download",           required_argument, NULL, 'd' },
            { "seedlimit",          required_argument, NULL, 'c' },
            { "finish",             required_argument, NULL, 'f' },
            { "display_interval",   required_argument, NULL, 'e' },
            { "torrentflux",        required_argument, NULL, 't' },
            { "torrentflux-owner",  required_argument, NULL, 'w' },
            { "pid",                required_argument, NULL, 'z' },
            { 0, 0, 0, 0} };

        int c, optind = 0;
        c = getopt_long( argc, argv, "hisv:p:u:d:c:f:e:t:w:z:", long_options, &optind );
        if( c < 0 )
        {
            break;
        }
        switch( c )
        {
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
                verboseLevel = atoi( optarg );
                break;
            case 'p':
                bindPort = atoi( optarg );
                break;
            case 'u':
                uploadLimit = atoi( optarg );
                break;
            case 'd':
                downloadLimit = atoi( optarg );
                break;
            case 'c':
                seedLimit = atoi( optarg );
                break;
            case 'f':
                finishCall = optarg;
                break;
            case 'e':
                displayInterval = atoi( optarg );
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

    if( optind > argc - 1  )
    {
        return !showHelp;
    }

    torrentPath = argv[optind];

    return 0;
}

static void sigHandler( int signal )
{
    switch( signal )
    {
        case SIGINT:
            mustDie = 1;
            break;

        default:
            break;
    }
}
