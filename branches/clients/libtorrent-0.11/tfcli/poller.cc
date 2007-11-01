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

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/poll_epoll.h>
#include <torrent/poll_kqueue.h>
#include <torrent/poll_select.h>
#include <torrent/torrent.h>

#include <rak/error_number.h>
namespace r = rak;

#include "cli.hh"
#include "poller.hh"
#define LAME_FDSETS	m_lame_read.get(), m_lame_write.get(), m_lame_error.get()


//
// Set of polling classes to hide differences between different poll implementations.
// Completely copied from rTorrent's poll_manager*, just slightly refactored to
// minimize code duplication while keeping only one virtual call per invocation.
//
// The whole thing is needlessly complex because libcurl doesn't
// have an epoll/kqueue/...-friendly API (curl_multi_fdset can
// only fill fdsets for use by select(2)).
//[FIXME] LIES!!! or at least, I'm not convinced this is still true...
// at first glance the curl_multi_socket* calls seem quite appropriate
// to me (no clue if they're easy to integrate though :o))
//
// This is done by sharing most of the select(2) implementation
// in the Poller base class, and by deriving from it into two
// branches, depending on the poll implementation:
// - PollerLame, for use with lame poll implementations (select(2)).
//     Always performs select(2) calls (slow when dealing with many fds).
// - PollerLeet, for use with leet poll implementations (epoll(7)/kqueue(2)/...).
//     Performs the leet calls when no curl activity, temporarily
//     falling back to lame select(2) calls while curl is active.
//



namespace
{


/******************************************************************************
 * Poller for use with lame poll implementations.
 * Always performs select(2) calls.
 ******************************************************************************/

template< typename TPoll >
class PollerLame : public Poller
{
public:
	PollerLame(const char* name, const shared_ptr< t::Poll >& poll, c::CurlStack& curl, unsigned int maxfds)
		: Poller(name, poll, curl, maxfds)
	{}

	void perform()
	{
		TPoll& poll(get_poll< TPoll >());

		const r::timer timeout(perform_start());

		// Always go with a select.
		lame_perform(
			timeout,
			// Don't bother with curl fds if no active http request.
			m_curl.is_busy(),
			// Get max fd # from poll implementation.
			poll.fdset(LAME_FDSETS)
		);

		perform_end();
		poll.perform(LAME_FDSETS);
	}
};



/******************************************************************************
 * Poller for use with leet poll implementations.
 * Performs leet (epoll(7)/kqueue(2)/...) calls unless libcurl is
 * active in which case temporarily fall back to select(2) calls.
 ******************************************************************************/

template< typename TPoll >
class PollerLeet : public Poller
{
public:
	PollerLeet(const char* name, const shared_ptr< t::Poll >& poll, c::CurlStack& curl, unsigned int maxfds)
		: Poller(name, poll, curl, maxfds)
	{}

	void perform()
	{
		TPoll& poll(get_poll< TPoll >());

		r::timer timeout(perform_start());

		// Only go lame (select) if curl is active.
		if (lame_need_lameness())
		{
			if (lame_perform(
					timeout,
					// Add poll implementation's leet fd to fd_sets used by select.
					true, 0, poll.file_descriptor()
				))
				return;

			// Update timeout for following poll (some of it, but not
			// necessarily all can have been used by select(2)).
			timeout = perform_get_timeout();
		}

		// Otherwise, or if select showed some activity on teh leet fd, handle it.
		if (poll.poll((timeout.usec() + 999) / 1000) == -1)
		{
			const int poll_errno(errno);
			if (poll_errno == EINTR)
				return;	// Signal, return to main loop asap.
			throw runtime_error("PollerLeet::perform() error: " + string(strerror(poll_errno)));
		}

		perform_end();
		poll.perform();
	}
};


}



/******************************************************************************
 * Create most efficient available polling engine.
 ******************************************************************************/

shared_ptr< Poller > Poller::create(c::CurlStack& curl)
{
	// Get max allowed # of fds.
	const int maxfds(sysconf(_SC_OPEN_MAX));
	if (maxfds < 64)
		throw runtime_error("Insufficient max number of open fds");


	//
	// Then try different libtorrent implementations, in order (factories
	// just return NULL if implementation is not available).
	//

	shared_ptr< t::Poll > poll;
	shared_ptr< Poller >  poller;

	// epoll (Linux >=2.6)
	{
		typedef t::PollEPoll        TPoll;
		typedef PollerLeet< TPoll > TPoller;
		poll.reset(TPoll::create(maxfds));
		if (poll != NULL)
		{
			poller.reset(new TPoller("epoll", poll, curl, maxfds));
			return poller;
		}
	}

	// kqueue (BSD)
	{
		typedef t::PollKQueue       TPoll;
		typedef PollerLeet< TPoll > TPoller;
		poll.reset(TPoll::create(maxfds));
		if (poll != NULL)
		{
			poller.reset(new TPoller("kqueue", poll, curl, maxfds));
			return poller;
		}
	}

	// select (*)
	{
		typedef t::PollSelect       TPoll;
		typedef PollerLame< TPoll > TPoller;
		poll.reset(TPoll::create(maxfds));
		if (poll != NULL)
		{
			poller.reset(new TPoller("select", poll, curl, maxfds));
			return poller;
		}
	}

	throw runtime_error("No available polling engine");
}



/******************************************************************************
 * Common code between lame and leet code paths.
 ******************************************************************************/

r::timer Poller::perform_get_timeout()
{
	// Return libtorrent timeout, clamped to min 1ms
	// to avoid getting stupid, and max 1s.
	return min< int64_t >(1000000, max< int64_t >(1000, t::next_timeout()));
}

r::timer Poller::perform_start()
{
	t::perform();
	return perform_get_timeout();
}

void Poller::perform_end()
{
	t::perform();
}



/******************************************************************************
 * Common select(2) code path, used by lame poller,
 * and by leet one in fallback mode.
 ******************************************************************************/

bool Poller::lame_perform(const r::timer& timeout, bool http, unsigned int max_fd, unsigned int leet_fd)
{
	//
	// First fill fdsets as needed, and get max fd #.
	//

	m_lame_read .zero(m_lame_size);
	m_lame_write.zero(m_lame_size);
	m_lame_error.zero(m_lame_size);

	// Explicit max fd # given.
	unsigned int maxfd(max_fd);

	// Leet fd given, add it to read fdset.
	if (leet_fd != (unsigned int)-1)
	{
		FD_SET(leet_fd, m_lame_read.get());
		if (leet_fd > maxfd)
			maxfd = leet_fd;
	}

	// Handle curl, have it update fdsets and max fd #.
	if (http)
	{
		const unsigned int http_max_fd(
			m_curl.fdset(LAME_FDSETS)
		);
		if (http_max_fd > maxfd)
			maxfd = http_max_fd;
	}

	assert(maxfd != 0);


	//
	// select(2).
	//

	timeval tv(timeout.tval());
	if (select(maxfd + 1, LAME_FDSETS, &tv) == -1)
	{
		const int select_errno(errno);
		if (select_errno == EINTR)
			return true;	// Signal, return to main loop asap.
		throw runtime_error("Poller::lame_perform() error: " + string(strerror(select_errno)));
	}


	//
	// Handle curl: let it do its stuff.
	//

	if (http)
		m_curl.perform();


	//
	// Leet fd given, test whether there is data pending on it.
	//

	if (leet_fd != (unsigned int)-1 && !FD_ISSET(leet_fd, m_lame_read.get()))
	{
		// Call t::perform here so that libtorrent stuff gets done even
		// when there is no other socket activity than http stuff.
		t::perform();

		// No activity on leet fd, means no need to call leet poll implementation.
		return true;
	}


	return false;
}
