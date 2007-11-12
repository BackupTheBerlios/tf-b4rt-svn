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

#ifndef TFCLILT_OPTS_HH
#define TFCLILT_OPTS_HH


#include <rak/socket_address.h>
namespace r = rak;


namespace opts
{


//
// Accessors.
//

enum AppMode
{
	NONE = -1,
	HELP,
	TRANSFER,
	CREATE,
	INFO,
};

enum EncryptionMode
{
	EM__MIN        = 0,
	EM_NONE        = 0,	// No encryption.
	EM_ACCEPT      = 1,	// Allow encrypted in.
	EM_ACTIVE      = 2,	// ACCEPT + try encrypted out (but fallback on non-encrypted on failure).
	EM_REQUIRE     = 3,	// Require encrypted in both dirs (never use unencrypted).
	EM_REQUIREFULL = 4,	// Require RC4-encrypted in both dirs.
	EM__MAX        = 4,
};

// General.
AppMode                  Mode();
const char*              Source();
const char*              Torrent();
unsigned int             Verbose();

// Create mode.
const char*              Announce();
const char*              AnnounceList();
const char*              Comment();
const char*              HTTPSeeds();
const char*              HTTPSeedsGR();
uint32_t                 PieceSize();
bool                     Private();

// Info mode.
bool                     Dump();

// Transfer mode.
const r::socket_address& BindIP();
const r::socket_address& ReportIP();
uint16_t                 PortMin();
uint16_t                 PortMax();
uint64_t                 Down();
uint64_t                 Up();
unsigned long            MaxConnections();
unsigned long            MaxUploads();
EncryptionMode           Encryption();
const char*              EncryptionTxt(EncryptionMode val = opts::Encryption());
bool                     SkipHashCheck();
bool                     AutoDie();
long                     ShareKill();
bool                     TFBMode();
// tfb-mode.
string                   PID();
string                   Cmd();
string                   Stat();
unsigned int             DisplayInterval();
const char*              Owner();


//
// Setters (unchecked -- arg must come from result of a previous Parse* call).
//

void                     Down     (uint64_t val);
void                     Up       (uint64_t val);
void                     AutoDie  (bool     val);
void                     ShareKill(long     val);


//
// Methods.
//

// .first = new value, .second = whether parse was successful.
pair< uint64_t, bool > ParseDown     (const char* arg);
pair< uint64_t, bool > ParseUp       (const char* arg);
pair< bool,     bool > ParseAutoDie  (const char* arg);
pair< long,     bool > ParseShareKill(const char* arg);

int ParseCommandLine(int argc, char** argv);

void ShowHelp(const char* program);


}


#endif
