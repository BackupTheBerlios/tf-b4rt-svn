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
 * Common helpers shared between modes.
 ******************************************************************************/

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/object.h>
#include <torrent/object_stream.h>

#include <ctime>
#include <fstream>

#include "cli.hh"
#include "opts.hh"


/******************************************************************************
 * Build log header (timestamp if in tfb mode).
 ******************************************************************************/

string GetLogHeader()
{
	if (!opts::TFBMode())
		return string();

	const time_t t(time(NULL));
	tm tim;
	if (localtime_r(&t, &tim) == NULL)
		return string();

	char buf[42];
	const int ret(
		strftime(buf, countof(buf), "[%Y/%m/%d - %H:%M:%S] ", &tim)
	);
	if (ret == 0 || ret == countof(buf))
		return string();

	return buf;
}


/******************************************************************************
 * Load torrent metafile.
 ******************************************************************************/

shared_ptr< t::Object > LoadTorrent(const char* path)
{
	ifstream strm(path, ios_base::in | ios_base::binary);
	if (strm.fail())
		throw runtime_error("Could not load torrent file (\"" + string(path) + "\")");

	const shared_ptr< t::Object > ret(
		new t::Object()
	);
	strm >> *ret;

	if (strm.fail())
		throw runtime_error("Could not read torrent file (\"" + string(path) + "\")");

	return ret;
}


/******************************************************************************
 * Save torrent metafile.
 ******************************************************************************/

void SaveTorrent(const char* path, const t::Object& obj)
{
	ofstream strm(path, ios_base::out | ios_base::trunc | ios_base::binary);
	if (strm.fail())
		throw runtime_error("Could not save torrent file (\"" + string(path) + "\")");

	strm << obj;

	if (strm.fail())
		throw runtime_error("Could not write torrent file (\"" + string(path) + "\")");
}
