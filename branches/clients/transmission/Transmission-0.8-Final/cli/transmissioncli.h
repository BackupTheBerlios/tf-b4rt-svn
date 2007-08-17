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
 * transmissioncli.h - use transmission with torrentflux-b4rt
 * http://tf-b4rt.berlios.de/
 *
 ******************************************************************************/

/*******************************************************************************
 * includes and defines
 ******************************************************************************/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <getopt.h>
#include <signal.h>

#include <libtransmission/transmission.h>
#include <libtransmission/makemeta.h>

//#include <sys/types.h>
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

const char * HEADER =
"Transmission %s - tfCLI \nhttp://transmission.m0k.org/ - http://tf-b4rt.berlios.de/\n\n";

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
"  -v, --verbose <int>  Verbose level (0 to 2, default = 0)\n\n"
"Torrentflux Commands:\n"
"  -e, --display-interval <int> Time between updates of stat-file (default = %d)\n"
"  -l, --seedlimit <int> Seed-Limit (Percent) to reach before shutdown\n"
"                        (0 = seed forever, -1 = no seeding, default = %d)\n"
"  -o, --owner <string> Name of the owner (default = 'n/a')\n"
"  -w, --die-when-done  Auto-Shutdown when done (0 = Off, 1 = On, default = %d)\n";

#define TF_CMDFILE_MAXLEN 65536

/*******************************************************************************
 * fields
 ******************************************************************************/

// tr
static int 			 showHelp      = 0;
static int           showInfo      = 0;
#if 0
static int           showScrape    = 0;
#endif
static int           isPrivate     = 0;
static int 			 verboseLevel  = 0;
static int 			 bindPort      = TR_DEFAULT_PORT;
static int 			 uploadLimit   = 20;
static int 			 downloadLimit = -1;
static char 		 * torrentPath = NULL;
static int 			 natTraversal  = 0;
static sig_atomic_t  gotsig        = 0;
static tr_torrent_t  * tor;

static char          * finishCall  = NULL;
static char          * announce    = NULL;
static char          * sourceFile  = NULL;
static char          * comment     = NULL;

// tf
static volatile char tf_shutdown = 0;
static int tf_dieWhenDone = 0;
static int tf_seedLimit = 0;
static int tf_displayInterval = 5;
static char tf_message[512];
static char * tf_owner = NULL;
static char * tf_stat_file = NULL;
static FILE * tf_stat_fp = NULL;
static char * tf_cmd_file = NULL;
static FILE * tf_cmd_fp = NULL;

/*******************************************************************************
 * functions
 ******************************************************************************/

// tr
static int parseCommandLine(int argc, char ** argv);
static void sigHandler(int signal);

// tf
static void tf_showInfo(void);
#if 0
static void tf_showScrape(void);
#endif
static void tf_torrentStop(tr_handle_t *h, const tr_info_t *info);
static int tf_initializeStatusFacility(void);
static int tf_initializeCommandFacility(void);
static int tf_processCommandStack(tr_handle_t *h);
static int tf_processCommandFile(tr_handle_t *h);
static int tf_execCommand(tr_handle_t *h, char *s);
static int tf_pidWrite(void);
static int tf_pidDelete(void);
static int tf_print(int len);
