/******************************************************************************
 * $Id$
 * $Date$
 * $Revision$
 ******************************************************************************
 *                                                                            *
 * LICENSE                                                                    *
 *                                                                            *
 * This program is free software; you can redistribute it and/or              *
 * modify it under the terms of the GNU General Public License (GPL)          *
 * as published by the Free Software Foundation; either version 2             *
 * of the License, or (at your option) any later version.                     *
 *                                                                            *
 * This program is distributed in the hope that it will be useful,            *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of             *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the               *
 * GNU General Public License for more details.                               *
 *                                                                            *
 * To read the license please visit http://www.gnu.org/copyleft/gpl.html      *
 *                                                                            *
 * In addition, as a special exception, the copyright holders give            *
 * permission to link the code of portions of this program with the           *
 * OpenSSL library under certain conditions as described in each              *
 * individual source file, and distribute linked combinations                 *
 * including the two.                                                         *
 *                                                                            *
 * You must obey the GNU General Public License in all respects               *
 * for all of the code used other than OpenSSL.  If you modify                *
 * file(s) with this exception, you may extend this exception to your         *
 * version of the file(s), but you are not obligated to do so.  If you        *
 * do not wish to do so, delete this exception statement from your            *
 * version.  If you delete this exception statement from all source           *
 * files in the program, then also delete it here.                            *
 *                                                                            *
 ******************************************************************************
 * Options (command-line and cmd-file) management.
 ******************************************************************************/

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/torrent.h>

#include <rak/functional.h>
namespace r = rak;

#include <getopt.h>
#include <unistd.h>
#include <sstream>
#include <cerrno>
#include <cstdio>
#include <map>
#include <algorithm>

#include "cli.hh"
#include "opts.hh"


namespace opts
{


namespace
{


/******************************************************************************
 * Options names.
 ******************************************************************************/

#define OPTs(n,s)	static const int OC_##n = (s)
#define OPT(n,s,l)	OPTs(n,s); static const char OS_##n[] = (l)

OPTs(ANNOUNCE2,       'a');
OPT (ANNOUNCE,        'A',     "announce");
OPT (BINDIP,          'b',     "bind-ip");
OPT (CREATE,          'c',     "create");
OPT (COMMENT,         'C',     "comment");
OPT (CMD,             'C'+128, "command-file");
OPT (DOWN,            'd',     "download-limit");
OPT (DISPLAYINTERVAL, 'e',     "display-interval");
OPT (ENCRYPTION,      'E',     "encryption");
OPT (HELP,            'h',     "help");
OPT (INFO,            'i',     "info");
OPT (SHAREKILL,       'l',     "seed-limit");
OPTs(COMMENT2,        'm');
OPT (MAXCONNECTIONS,  'N',     "max-connections");
OPT (OWNER,           'o',     "owner");
OPT (PORT,            'p',     "port");
OPT (PRIVATE,         'P',     "private");
OPT (PID,             'P'+128, "pid-file");
OPTs(PRIVATE2,        'r');
OPT (REPORTIP,        'R',     "report-ip");
OPT (SKIPHASHCHECK,   's',     "skip-hash-check");
OPT (PIECESIZE,       'S',     "piece-size");
OPT (STAT,            'S'+128, "stat-file");
OPT (TRANSFER,        't',     "transfer");
OPT (TFBMODE,         'T',     "tfb");
OPT (UP,              'u',     "upload-limit");
OPT (MAXUPLOADS,      'U',     "max-uploads");
OPT (VERBOSE,         'v',     "verbose");
OPT (AUTODIE,         'w',     "die-when-done");

#undef OPT
#undef OPTs


/******************************************************************************
 * Options variables.
 ******************************************************************************/

static const unsigned int   c_max_verbose         = 4;
static const uint16_t       c_def_port            = 9099;
static const EncryptionMode c_def_encryption      = EM_ACTIVE;
static const unsigned int   c_def_displayinterval = 5;
static const char* const    c_def_owner           = "n/a";

//[CARE] ParseEncryption relies on the contents of those.
static const char* const    c_arg_none         = "none";
static const char* const    c_arg_accept       = "accept";
static const char* const    c_arg_active       = "active";
static const char* const    c_arg_require      = "require";
static const char* const    c_arg_require_full = "require-full";

static const struct EncryptionEntry
{
	const char*    txt;
	EncryptionMode val;
} c_encryption_entries[] = {
	{ c_arg_none,         EM_NONE        },
	{ c_arg_accept,       EM_ACCEPT      },
	{ c_arg_active,       EM_ACTIVE      },
	{ c_arg_require,      EM_REQUIRE     },
	{ c_arg_require_full, EM_REQUIREFULL },
};


AppMode           p_mode            = NONE;
const char*       p_source          = NULL;
const char*       p_torrent         = NULL;
unsigned int      p_verbose         = 0;

r::socket_address p_bindip;
r::socket_address p_reportip;
uint16_t          p_portmin         = c_def_port;
uint16_t          p_portmax         = c_def_port;
uint64_t          p_down            = 0ULL;
uint64_t          p_up              = 0ULL;
unsigned long     p_maxconnections  = 0UL;
unsigned long     p_maxuploads      = 0UL;
EncryptionMode    p_encryption      = c_def_encryption;
bool              p_skiphashcheck   = false;
bool              p_autodie         = false;
long              p_sharekill       = 0L;
bool              p_tfbmode         = false;

string            p_pid;
string            p_cmd;
string            p_stat;
unsigned int      p_displayinterval = c_def_displayinterval;
const char*       p_owner           = c_def_owner;


}



/******************************************************************************
 * Options accessors.
 ******************************************************************************/

AppMode                  Mode()            { return p_mode;            }
const char*              Source()          { return p_source;          }
const char*              Torrent()         { return p_torrent;         }
unsigned int             Verbose()         { return p_verbose;         }

const r::socket_address& BindIP()          { return p_bindip;          }
const r::socket_address& ReportIP()        { return p_reportip;        }
uint16_t                 PortMin()         { return p_portmin;         }
uint16_t                 PortMax()         { return p_portmax;         }
uint64_t                 Down()            { return p_down;            }
uint64_t                 Up()              { return p_up;              }
unsigned long            MaxConnections()  { return p_maxconnections;  }
unsigned long            MaxUploads()      { return p_maxuploads;      }
EncryptionMode           Encryption()      { return p_encryption;      }
bool                     SkipHashCheck()   { return p_skiphashcheck;   }
bool                     AutoDie()         { return p_autodie;         }
long                     ShareKill()       { return p_sharekill;       }
bool                     TFBMode()         { return p_tfbmode;         }

const char* EncryptionTxt()
{
	const EncryptionEntry* const p(
		find_if(
			c_encryption_entries,
			c_encryption_entries + countof(c_encryption_entries),
			r::equal(p_encryption, r::const_mem_ref(&EncryptionEntry::val))
		)
	);
	assert(p != c_encryption_entries + countof(c_encryption_entries));
	if (p != c_encryption_entries + countof(c_encryption_entries))
		return p->txt;

	throw runtime_error("Invalid encryption value");
	return NULL;
}

string                   PID()             { return !p_pid .empty() ? p_pid  : string(p_torrent) + ".pid";  }
string                   Cmd()             { return !p_cmd .empty() ? p_cmd  : string(p_torrent) + ".cmd";  }
string                   Stat()            { return !p_stat.empty() ? p_stat : string(p_torrent) + ".stat"; }
unsigned int             DisplayInterval() { return p_displayinterval; }
const char*              Owner()           { return p_owner;           }



/******************************************************************************
 * Options setters.
 ******************************************************************************/

void Down(uint64_t val) { p_down = val; }
void Up  (uint64_t val) { p_up   = val; }

void AutoDie(bool val)
{
	p_autodie = val;
	if (val)
		p_sharekill = -1L;
	else if (p_sharekill == -1L)
		p_sharekill = 0L;
}

void ShareKill(long val)
{
	p_sharekill = val;
	if (val == -1L)
		p_autodie = true;
	else if (p_autodie)
		p_autodie = false;
}



/******************************************************************************
 * Parsing helpers.
 ******************************************************************************/

namespace
{

pair< unsigned long, bool > ParseUNumber(const char* ptr)
{
	const char* endptr(ptr);
	errno = 0;

	if (strchr(ptr, '-') != NULL)
		return make_pair(0UL, false);

	const unsigned long ret(strtoul(ptr, const_cast< char** >(&endptr), 10));
	if (*ptr == '\0' || (ret == 0UL && errno != 0) || ret == ULONG_MAX || *endptr != '\0')
		return make_pair(0UL, false);
	return make_pair(ret, true);
}

pair< long, bool > ParseSNumber(const char* ptr)
{
	const char* endptr(ptr);
	errno = 0;

	const long ret(strtol(ptr, const_cast< char** >(&endptr), 10));
	if (*ptr == '\0' || (ret == 0L && errno != 0) || ret == LONG_MAX || *endptr != '\0')
		return make_pair(0L, false);
	return make_pair(ret, true);
}

template< bool BAcceptM1/* = false*/ >
pair< unsigned long long, bool > ParseUNumberL(const char* ptr)
{
	const char* endptr(ptr);
	errno = 0;

	if (BAcceptM1)
	{
		while (isspace(*ptr))
			ptr++;
		if (!strcmp(ptr, "-1"))
			return make_pair(-1ULL, true);
	}
	if (strchr(ptr, '-') != NULL)
		return make_pair(0ULL, false);

	const unsigned long long ret(strtoull(ptr, const_cast< char** >(&endptr), 10));
	if (*ptr == '\0' || (ret == 0ULL && errno != 0) || ret == ULLONG_MAX || *endptr != '\0')
		return make_pair(0ULL, false);
	return make_pair(ret, true);
}

pair< bool, bool > ParseBool(const char* ptr)
{
	if (*ptr != '\0')
	{
		if (*(ptr + 1) == '\0')
		{
			if (*ptr == '0')
				return make_pair(false, true);
			if (*ptr == '1')
				return make_pair(true, true);
		}
		else
		{
			if (!strcasecmp(ptr, "false") || !strcasecmp(ptr, "no")  || !strcasecmp(ptr, "off"))
				return make_pair(false, true);
			if (!strcasecmp(ptr, "true")  || !strcasecmp(ptr, "yes") || !strcasecmp(ptr, "on"))
				return make_pair(true, true);
		}
	}
	return make_pair(false, false);
}

}



/******************************************************************************
 * Parse some options' arguments.
 ******************************************************************************/

pair< uint64_t, bool > ParseDown(const char* arg)
{
	const pair< unsigned long long, bool > ret(ParseUNumberL< true >(arg));
	if (ret.second &&
		// Accept -1 meaning unlimited for compatibility with transmissioncli.
		(ret.first == -1ULL ||
		 (ret.first >= 0ULL &&
		  ret.first <= (unsigned long long)numeric_limits< int32_t >::max() &&	// libtorrent rate is an int32_t.
		  ret.first <= (1ULL << 40))))
		return make_pair(ret.first == -1ULL ? 0ULL : ret.first, true);
	return make_pair(0ULL, false);
}

pair< uint64_t, bool > ParseUp(const char* arg)
{
	const pair< unsigned long long, bool > ret(ParseUNumberL< true >(arg));
	if (ret.second &&
		// Accept -1 meaning unlimited for compatibility with transmissioncli.
		(ret.first == -1ULL ||
		 (ret.first >= 0ULL &&
		  ret.first <= (unsigned long long)numeric_limits< int32_t >::max() &&	// libtorrent rate is an int32_t.
		  ret.first <= (1ULL << 40))))
		return make_pair(ret.first == -1ULL ? 0ULL : ret.first, true);
	return make_pair(0ULL, false);
}

pair< bool, bool > ParseAutoDie(const char* arg)
{
	const pair< bool, bool > ret(ParseBool(arg));
	if (ret.second)
		return make_pair(ret.first, true);
	return make_pair(false, false);
}

pair< long, bool > ParseShareKill(const char* arg)
{
	const pair< long, bool > ret(ParseSNumber(arg));
	if (ret.second && (ret.first == -1L || (ret.first >= 0L && ret.first <= 100000000L)))
		return make_pair(ret.first, true);
	return make_pair(0L, false);
}

pair< EncryptionMode, bool > ParseEncryption(const char* arg)
{
	const pair< unsigned long, bool > ret(ParseUNumber(arg));
	if (ret.second && ret.first >= EM__MIN && ret.second <= EM__MAX)
		return make_pair(EncryptionMode(ret.first), true);

	const size_t len(strlen(arg));

	typedef map< EncryptionMode, bool > TMatches;
	TMatches matches;
	unsigned int count = 0;
	for (const EncryptionEntry* p = c_encryption_entries;
		 p < c_encryption_entries + countof(c_encryption_entries);
		 p++)
	{
		const bool match(strncmp(p->txt, arg, len) == 0);
		if (match)
			count++;
		matches.insert(make_pair(p->val, match));
	}

	if (count == 1)
	{
		// No ambiguity.
		return make_pair(
			find_if(matches.begin(), matches.end(), r::const_mem_ref(&TMatches::value_type::second))->first,
			true
		);
	}
	else if (count == 2)
	{
		// Match on both require and require-full: require wins.
		if (matches[EM_REQUIRE] && matches[EM_REQUIREFULL])
			return make_pair(EM_REQUIRE, true);

		// Match on both accept and active: active wins.
		if (matches[EM_ACCEPT] && matches[EM_ACTIVE])
			return make_pair(EM_ACTIVE, true);
	}

	return make_pair(c_def_encryption, false);
}



/******************************************************************************
 * Parse command-line options.
 ******************************************************************************/

int ParseCommandLine(int argc, char** argv)
{
	// Handle no-args as --help.
	if (argc <= 1)
	{
		p_mode = HELP;
		return EXIT_SUCCESS;
	}


	static const option opts_long[] = {
		{ OS_BINDIP,          required_argument, NULL, OC_BINDIP          },
		{ OS_CREATE,          required_argument, NULL, OC_CREATE          },
		{ OS_CMD,             required_argument, NULL, OC_CMD             },
		{ OS_DOWN,            required_argument, NULL, OC_DOWN            },
		{ OS_DISPLAYINTERVAL, required_argument, NULL, OC_DISPLAYINTERVAL },
		{ OS_ENCRYPTION,      optional_argument, NULL, OC_ENCRYPTION      },
		{ OS_HELP,            no_argument,       NULL, OC_HELP            },
		{ OS_INFO,            no_argument,       NULL, OC_INFO            },
		{ OS_SHAREKILL,       required_argument, NULL, OC_SHAREKILL       },
		{ OS_MAXCONNECTIONS,  required_argument, NULL, OC_MAXCONNECTIONS  },
		{ OS_OWNER,           required_argument, NULL, OC_OWNER           },
		{ OS_PID,             required_argument, NULL, OC_PID             },
		{ OS_PORT,            required_argument, NULL, OC_PORT            },
		{ OS_REPORTIP,        required_argument, NULL, OC_REPORTIP        },
		{ OS_SKIPHASHCHECK,   optional_argument, NULL, OC_SKIPHASHCHECK   },
		{ OS_STAT,            required_argument, NULL, OC_STAT            },
		{ OS_TRANSFER,        no_argument,       NULL, OC_TRANSFER        },
		{ OS_TFBMODE,         optional_argument, NULL, OC_TFBMODE         },
		{ OS_UP,              required_argument, NULL, OC_UP              },
		{ OS_MAXUPLOADS,      required_argument, NULL, OC_MAXUPLOADS      },
		{ OS_VERBOSE,         optional_argument, NULL, OC_VERBOSE         },
		{ OS_AUTODIE,         optional_argument, NULL, OC_AUTODIE         },
		{ NULL,               0,                 NULL, 0                  }
	};

	static const char opts[] = {
		OC_BINDIP,          ':',
		OC_CREATE,          ':',
		OC_DOWN,            ':',
		OC_DISPLAYINTERVAL, ':',
		OC_ENCRYPTION,      ':',
		OC_HELP,
		OC_INFO,
		OC_SHAREKILL,       ':',
		OC_MAXCONNECTIONS,  ':',
		OC_OWNER,           ':',
		OC_PORT,            ':',
		OC_REPORTIP,        ':',
		OC_SKIPHASHCHECK,
		OC_TRANSFER,
		OC_TFBMODE,
		OC_UP,              ':',
		OC_MAXUPLOADS,      ':',
		OC_VERBOSE,         // (would prevent -vv) ':', ':',
		OC_AUTODIE,
		'\0'
	};

#define ARGCHECK(o,t,c)																			\
	if (!(c))																					\
	{																							\
		fprintf(stderr, "%s: invalid %s for option `--%s': %s\n", argv[0], (t), (o), optarg);	\
		return EXIT_FAILURE;																	\
	}

	while (true)
	{
		const int opt(getopt_long(argc, argv, opts, opts_long, NULL));
		if (opt == -1)
		{
			// If in help mode, return right away.
			if (p_mode == HELP)
				return EXIT_SUCCESS;

			// Otherwise, there shall be one and only one non-option arg: torrent filename.
			if (argv[optind] == NULL || optind >= argc)
			{
				fprintf(stderr, "%s: no torrent filename given\n", argv[0]);
				return EXIT_FAILURE;
			}
			else if (optind < argc - 1)
			{
				fprintf(stderr, "%s: multiple torrent filenames given\n", argv[0]);
				return EXIT_FAILURE;
			}

			p_torrent = argv[optind];
			if (p_mode == NONE)		// If not specified, default mode is transfer.
				p_mode = TRANSFER;

			return EXIT_SUCCESS;
		}


		switch (opt)
		{

		//
		// Modes.
		//

		case OC_HELP:
			p_mode = HELP;
			break;

		case OC_CREATE:
		case OC_INFO:
		case OC_TRANSFER:
			if (p_mode == HELP)		// Help mode wins, ignore new one.
				break;
			if (p_mode != NONE)
			{
				fprintf(stderr, "%s: at most one mode is allowed: -%c\n", argv[0], (char)opt);
				return EXIT_FAILURE;
			}
			if (opt == OC_CREATE)
			{
				ARGCHECK(OS_CREATE, "source", optarg != NULL && *optarg != '\0');
				p_source = optarg;
			}
			p_mode =
				opt == OC_CREATE ? CREATE   :
				opt == OC_INFO   ? INFO     :
								   TRANSFER;
			break;


		//
		// Verbose.
		//

		case OC_VERBOSE:
			if (optarg == NULL)
			{
				if (p_verbose < c_max_verbose)
					p_verbose++;
			}
			else
			{
				ARGCHECK(OS_VERBOSE, "level",
						 optarg[0] >= '0' && optarg[0] <= '0' + (char)c_max_verbose && optarg[1] == '\0');
				p_verbose = *optarg - '0';
			}
			break;


		//
		// Transfer-mode low-level network stuff.
		//

		case OC_BINDIP:
			if (optarg == NULL || *optarg == '\0')
				p_bindip.clear();
			else
				ARGCHECK(OS_BINDIP, "ip", p_bindip.set_address_c_str(optarg));
			break;

		case OC_REPORTIP:
			if (optarg == NULL || *optarg == '\0')
				p_reportip.clear();
			else
				ARGCHECK(OS_REPORTIP, "ip", p_reportip.set_address_c_str(optarg));
			break;

		case OC_PORT:
			{
				const char* const sep(strchr(optarg, ':'));
				const unsigned long portmin(
					sep == NULL ? ParseUNumber(optarg).first : ParseUNumber(string(optarg, sep - optarg).c_str()).first
				);
				const unsigned long portmax(
					sep == NULL ? portmin                    : ParseUNumber(sep + 1).first
				);
				ARGCHECK(OS_PORT, sep == NULL ? "port" : "ports",
						 portmin > 0UL && portmin < 65535UL &&
						 (sep == NULL || (portmax > 0UL && portmax < 65535UL && portmax >= portmin)));
				p_portmin = (uint16_t)portmin;
				p_portmax = (uint16_t)portmax;
			}
			break;


		//
		// Transfer-mode high-level network stuff.
		//

		case OC_DOWN:
			{
				const pair< uint64_t, bool > ret(ParseDown(optarg));
				ARGCHECK(OS_DOWN, "rate", ret.second);
				Down(ret.first);
			}
			break;

		case OC_UP:
			{
				const pair< uint64_t, bool > ret(ParseUp(optarg));
				ARGCHECK(OS_UP, "rate", ret.second);
				Up(ret.first);
			}
			break;

		case OC_MAXCONNECTIONS:
			{
				const pair< unsigned long, bool > ret(ParseUNumber(optarg));
				ARGCHECK(OS_MAXCONNECTIONS, "number", ret.second && ret.first >= 0UL && ret.first <= 10000UL);
				p_maxconnections = ret.first;
			}
			break;

		case OC_MAXUPLOADS:
			{
				const pair< unsigned long, bool > ret(ParseUNumber(optarg));
				ARGCHECK(OS_MAXUPLOADS, "number", ret.second && ret.first >= 0UL && ret.first <= 10000UL);
				p_maxuploads = ret.first;
			}
			break;

		case OC_ENCRYPTION:
			if (optarg == NULL)
				p_encryption = c_def_encryption;
			else
			{
				const pair< EncryptionMode, bool > ret(ParseEncryption(optarg));
				ARGCHECK(OS_ENCRYPTION, "mode", ret.second && ret.first >= EM__MIN && ret.first <= EM__MAX);
				p_encryption = ret.first;
			}
			break;


		//
		// Transfer-mode torrent stuff.
		//

		case OC_SKIPHASHCHECK:
			if (optarg == NULL)
				p_skiphashcheck = true;
			else
			{
				const pair< bool, bool > ret(ParseBool(optarg));
				ARGCHECK(OS_SKIPHASHCHECK, "boolean", ret.second);
				p_skiphashcheck = ret.first;
			}
			break;

		case OC_AUTODIE:
			if (optarg == NULL)
			{
				p_autodie = true;
				p_sharekill = -1;
			}	
			else
			{
				const pair< bool, bool > ret(ParseAutoDie(optarg));
				ARGCHECK(OS_AUTODIE, "boolean", ret.second);
				AutoDie(ret.first);
			}
			break;

		case OC_SHAREKILL:
			{
				const pair< long, bool > ret(ParseShareKill(optarg));
				ARGCHECK(OS_SHAREKILL, "ratio", ret.second);
				ShareKill(ret.first);
			}
			break;


		//
		// Transfer-mode tfb stuff.
		//

		case OC_TFBMODE:
			if (optarg == NULL)
				p_tfbmode = true;
			else
			{
				const pair< bool, bool > ret(ParseBool(optarg));
				ARGCHECK(OS_TFBMODE, "boolean", ret.second);
				p_tfbmode = ret.first;
			}
			break;

		case OC_CMD:
			ARGCHECK(OS_CMD, "filename", optarg != NULL && *optarg != '\0');
			p_cmd = optarg;
			break;

		case OC_PID:
			ARGCHECK(OS_PID, "filename", optarg != NULL && *optarg != '\0');
			p_pid = optarg;
			break;

		case OC_STAT:
			ARGCHECK(OS_STAT, "filename", optarg != NULL && *optarg != '\0');
			p_stat = optarg;
			break;

		case OC_DISPLAYINTERVAL:
			{
				const pair< unsigned long, bool > ret(ParseUNumber(optarg));
				ARGCHECK(OS_DISPLAYINTERVAL, "interval", ret.second && ret.first > 0 && ret.first <= 40000000);
				p_displayinterval = ret.first;
			}
			break;

		case OC_OWNER:
			ARGCHECK(OS_OWNER, "user", optarg != NULL && *optarg != '\0');
			p_owner = optarg;
			break;


		//
		// Errors.
		//

		case '?':
		case ':':
			return EXIT_FAILURE;

		default:
			assert(false);
			fprintf(stderr, "%s: Internal error: unknown option: %d\n", argv[0], opt);
			return EXIT_FAILURE;

		}
	}

#undef ARGCHECK
}



/******************************************************************************
 * Show help.
 ******************************************************************************/

void ShowHelp(const char* program)
{
	const bool tty(isatty(STDOUT_FILENO));
	const char* const b(tty ? "\033[01m" : "");
	const char* const u(tty ? "\033[04m" : "");
	const char* const n(tty ? "\033[00m" : "");

	const bool adv(Verbose() >= 1);

	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	ss
#define OPT_NORM		; ss
#define OPT_ADV			; if (adv) ss
#define B(v)	b << v << n
#define U(v)	u << v << n
#define OPTc(o,w,a,t) \
			B("--" << OS_##o) << " " << a << \
			string((w), ' ') << " " t << endl
#define OPT2(o,w,a,t)	"  " << B('-' << string(1, OC_##o)) << ", " << B('-' << string(1, OC_##o##2)) << ", " << OPTc(o,w,a,t)
#define OPT(o,w,a,t)	"  " << B('-' << string(1, OC_##o)) << (adv ? ",     " : ", ") << OPTc(o,w,a,t)
#define OPTl(o,w,a,t)	(adv ? "          " : "      ") << OPTc(o,w,a,t)
#define P				string(adv ? 33 : 29, ' ')
		<< "tfcli-libtorrent v" << TFCLILT_VER_STR << " - http://tf-b4rt.berlios.de/" << endl
		<< "libtorrent v" << t::version() << " - http://libtorrent.rakshasa.no/" << endl
		<< "TorrentFlux-b4rt command-line libtorrent client" << endl
		<< endl
		<< "Usage:" << endl
		<< "  " << program << " [--help]" << endl
		<< "  " << program << " [mode] [options] [--] " << U("torrent-file") << endl
		<< endl
		<< "Modes:" << endl
		<< OPT (CREATE,           7, U("source"),
				"Create torrent from " << U("source") << " (file or directory) and exit")
		<< OPT (HELP,            15, "", "Print this help and exit (with " << B("--" << OS_VERBOSE) << " = advanced options)")
		<< OPT (INFO,            15, "", "Dump torrent info and exit")
		<< OPT (TRANSFER,        11, "", "Transfer torrent (default if no mode specified)")
		<< endl
		<< "General options:" << endl
		<< OPT (VERBOSE,          9, "[" << U("n") << "]",
				"Verbose output (several times = more verbose, " << U("n") << " <= " << c_max_verbose << ")")
		<< endl
	OPT_ADV
		<< "Options for create (" << B("--" << OS_CREATE) << ") mode:" << endl
		<< OPT2(ANNOUNCE,         8, U("url"),
				"Set " << U("url") << " as announce")
		<< OPT2(COMMENT,          8, U("text"),
				"Set " << U("text") << " as comment")
		<< OPT2(PRIVATE,         12, "", "Set private flag")
		<< OPT (PIECESIZE,        5, U("size"),
				"Use " << U("size") << " bytes as piece size (default: 0 = auto-detect)")
		<< endl
	OPT_NORM
		<< "Options for transfer (" << B("--" << OS_TRANSFER) << ") mode:" << endl
	OPT_ADV
		<< OPT (BINDIP,          10, U("ip"),
				"Bind to " << U("ip"))
	OPT_NORM
		<< OPT (DOWN,             1, U("rate"),
				"Limit download speed to " << U("rate") << " kB/s (default: 0 = unlimited)")
	OPT_ADV
		<< OPT (ENCRYPTION,       5, U("mode"),
				"Set encryption mode to " << U("mode") << " (default: 2 / active)")
		<< P << "  0 / none         = disabled" << endl
		<< P << "  1 / accept       = allow encrypted in" << endl
		<< P << "  2 / active       = <accept> + try encrypted out" << endl
		<< P << "  3 / require      = require encrypted both ways" << endl
		<< P << "  4 / require-full = require RC4-encrypted both ways" << endl
	OPT_NORM
		<< OPT (SHAREKILL,        4, U("ratio"),
				"Auto-shutdown when " << U("ratio") << " (percent) is reached (default: 0)")
		<< P << "  -1 = do not seed (die when done), 0 = seed forever" << endl
	OPT_ADV
				// Well actually the defaults for MAXUPLOADS and MAXCONNECTIONS are unlimited and just
				// plain 100 right now, but "auto-detect" implies something smart and it if avoids
				// users upping / toying with them for no good reason... let's leave it like that.
		<< OPT (MAXCONNECTIONS,   3, U("n"),
				"Allow at most " << U("n") << " connections at once (default: 0 = auto-detect)")
	OPT_NORM
		<< OPT (PORT,            10, U("p") << "[:" << U("q") << "]",
				"Listen on port " << U("p") << " (default: " << c_def_port << "), or on one port between " << U("p") << " and " << U("q"))
	OPT_ADV
		<< OPT (REPORTIP,         8, U("ip"),
				"Report " << U("ip") << " to tracker")
	OPT_NORM
		<< OPT (SKIPHASHCHECK,    4, "", "Skip initial hash check")
		<< OPT (UP,               3, U("rate"),
				"Limit upload speed to " << U("rate") << " kB/s (default: 0 = unlimited)")
	OPT_ADV
		<< OPT (MAXUPLOADS,       7, U("n"),
				"Allow at most " << U("n") << " uploads at once (default: 0 = auto-detect)")
	OPT_NORM
		<< OPT (AUTODIE,          6, "", "Auto-shutdown when done")
	OPT_ADV
		<< OPT (TFBMODE,         16, "", "TorrentFlux-b4rt mode")
		<< "Only if in TorrentFlux-b4rt (" << B("--" << OS_TFBMODE) << ") mode:" << endl
		<< OPT (DISPLAYINTERVAL,  2, U("n"),
				"Update status file every " << U("n") << " seconds (default: " << c_def_displayinterval << ")")
		<< OPT (OWNER,            6, U("fluxuser"),
				"Set " << U("fluxuser") << " as owner of transfer (default: " << c_def_owner << ")")
#if 0	// Even in advanced mode, those don't need to be shown :)
		<< OPTl(CMD,              3, U("name"),
				"Read commands from " << U("name") << " (default: " << U("torrent-file") << ".cmd )")
		<< OPTl(PID,              7, U("name"),
				"Write PID to " << U("name") << "       (default: " << U("torrent-file") << ".pid )")
		<< OPTl(STAT,             6, U("name"),
				"Write status to " << U("name") << "    (default: " << U("torrent-file") << ".stat)")
#endif
#undef P
#undef OPTl
#undef OPT
#undef OPT2
#undef OPTc
#undef U
#undef B
#undef OPT_ADV
#undef OPT_NORM
	;

	cout << ss.str();
}

}
