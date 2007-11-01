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

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/torrent.h>
#include <torrent/data/file_list.h>
#include <torrent/rate.h>

#include <sstream>
#include <iomanip>

#include "cli.hh"
#include "opts.hh"
#include "transfer_context.hh"


namespace mode_transfer
{


/******************************************************************************
 * Update stats, and stat file if requested.
 ******************************************************************************/

void TransferStats::Update(bool stat_file)
{
	// Update stats, and write out stat file if asked to or state just changed.

	const State state(
		UpdateCore()
	);
	assert(state != UNKNOWN);

	if (m_state != state)
	{
		m_state = state;
		if (UpdateStatFile(true))
			m_stat_file->Save();
	}
	else if (stat_file)
		if (UpdateStatFile(false))
			m_stat_file->Save();
}


/******************************************************************************
 * Update stats.
 ******************************************************************************/

TransferStats::State TransferStats::UpdateCore()
{
	//
	// Get transfer stats if transfer.
	//

	const t::FileList* const file_list(m_torrent.file_list());

	m_done = file_list->is_done();

	m_left = file_list->left_bytes();
	assert(m_left >= 0ULL && m_left <= m_size);
	assert(m_done == (m_left == 0ULL));

	m_completed_chunks = file_list->completed_chunks();
	m_completion = max(0., min(1.,
		double(m_completed_chunks) / double(max(1U, m_size_chunks))
	));
	assert(m_done == (m_completion == 1.));

	m_hashed_chunks = m_torrent.chunks_hashed();
	m_hashing_completion = max(0., min(1.,
		double(m_hashed_chunks) / double(max(1U, m_size_chunks))
	));

	const t::Rate* const down(m_torrent.down_rate());
	const t::Rate* const up  (m_torrent.up_rate());
	m_downtotal = down->total();
	m_uptotal   = up  ->total();
	m_downrate  = down->rate();
	m_uprate    = up  ->rate();

	m_ratio =
#if 0
		m_downtotal == 0 && m_uptotal == 0 ?
			numeric_limits< double >::quiet_NaN() :
			double(m_uptotal) / double(m_downtotal);
#else
		double(m_uptotal) /
		double(m_downtotal == 0 ? max(uint64_t(1U), m_size) : m_downtotal);
#endif

#if 1
	assert(m_torrent.peers_complete() <= m_torrent.peers_connected());
#endif
	m_seeds = m_torrent.peers_complete();
	const uint32_t peers(m_torrent.peers_connected());
	m_peers = peers > m_seeds ? peers - m_seeds : 0;	// Like other tfb clients, that's actually leechers, not peers.


	//
	// Return transfer state.
	//

	// Those are not auto-detected, keep them once they are set.
	if (m_state == STARTING ||
		m_state == STOPPING || m_state == STOPPED ||
		m_state == FAILED)
		return m_state;

	
	// is_hash_checked() means running.
	if (m_torrent.is_hash_checked())
		return m_done ? SEEDING : LEECHING;

	// is_hash_checking() means, well... hashing.
	if (m_torrent.is_hash_checking())
		return HASHING;

	return UNKNOWN;
}


/******************************************************************************
 * Prepare stat-file for save.
 * Returns true if it should be saved.
 ******************************************************************************/

bool TransferStats::UpdateStatFile(bool first_time)
{
	if (m_stat_file == NULL)
		return false;

	bool done = false, done_rev = false;
	bool downrate = false, uprate = false;
	bool seeds = false, peers = false;
	bool ratio = false;
	bool uptotal = false, downtotal = false;
	switch (m_state)
	{

	case STARTING:
		if (first_time)
		{
			m_stat_file->Running(tfb::Running);
			m_stat_file->Done(0.);
			m_stat_file->Remaining("Starting up...");
			m_stat_file->DownRate().clear();
			m_stat_file->UpRate().clear();
			m_stat_file->Seeds().clear();
			m_stat_file->Peers().clear();
			m_stat_file->Ratio().clear();
			m_stat_file->UpTotal().clear();
			m_stat_file->DownTotal().clear();
		}
		break;

	case HASHING:
		if (first_time)
		{
			m_stat_file->Running(tfb::Running);
			m_stat_file->Remaining("Checking existing data");
			m_stat_file->DownRate().clear();
			m_stat_file->UpRate().clear();
			m_stat_file->Seeds().clear();
			m_stat_file->Peers().clear();
			m_stat_file->Ratio().clear();
			m_stat_file->UpTotal().clear();
			m_stat_file->DownTotal().clear();
		}
		m_stat_file->Done(m_hashing_completion);
		break;

	case LEECHING:
		if (first_time)
		{
			m_stat_file->Running(tfb::Running);
		}
		done      = true;
		{
			// Downloading.
			if (m_downrate >= 1024)
			{
				const uint64_t eta0(m_left / uint64_t(m_downrate));
				if (eta0 <= uint64_t(7 * 86400))	// Only display if <= 1 week.
				{
					const unsigned int eta((unsigned int)eta0);

					ostringstream ss;
					ss.exceptions(ios_base::badbit | ios_base::failbit);

					if (eta >= 86400)				// >= 1 day.
						ss << (eta / 86400) << "d ";
					ss << setfill('0') <<
						setw(2) << ((eta % 86400) / 3600) << ":" <<
						setw(2) << ((eta %  3600) /   60) << ":" <<
						setw(2) << ((eta %    60)       );

					m_stat_file->Remaining(ss.str());
				}
				else
					m_stat_file->Remaining("-");
			}
			// Connecting to peers.
			else// if (m_seeds == 0 && m_peers == 0)
				m_stat_file->Remaining("Connecting to Peers");
			//// Unknown.
			//else
			//	m_stat_file->Remaining("-");
		}
		downrate  = true;
		uprate    = true;
		seeds     = true;
		peers     = true;
		ratio     = true;
		uptotal   = true;
		downtotal = true;
		break;

	case SEEDING:
		if (first_time)
		{
			m_stat_file->Running(tfb::Running);
			m_stat_file->Remaining("Download Succeeded!");
			m_stat_file->DownRate().clear();
		}
		done      = true;
		uprate    = true;
		seeds     = true;
		peers     = true;
		ratio     = true;
		uptotal   = true;
		downtotal = true;
		break;

	case STOPPING:
		if (first_time)
		{
			m_stat_file->Running(tfb::Running);
			m_stat_file->Remaining("Stopping...");
			m_stat_file->DownRate().clear();
		}
		done_rev  = true;
		uprate    = true;
		seeds     = true;
		peers     = true;
		ratio     = true;
		uptotal   = true;
		downtotal = true;
		break;

	case STOPPED:
		if (first_time)
		{
			m_stat_file->Running(tfb::Stopped);
			m_stat_file->Remaining(m_done ? "Download Succeeded!" : "Torrent Stopped");
			m_stat_file->DownRate().clear();
			m_stat_file->UpRate().clear();
			m_stat_file->Seeds().clear();
			m_stat_file->Peers().clear();
		}
		done_rev  = true;
		ratio     = true;
		uptotal   = true;
		downtotal = true;
		break;

	case FAILED:
		if (first_time)
		{
			m_stat_file->Running(tfb::Stopped);
			m_stat_file->Remaining("Download Failed!");
			m_stat_file->DownRate().clear();
			m_stat_file->UpRate().clear();
			m_stat_file->Seeds().clear();
			m_stat_file->Peers().clear();
		}
		done_rev  = true;
		ratio     = true;
		uptotal   = true;
		downtotal = true;
		break;

	default:
		return false;

	}


	// Shared updates.

	if (done)      m_stat_file->Done     (m_completion);
	if (done_rev)  m_stat_file->Done     (m_done ? m_completion : -(1. + m_completion));
	if (downrate)  m_stat_file->DownRate (m_downrate);
	if (uprate)    m_stat_file->UpRate   (m_uprate);
	if (seeds)     m_stat_file->Seeds    (m_seeds);
	if (peers)     m_stat_file->Peers    (m_peers);
	if (ratio)     m_stat_file->Ratio    (m_ratio);
	if (uptotal)   m_stat_file->UpTotal  (m_uptotal);
	if (downtotal) m_stat_file->DownTotal(m_downtotal);


	// Request a stat-file save.

	return true;
}


}
