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
 * TorrentFlux-b4rt stat-file management.
 ******************************************************************************/

#include <fstream>
#include <sstream>
#include <iomanip>

#include "cli.hh"
#include "stat_file.hh"
#include "common.hh"


namespace tfb
{


/******************************************************************************
 * Write out stat file, logging or throwing error.
 ******************************************************************************/

void StatFile::Save(bool allow_failure)
{
	const string msg(DoSave());
	if (msg.empty())
		return;

	if (allow_failure)
		cerr << GetLogHeader() << "error: " << msg << endl;
	else
		throw runtime_error(msg);
}


/******************************************************************************
 * Write out stat file, returning error.
 ******************************************************************************/

string StatFile::DoSave()
{
	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
#define APPEND(v)	ss << (v) << endl
	APPEND(ToStringS(m_state));
	APPEND(m_done);
	APPEND(m_remaining);
	APPEND(m_downrate);
	APPEND(m_uprate);
	APPEND(m_owner);
	APPEND(m_seeds);
	APPEND(m_peers);
	APPEND(m_ratio);
	APPEND(m_sharekill);
	APPEND(m_uptotal);
	APPEND(m_downtotal);
	APPEND(m_size);
#undef APPEND

	ofstream strm(m_path.c_str(), ios_base::out | ios_base::trunc);
	if (strm.fail())
		return "Could not save stat file (\"" + m_path + "\")";

	strm << ss.str() << flush;

	strm.close();
	if (strm.fail())
		return "Could not write stat file (\"" + m_path + "\")";

	return string();
}


/******************************************************************************
 * Format a number.
 ******************************************************************************/

string StatFile::ToStringN(uint64_t number)
{
	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	ss << number;
	return ss.str();
}


/******************************************************************************
 * Format a percent (keep prec digits after decimal separator).
 ******************************************************************************/

string StatFile::ToStringP(double percent, int prec, bool shortbounds)
{
	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	if (percent == numeric_limits< double >::quiet_NaN())
		ss << "n/a";
	else if (percent == numeric_limits< double >::infinity())
		ss << "inf";
	else
	{
		const unsigned int realprec(
			(shortbounds && (percent == 0. || percent == 1.)) ?
				0 :
				max(0, min(9, prec))
		);

		ss << fixed << setprecision(realprec) << percent * 100.;
	}
	return ss.str();
}


/******************************************************************************
 * Format a rate (append xB/s suffix, keep one digit after decimal separator).
 ******************************************************************************/

string StatFile::ToStringR(uint64_t rate)
{
	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	double tmp(rate);
#if 0	// Don't write B/s, always start at kB/s.
	if (tmp < 1000.)
		return (ss << rate << " B/s").str();
#else
	if (tmp > 0. && tmp < 102.4)	// Make sure anything != 0 doesn't show up as 0.
		tmp = 102.4;
#endif
	ss << fixed << setprecision(1);
#define R(d,u)	tmp /= 1024.; if ((d) || tmp < 1000.) { ss << tmp << " " u; return ss.str(); }
	R(false, "kB/s");
	R(false, "MB/s");
	R(false, "GB/s");
	R(true,  "TB/s");	// yeah right, Bridget...
#undef R
}


/******************************************************************************
 * Format a RunningState.
 ******************************************************************************/

string StatFile::ToStringS(RunningState state)
{
	switch (state)
	{
	case tfb::Stopped: return "0";
	case tfb::Running: return "1";
	case tfb::New:     return "2";
	case tfb::Queued:  return "3";
	default:           assert(false); return "?";
	}
}


}
