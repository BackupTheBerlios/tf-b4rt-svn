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

#ifndef TFCLILT_STAT_FILE_HH
#define TFCLILT_STAT_FILE_HH


namespace tfb
{

enum RunningState
{
	Stopped = 0,
	Running = 1,
	New     = 2,
	Queued  = 3,
};

class StatFile
{
public:

	//
	// C-tor.
	//

	StatFile(const string& path, uint64_t size)
		: m_path(path)
		, m_state(tfb::New)
		, m_done(ToStringP(0.))
		, m_size(ToStringN(size))
	{}


	//
	// API.
	//

	void Save(bool allow_failure = true);


	//
	// Accessors.
	//

	RunningState  Running()                          const    { return m_state; }
	RunningState& Running()                                   { return m_state; }
	void          Running(RunningState state)                 { m_state = state; }

	const string& Done()                             const    { return m_done; }
	string&       Done()                                      { return m_done; }
	void          Done(const string& done)                    { m_done = done; }
	void          Done(double done)          /* 1. == 100% */ { Done(ToStringP(done < -.5 ?
																				min(-1., max(-2., done)) :
																				max( 0., min( 1., done)))); }

	const string& Remaining()                        const    { return m_remaining; }
	string&       Remaining()                                 { return m_remaining; }
	void          Remaining(const string& remaining)          { m_remaining = remaining; }

	const string& DownRate()                         const    { return m_downrate; }
	string&       DownRate()                                  { return m_downrate; }
	void          DownRate(const string& downrate)            { m_downrate = downrate; }
	void          DownRate(uint64_t downrate)       /* B/s */ { DownRate(ToStringR(downrate)); }

	const string& UpRate()                           const    { return m_uprate; }
	string&       UpRate()                                    { return m_uprate; }
	void          UpRate(const string& uprate)                { m_uprate = uprate; }
	void          UpRate(uint64_t uprate)           /* B/s */ { UpRate(ToStringR(uprate)); }

	const string& Owner()                            const    { return m_owner; }
	string&       Owner()                                     { return m_owner; }
	void          Owner(const string& owner)                  { m_owner = owner; }

	const string& Seeds()                            const    { return m_seeds; }
	string&       Seeds()                                     { return m_seeds; }
	void          Seeds(const string& seeds)                  { m_seeds = seeds; }
	void          Seeds(size_t seeds)                         { Seeds(ToStringN(seeds)); }

	const string& Peers()                            const    { return m_peers; }
	string&       Peers()                                     { return m_peers; }
	void          Peers(const string& peers)                  { m_peers = peers; }
	void          Peers(size_t peers)                         { Peers(ToStringN(peers)); }

	const string& Ratio()                            const    { return m_ratio; }
	string&       Ratio()                                     { return m_ratio; }
	void          Ratio(const string& ratio)                  { m_ratio = ratio; }
	void          Ratio(double ratio)        /* 1. == 100% */ { Ratio(ToStringP(ratio)); }

	const string& ShareKill()                        const    { return m_sharekill; }
	string&       ShareKill()                                 { return m_sharekill; }
	void          ShareKill(const string& sharekill)          { m_sharekill = sharekill; }
	void          ShareKill(long sharekill) /* 100 == 100% */ { ShareKill(ToStringN(max(0L, sharekill))); }

	const string& UpTotal()                          const    { return m_uptotal; }
	string&       UpTotal()                                   { return m_uptotal; }
	void          UpTotal(const string& uptotal)              { m_uptotal = uptotal; }
	void          UpTotal(uint64_t uptotal)                   { UpTotal(ToStringN(uptotal)); }

	const string& DownTotal()                        const    { return m_downtotal; }
	string&       DownTotal()                                 { return m_downtotal; }
	void          DownTotal(const string& downtotal)          { m_downtotal = downtotal; }
	void          DownTotal(uint64_t downtotal)               { DownTotal(ToStringN(downtotal)); }

	const string& Size()                             const    { return m_size; }
	string&       Size()                                      { return m_size; }
	void          Size(const string& size)                    { m_size = size; }
	void          Size(uint64_t size)                         { Size(ToStringN(size)); }


	//
	// Implementation.
	//

protected:

	string       m_path;

	RunningState m_state;
	string       m_done;
	string       m_remaining;
	string       m_downrate;
	string       m_uprate;
	string       m_owner;
	string       m_seeds;
	string       m_peers;
	string       m_ratio;
	string       m_sharekill;
	string       m_uptotal;
	string       m_downtotal;
	string       m_size;


	// Perform save, returning error message.

	string DoSave();


	// Formatting helpers.

	static string ToStringN(uint64_t number);										// Format a number.
	static string ToStringP(double percent, int prec = 1, bool shortbounds = true);	// Format a percentage.
	static string ToStringR(uint64_t rate);											// Format a rate.
	static string ToStringS(RunningState state);									// Format a state.

};

}


#endif
