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
 * Wrapper for a transfer.
 ******************************************************************************/

#ifndef TFCLILT_TRANSFER_CONTEXT_HH
#define TFCLILT_TRANSFER_CONTEXT_HH


#include <torrent/common.h>
namespace t = torrent;
#include <torrent/download.h>

#include <limits>

#include "opts.hh"
#include "stat_file.hh"
#include "cmd_file.hh"


namespace mode_transfer
{


class TransferStats
{
public:

	//
	// C-tor.
	//

	TransferStats(const t::Download& torrent)
		: m_torrent(torrent)
		, m_size(torrent.file_list()->size_bytes())
		, m_size_chunks(torrent.file_list()->size_chunks())
		, m_stat_file(opts::TFBMode() ? new tfb::StatFile(opts::Stat(), m_size) : NULL)
		, m_done(false)
		, m_left(torrent.file_list()->left_bytes())
		, m_completed_chunks(0)
		, m_completion(0.)
		, m_hashed_chunks(0)
		, m_hashing_completion(0.)
		, m_downtotal(0)
		, m_uptotal(0)
		, m_downrate(0)
		, m_uprate(0)
		, m_ratio(numeric_limits< double >::quiet_NaN())
		, m_seeds(0)
		, m_peers(0)
		, m_state(UNKNOWN)
	{
		UpdateCore();
		m_state = STARTING;
		UpdateStatFile(true);
	}


	//
	// Accessors.
	//

	uint64_t Size()              const { return m_size;               }

	enum State
	{
		UNKNOWN,
		STARTING,
		HASHING,
		LEECHING,
		SEEDING,
		STOPPING,
		STOPPED,
		FAILED,
	};
	State    Status()            const { return m_state;              }

	bool     Done()              const { return m_done;               }
	double   Completion()        const { return m_completion;         }
	double   HashingCompletion() const { return m_hashing_completion; }

	double   Ratio()             const { return m_ratio;              }

	const tfb::StatFile* StatFile() const { return m_stat_file.get(); }
	tfb::StatFile*       StatFile()       { return m_stat_file.get(); }


	//
	// Methods.
	//

	void Update(bool stat_file = false);

#define RETIFFAILED()		do { if (m_state == FAILED) return; } while (false)
#define HANDLESTATFILE()	do { if (UpdateStatFile(true)) m_stat_file->Save(); } while (false)
	void SetStarting() { UpdateCore(); RETIFFAILED(); m_state = STARTING; HANDLESTATFILE(); }
	void SetRunning()  {               RETIFFAILED(); m_state = UNKNOWN;  Update(true); }
	void SetStopping() { UpdateCore(); RETIFFAILED(); m_state = STOPPING; HANDLESTATFILE(); }
	void SetStopped()  { UpdateCore(); RETIFFAILED(); m_state = STOPPED;  HANDLESTATFILE(); }
	void SetFailed()   { UpdateCore(); RETIFFAILED(); m_state = FAILED;   HANDLESTATFILE(); }
#undef HANDLESTATFILE
#undef RETIFFAILED


	//
	// Implementation.
	//

protected:
	const t::Download m_torrent;
	const uint64_t    m_size;
	const uint32_t    m_size_chunks;

	const shared_ptr< tfb::StatFile > m_stat_file;

	bool m_done;

	uint64_t m_left;

	uint32_t m_completed_chunks;
	double   m_completion;

	uint32_t m_hashed_chunks;
	double   m_hashing_completion;

	uint64_t m_downtotal;
	uint64_t m_uptotal;
	uint64_t m_downrate;
	uint64_t m_uprate;

	double m_ratio;

	uint32_t m_seeds;
	uint32_t m_peers;

	State m_state;


	// Update amounts/rates/ratio, and return new state.
	State UpdateCore();

	// Write out stat file.
	bool UpdateStatFile(bool first_time = false);
};


class TransferContext
{
public:

	//
	// C-tor.
	//

	TransferContext(t::Download& torrent)
		: m_torrent(torrent)
		, m_stats(torrent)
		, m_cmd_file(opts::TFBMode() ? new tfb::CmdFile(opts::Cmd()) : NULL)
		, m_requested_stop(false)
	{}


	//
	// Accessors.
	//

	const t::Download&   Torrent()  const { return m_torrent;          }
	t::Download          Torrent()        { return m_torrent;          }

	const TransferStats& Stats()    const { return m_stats;            }
	TransferStats&       Stats()          { return m_stats;            }

	const tfb::StatFile* StatFile() const { return m_stats.StatFile(); }
	tfb::StatFile*       StatFile()       { return m_stats.StatFile(); }

	const tfb::CmdFile*  CmdFile()  const { return m_cmd_file.get();   }
	tfb::CmdFile*        CmdFile()        { return m_cmd_file.get();   }

	bool RequestedStop() const              { return m_requested_stop;           }
	void RequestedStop(bool requested_stop) { m_requested_stop = requested_stop; }


	//
	// Implementation.
	//

protected:
	t::Download                      m_torrent;
	TransferStats                    m_stats;
	const shared_ptr< tfb::CmdFile > m_cmd_file;
	bool                             m_requested_stop;

};


}


#endif
