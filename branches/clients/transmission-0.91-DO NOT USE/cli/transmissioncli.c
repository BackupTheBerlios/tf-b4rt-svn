/******************************************************************************
 * $Id: transmissioncli.c 3486 2007-10-20 22:07:21Z charles $
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
 *****************************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <getopt.h>
#include <signal.h>

#include <libtransmission/transmission.h>
#include <libtransmission/makemeta.h>

#ifdef __BEOS__
    #include <kernel/OS.h>
    #define wait_msecs(N)  snooze( (N) * 1000 )
    #define wait_secs(N)   sleep( (N) )
#elif defined(WIN32)
    #include <windows.h>
    #define wait_msecs(N)  Sleep( (N) )
    #define wait_secs(N)   Sleep( (N) * 1000 )
#else
    #define wait_msecs(N)  usleep( (N) * 1000 )
    #define wait_secs(N)   sleep( (N) )
#endif

/* macro to shut up "unused parameter" warnings */
#ifdef __GNUC__
#define UNUSED                  __attribute__((unused))
#else
#define UNUSED
#endif

const char * USAGE =
"Usage: %s [options] file.torrent [options]\n\n"
"Options:\n"
"  -c, --create-from <file>  Create torrent from the specified source file.\n"
"  -a, --announce <url> Used in conjunction with -c.\n"
"  -r, --private        Used in conjunction with -c.\n"
"  -m, --comment <text> Adds an optional comment when creating a torrent.\n"
"  -d, --download <int> Maximum download rate (-1 = no limit, default = -1)\n"
"  -f, --finish <shell script> Command you wish to run on completion\n" 
"  -h, --help           Print this help and exit\n" 
"  -i, --info           Print metainfo and exit\n"
"  -n  --nat-traversal  Attempt NAT traversal using NAT-PMP or UPnP IGD\n"
"  -p, --port <int>     Port we should listen on (default = %d)\n"
#if 0
"  -s, --scrape         Print counts of seeders/leechers and exit\n"
#endif
"  -u, --upload <int>   Maximum upload rate (-1 = no limit, default = 20)\n"
"  -v, --verbose <int>  Verbose level (0 to 2, default = 0)\n"
"\nTorrentflux Commands:\n"
"  -e, --display-interval <int> Time between updates of stat-file (default = %d)\n"
"  -l, --seedlimit <int> Seed-Limit (Percent) to reach before shutdown\n"
"                        (0 = seed forever, -1 = no seeding, default = %d)\n"
"  -o, --owner <string> Name of the owner (default = 'n/a')\n"
"  -w, --die-when-done  Auto-Shutdown when done (0 = Off, 1 = On, default = %d)\n";

static int           showHelp      = 0;
static int           showInfo      = 0;
#if 0
static int           showScrape    = 0;
#endif
static int           isPrivate     = 0;
static int           verboseLevel  = 0;
static int           bindPort      = TR_DEFAULT_PORT;
static int           uploadLimit   = 20;
static int           downloadLimit = -1;
static char          * torrentPath = NULL;
static int           natTraversal  = 0;
static sig_atomic_t  gotsig        = 0;
static sig_atomic_t  manualUpdate  = 0;
static tr_torrent    * tor;

static char          * finishCall   = NULL;
static char          * announce     = NULL;
static char          * sourceFile   = NULL;
static char          * comment      = NULL;

/* Torrentflux -START- */
//static volatile char tf_shutdown = 0;
static int           TOF_dieWhenDone     = 0; 
static int           TOF_seedLimit       = 0;
static int           TOF_displayInterval = 5;

static char          * TOF_owner    = NULL;
static char          * TOF_statFile = NULL;
static FILE          * TOF_statFp   = NULL;
static char          * TOF_cmdFile  = NULL;
static FILE          * TOF_cmdFp    = NULL;
static char          * TOF_message  = NULL;
/* -END- */

static int  parseCommandLine ( int argc, char ** argv );
static void sigHandler       ( int signal );

/* Torrentflux -START- */
//static void tf_showInfo(void);
//#if 0
//static void tf_showScrape(void);
//#endif
//static void tf_torrentStop(tr_handle_t *h, const tr_info_t *info);

//static int tf_processCommandStack(tr_handle_t *h);
//static int tf_processCommandFile(tr_handle_t *h);
//static int tf_execCommand(tr_handle_t *h, char *s);

static void TOF_print(char *printmsg);
static int TOF_initStatus(void);
static void TOF_writeStatus( const tr_stat_t *s, const tr_info_t *info, const int state, const char *status );
static int TOF_initCommand(void);
static int TOF_writePID(void);
static void TOF_deletePID(void);
/* -END- */

char * getStringRatio( float ratio )
{
    static char string[20];

    if( ratio == TR_RATIO_NA )
        return "n/a";
    snprintf( string, sizeof string, "%.3f", ratio );
    return string;
}

#define LINEWIDTH 80

static void
torrentStateChanged( tr_torrent   * torrent UNUSED,
                     cp_status_t    status UNUSED,
                     void         * user_data UNUSED )
{
    system( finishCall );
}

int main( int argc, char ** argv )
{
    int i, error;
    tr_handle  * h;
    const tr_stat    * s;
	const tr_info    * info;
    tr_handle_status * hstat;
	
	
	// vars
	char *TOF_eta = NULL;
	
    printf( "Transmission %s - http://transmission.m0k.org/ - modified for Torrentflux\n\n",
            LONG_VERSION_STRING );

    /* Get options */
    if( parseCommandLine( argc, argv ) )
    {
        printf( USAGE, argv[0], TR_DEFAULT_PORT, TOF_displayInterval, TOF_seedLimit, TOF_dieWhenDone );
        return EXIT_FAILURE;
    }

    if( showHelp )
    {
        printf( USAGE, argv[0], TR_DEFAULT_PORT, TOF_displayInterval, TOF_seedLimit, TOF_dieWhenDone );
        return EXIT_SUCCESS;
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
        snprintf( env, sizeof env, "TR_DEBUG=%d", verboseLevel );
        putenv( env );
    }

    if( bindPort < 1 || bindPort > 65535 )
    {
		sprintf( TOF_message, "Invalid port '%d'\n", bindPort );
        TOF_print( TOF_message );
		//printf( "Invalid port '%d'\n", bindPort );
        return EXIT_FAILURE;
    }

    /* Initialize libtransmission */
    h = tr_init( "cli" );

    if( sourceFile && *sourceFile ) /* creating a torrent */
    {
        int ret;
        tr_metainfo_builder * builder = tr_metaInfoBuilderCreate( h, sourceFile );
        tr_makeMetaInfo( builder, torrentPath, announce, comment, isPrivate );
        while( !builder->isDone ) {
            wait_msecs( 1 );
            printf( "." );
        }
        ret = !builder->failed;
        tr_metaInfoBuilderFree( builder );
        return ret;
    }

    /* Open and parse torrent file */
    if( !( tor = tr_torrentInit( h, torrentPath, ".", 0, &error ) ) )
    {
        sprintf( TOF_message, "Failed opening torrent file '%s'\n", torrentPath );
        TOF_print( TOF_message );
		//printf( "Failed opening torrent file `%s'\n", torrentPath );
        tr_close( h );
        return EXIT_FAILURE;
    }

    if( showInfo )
    {
        info = tr_torrentInfo( tor );

        s = tr_torrentStat( tor );

        /* Print torrent info (quite à la btshowmetainfo) */
        printf( "hash:     " );
        for( i = 0; i < SHA_DIGEST_LENGTH; i++ )
        {
            printf( "%02x", info->hash[i] );
        }
        printf( "\n" );
        printf( "tracker:  %s:%d\n",
                s->tracker->address, s->tracker->port );
        printf( "announce: %s\n", s->tracker->announce );
        printf( "size:     %"PRIu64" (%"PRIu64" * %d + %"PRIu64")\n",
                info->totalSize, info->totalSize / info->pieceSize,
                info->pieceSize, info->totalSize % info->pieceSize );
        if( info->comment[0] )
        {
            printf( "comment:  %s\n", info->comment );
        }
        if( info->creator[0] )
        {
            printf( "creator:  %s\n", info->creator );
        }
        if( info->isPrivate )
        {
            printf( "private flag set\n" );
        }
        printf( "file(s):\n" );
        for( i = 0; i < info->fileCount; i++ )
        {
            printf( " %s (%"PRIu64")\n", info->files[i].name,
                    info->files[i].length );
        }

        goto cleanup;
    }

#if 0
    if( showScrape )
    {
        int seeders, leechers, downloaded;

        if( tr_torrentScrape( tor, &seeders, &leechers, &downloaded ) )
        {
            printf( "Scrape failed.\n" );
        }
        else
        {
            printf( "%d seeder(s), %d leecher(s), %d download(s).\n",
                    seeders, leechers, downloaded );
        }

        goto cleanup;
    }
#endif

	//* Torrentflux -START- */
	if (TOF_owner == NULL) 
	{
		sprintf( TOF_message, "No owner supplied, using 'n/a'.\n" );
        TOF_print( TOF_message );
		
		strcpy(TOF_owner,"n/a");
	}
	
	// Output for log
	sprintf( TOF_message, "transmission starting up :\n" );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - torrent : %s\n", torrentPath );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - owner : %s\n", TOF_owner );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - dieWhenDone : %d\n", TOF_dieWhenDone );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - seedLimit : %d\n", TOF_seedLimit );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - bindPort : %d\n", bindPort );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - uploadLimit : %d\n", uploadLimit );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - downloadLimit : %d\n", downloadLimit );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - natTraversal : %d\n", natTraversal );
    TOF_print( TOF_message );
	sprintf( TOF_message, " - displayInterval : %d\n", TOF_displayInterval );
    TOF_print( TOF_message );
	if (finishCall != NULL)
	{
		sprintf( TOF_message, " - finishCall : %s\n", finishCall );
		TOF_print( TOF_message );
	}	
	/* -END- */
	
    signal( SIGINT, sigHandler );
    signal( SIGHUP, sigHandler );

    tr_setBindPort( h, bindPort );
  
    tr_setGlobalSpeedLimit   ( h, TR_UP,   uploadLimit );
    tr_setUseGlobalSpeedLimit( h, TR_UP,   uploadLimit > 0 );
    tr_setGlobalSpeedLimit   ( h, TR_DOWN, downloadLimit );
    tr_setUseGlobalSpeedLimit( h, TR_DOWN, downloadLimit > 0 );

    tr_natTraversalEnable( h, natTraversal );
    
    tr_torrentSetStatusCallback( tor, torrentStateChanged, NULL );
    tr_torrentStart( tor );
	
	/* Torrentflux -START */
	
	// initialize status-facility
	if (TOF_initStatus() == 0) 
	{
		sprintf( TOF_message, "Failed to init status-facility. exit transmission.\n" );
		TOF_print( TOF_message );
		goto failed;
	}

	// initialize command-facility
	if (TOF_initCommand() == 0) 
	{
		sprintf( TOF_message, "Failed to init command-facility. exit transmission.\n" );
		TOF_print( TOF_message );
		goto failed;
	}

	// write pid
	if (TOF_writePID() == 0) 
	{
		sprintf( TOF_message, "Failed to write pid-file. exit transmission.\n" );
		TOF_print( TOF_message );
		goto failed;
	}
	
	sprintf( TOF_message, "Transmission up and running.\n" );
    TOF_print( TOF_message );
	
	info = tr_torrentInfo( tor );
	/* -END- */

    for( ;; )
    {
        int result;

        wait_secs( 1 );

        if( gotsig )
        {
            gotsig = 0;
            tr_torrentStop( tor );
            tr_natTraversalEnable( h, 0 );
        }
        
        if( manualUpdate )
        {
            manualUpdate = 0;
            if ( !tr_torrentCanManualUpdate( tor ) )
                fprintf( stderr, "\rReceived SIGHUP, but can't send a manual update now\n" );
            else {
                fprintf( stderr, "\rReceived SIGHUP: manual update scheduled\n" );
                tr_manualUpdate( tor );
            }
        }

        s = tr_torrentStat( tor );

        if( s->status & TR_STATUS_CHECK_WAIT )
        {
			TOF_writeStatus(s, info, 1, "Waitung to verify local files" );
        }
        else if( s->status & TR_STATUS_CHECK )
        {
			TOF_writeStatus(s, info, 1, "Verifying local files" );
        }
        else if( s->status & TR_STATUS_DOWNLOAD )
        {
			sprintf(TOF_eta, "-");
			if ( s->eta > 0 ) 
			{
				if ( s->eta < 604800 ) // 7 days
				{
					if ( s->eta >= 86400 ) // 1 day
						sprintf(TOF_eta, "%d:",
							s->eta / 86400);
					
					if ( s->eta >= 3600 ) // 1 hour
						sprintf(TOF_eta, "%s%02d:",
							TOF_eta,((s->eta % 86400) / 3600));
					
					if ( s->eta >= 60 ) // 1 Minute
						sprintf(TOF_eta, "%s%02d:",
							TOF_eta,((s->eta % 3600) / 60));
							
					sprintf(TOF_eta, "%s%02d",
						TOF_eta,(s->eta % 60));
				} 
			}
				
            if ((s->seeders < -1) && (s->peersTotal == 0))
				sprintf(TOF_eta, "Connecting to Peers");
			
			TOF_writeStatus(s, info, 1, TOF_eta );
        }
        else if( s->status & TR_STATUS_SEED )
        {
			if (TOF_dieWhenDone == 1) 
			{
				TOF_print( "Die-when-done set, setting shutdown-flag...\n" );
				gotsig = 1;
			} 
			else 
			{
				if (TOF_seedLimit == -1) 
				{
					TOF_print( "Sharekill set to -1, setting shutdown-flag...\n" );
					gotsig = 1;
				} 
				else if ( ( TOF_seedLimit > 0 ) && ( ( s->ratio * 100.0 ) > (float)TOF_seedLimit ) ) 
				{
					sprintf( TOF_message, "Seed-limit %d reached, setting shutdown-flag...\n", TOF_seedLimit );
					TOF_print( TOF_message );
					gotsig = 1;
				}
			}
            TOF_writeStatus(s, info, 1, "Download Succeeded!" );
        }
        else if( s->status & TR_STATUS_STOPPED )
        {
            break;
        }

        if( s->error )
        {
			sprintf( TOF_message, "error: %s\n", s->errorString );
			TOF_print( TOF_message );
        }
    }

	TOF_print("Transmission shutting down...\n");

    /* Try for 5 seconds to delete any port mappings for nat traversal */
    tr_natTraversalEnable( h, 0 );
    for( i = 0; i < 10; i++ )
    {
        hstat = tr_handleStatus( h );
        if( TR_NAT_TRAVERSAL_DISABLED == hstat->natTraversalStatus )
        {
            /* Port mappings were deleted */
            break;
        }
        wait_msecs( 500 );
    }
	
	if (s->percentDone == 1)
		sprintf(TOF_eta, "Download Succeeded!");
	else 
		sprintf(TOF_eta, "Torrent Stopped");
	
	TOF_writeStatus(s, info, 0, TOF_eta );

	TOF_deletePID();
	
	TOF_print("Transmission exit.\n");
    
cleanup:
    tr_torrentClose( tor );
    tr_close( h );

    return EXIT_SUCCESS;

failed:
	tr_torrentClose( tor );
    tr_close( h );

	return EXIT_FAILURE;
}

static int parseCommandLine( int argc, char ** argv )
{
    for( ;; )
    {
        static struct option long_options[] =
          { { "help",     no_argument,       NULL, 'h' },
            { "info",     no_argument,       NULL, 'i' },
            { "scrape",   no_argument,       NULL, 's' },
            { "private",  no_argument,       NULL, 'r' },
            { "verbose",  required_argument, NULL, 'v' },
            { "port",     required_argument, NULL, 'p' },
            { "upload",   required_argument, NULL, 'u' },
            { "download", required_argument, NULL, 'd' },
            { "finish",   required_argument, NULL, 'f' },
            { "create",   required_argument, NULL, 'c' },
            { "comment",  required_argument, NULL, 'm' },
            { "announce", required_argument, NULL, 'a' },
			{ "nat-traversal", no_argument,  NULL, 'n' },
            { "display-interval", required_argument, NULL, 'e' },
			{ "seedlimit",        required_argument, NULL, 'l' },
			{ "owner",            required_argument, NULL, 'o' },
			{ "die-when-done",    required_argument, NULL, 'w' },
            { 0, 0, 0, 0} };

        int c, optind = 0;
        c = getopt_long( argc, argv, "hisrv:p:u:d:f:c:m:a:n:e:l:o:w",
                         long_options, &optind );
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
#if 0
            case 's':
                showScrape = 1;
                break;
#endif
            case 'r':
                isPrivate = 1;
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
            case 'f':
                finishCall = optarg;
                break;
            case 'c':
                sourceFile = optarg;
                break;
            case 'm':
                comment = optarg;
                break;
            case 'a':
                announce = optarg;
                break;
            case 'n':
                natTraversal = 1;
                break;
			case 'w':
				TOF_dieWhenDone = atoi(optarg);
				break;
			case 'l':
				TOF_seedLimit = atoi(optarg);
				break;
			case 'e':
				TOF_displayInterval = atoi(optarg);
				break;
			case 'o':
				TOF_owner = optarg;
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
            gotsig = 1;
            break;
            
        case SIGHUP:
            manualUpdate = 1;
            break;

        default:
            break;
    }
}

/* Torrentflux -START- */
static void TOF_print( char *printmsg ) 
{
	time_t rawtime;
	struct tm * timeinfo;
	time(&rawtime);
	timeinfo = localtime(&rawtime);

	fprintf(stderr, "[%4d/%02d/%02d - %02d:%02d:%02d] %s",
		timeinfo->tm_year + 1900,
		timeinfo->tm_mon + 1,
		timeinfo->tm_mday,
		timeinfo->tm_hour,
		timeinfo->tm_min,
		timeinfo->tm_sec,
		((printmsg != NULL) && (strlen(printmsg) > 0)) ? printmsg : ""
	);
}

static int TOF_initStatus( void ) 
{
	sprintf( TOF_statFile, "%s", torrentPath );
	strcat( TOF_statFile, ".stat" );
	
	sprintf( TOF_message, "Initialized status-facility. (%s)\n", TOF_statFile );
    TOF_print( TOF_message );
	return 1;
}

static int TOF_initCommand( void ) 
{
	TOF_cmdFile = torrentPath;
	strcat( TOF_cmdFile, ".cmd" );
	
	sprintf( TOF_message, "Initialized command-facility. (%s)\n", TOF_cmdFile );
    TOF_print( TOF_message );

	// remove command-file if exists
	TOF_cmdFp = NULL;
	TOF_cmdFp = fopen(TOF_cmdFile, "r");
	if (TOF_cmdFp != NULL) 
	{
		fclose(TOF_cmdFp);
		sprintf( TOF_message, "Removing command-file. (%s)\n", TOF_cmdFile );
		TOF_print( TOF_message );
		remove(TOF_cmdFile);
		TOF_cmdFp = NULL;
	}
	return 1;
}

static int TOF_writePID( void ) 
{
	FILE * TOF_pidFp;
	char TOF_pidFile[strlen(torrentPath) + 4];
	
	sprintf(TOF_pidFile,"%s.pid",torrentPath);
	
	TOF_pidFp = fopen(TOF_pidFile, "w+");
	if (TOF_pidFp != NULL) 
	{
		fprintf(TOF_pidFp, "%d", getpid());
		fclose(TOF_pidFp);
		sprintf( TOF_message, "Wrote pid-file: %s (%d)\n", TOF_pidFile , getpid() );
		TOF_print( TOF_message );
		return 1;
	} 
	else 
	{
		sprintf( TOF_message, "Error opening pid-file for writting: %s (%d)\n", TOF_pidFile , getpid() );
		TOF_print( TOF_message );
		return 0;
	}
}

static void TOF_deletePID( void ) 
{
	char TOF_pidFile[strlen(torrentPath) + 4];
	
	sprintf(TOF_pidFile,"%s.pid",torrentPath);
	
	sprintf( TOF_message, "Removing pid-file: %s (%d)\n", TOF_pidFile , getpid() );
	TOF_print( TOF_message );
	
	remove(TOF_pidFile);
}

static void TOF_writeStatus( const tr_stat_t *s, const tr_info_t *info, const int state, const char *status )
{
	TOF_statFp = fopen(TOF_statFile, "w+");
	if (TOF_statFp != NULL) 
	{
		float TOF_pd,TOF_ratio;
		int TOF_seeders,TOF_leechers;
		
		TOF_seeders  = ( s->seeders < 0 )  ? 0 : s->seeders;
		TOF_leechers = ( s->leechers < 0 ) ? 0 : s->leechers;
		
		if (state == 0 && s->percentDone < 1)
			TOF_pd = ( -100.0 * s->percentDone ) - 100;
		else
			TOF_pd = 100.0 * s->percentDone;
		
		TOF_ratio = s->ratio < 0 ? 0 : s->ratio;
			
		fprintf(TOF_statFp,
			"%d\n%.1f\n%s\n%.1f kB/s\n%.1f kB/s\n%s\n%d (%d)\n%d (%d)\n%.1f\n%d\n%" PRIu64 "\n%" PRIu64 "\n%" PRIu64,
			state,                                       /* State            */
			TOF_pd,                                     /* Progress         */
			status,                                    /* Status text      */
			s->rateDownload,                          /* Download speed   */
			s->rateUpload,                           /* Upload speed     */
			TOF_owner,                              /* Owner            */
			s->peersSendingToUs, TOF_seeders,      /* Seeder           */
			s->peersGettingFromUs, TOF_leechers,  /* Leecher          */
			100.0 * TOF_ratio,                   /* ratio            */
			TOF_seedLimit,                      /* seedlimit        */
			s->uploaded,                       /* uploaded bytes   */
			s->downloaded,                    /* downloaded bytes */
			info->totalSize                  /* global size      */
		);               
		fclose(TOF_statFp);
	}
	else 
	{
		sprintf( TOF_message, "Error opening stat-file for writting: %s\n", TOF_statFile );
		TOF_print( TOF_message );
	}
}

/* -END- */
