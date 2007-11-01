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
 * fd poller for use by libtorrent.
 ******************************************************************************/

#ifndef TFCLILT_POLLER_HH
#define TFCLILT_POLLER_HH


#include <torrent/common.h>
namespace t = torrent;
#include <torrent/poll.h>

#include <rak/timer.h>
namespace r = rak;

#include "curl_stack.h"
#include "curl_get.h"
namespace c = core;

#include <sys/select.h>


class Poller
{
public:

	// Factory.
	static shared_ptr< Poller > create(c::CurlStack& curl);

	// D-tor.
	virtual ~Poller() {}

	// Entry point.
	virtual void perform() = 0;


	// Helpers.

	const char* name() const { return m_name; }

	template< typename TPoll >
	TPoll& get_poll() const { return static_cast< TPoll& >(*m_poll); }


	//
	// Implementation.
	//

protected:
	// C-tor.
	Poller(const char* name, const shared_ptr< t::Poll >& poll, c::CurlStack& curl, unsigned int maxfds)
		: m_name(name)
		, m_poll(poll)
		, m_curl(curl)
		, m_lame_size(lame_size(maxfds))
		, m_lame_read (m_lame_size)
		, m_lame_write(m_lame_size)
		, m_lame_error(m_lame_size)
	{}

	// Common code.
	r::timer perform_get_timeout();
	r::timer perform_start();
	void     perform_end();


	// Implementation display name.
	const char* const m_name;

	// libtorrent poll implementation.
	const shared_ptr< t::Poll > m_poll;

	// http stack.
	c::CurlStack& m_curl;


	// --- lame begin ---

	// Size of fdsets for use with select(2).
	static unsigned int lame_size(unsigned int maxfds)
	{
		assert(maxfds >= 8);
		if (
#ifdef TFCLILT_OPT_NO_VARIABLE_FDSETS
			maxfds > FD_SETSIZE ||
#endif
			maxfds > 1048576 * 8
			)
			throw runtime_error("Too large max number of open fds");

		return (maxfds + 7) / 8;
	}

	// Wrapper around fd_set to hide differences between standard
	// impl (fixed-size) and non-portable variable-sized impl.
	class LameFDSet
	{
	public:
		LameFDSet(unsigned int size)
#ifdef TFCLILT_OPT_NO_VARIABLE_FDSETS
			: m_set(new fd_set())
#else
			: m_set((fd_set*)new uint8_t[size])
#endif
		{}

		~LameFDSet()
		{
#ifdef TFCLILT_OPT_NO_VARIABLE_FDSETS
			delete m_set;
#else
			delete[] (uint8_t*)m_set;
#endif
		}

		fd_set* get() const { return m_set; }

		void zero(unsigned int size) const
		{
#ifdef TFCLILT_OPT_NO_VARIABLE_FDSETS
			FD_ZERO(m_set);
#else
			memset(m_set, 0, size);
#endif
		}

	protected:
		fd_set* const m_set;
	};

	// fdsets for use with select(2).
	const unsigned int m_lame_size;		// Must be defined before fdsets.
	const LameFDSet    m_lame_read;
	const LameFDSet    m_lame_write;
	const LameFDSet    m_lame_error;

	// Need to switch to lame mode?
	bool lame_need_lameness() const { return m_curl.is_busy(); }

	// Perform lame select, handling http if needed. In case a leet_fd is provided,
	// returns true if nothing more to do (i.e. there is no activity on it).
	bool lame_perform(const r::timer& timeout, bool http = false, unsigned int max_fd = 0, unsigned int leet_fd = (unsigned int)-1);

	// ---- lame end ----
};


#endif
