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
 * Transfer mode entry point.
 ******************************************************************************/

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/torrent.h>
#include <torrent/download.h>
#include <torrent/data/file_list.h>
#include <torrent/rate.h>
#include <torrent/http.h>
#include <torrent/poll.h>
#include <torrent/connection_manager.h>

#include <rak/timer.h>
namespace r = rak;

#include <csignal>
#include <sstream>
#include <iomanip>

#include "curl_stack.h"
#include "curl_get.h"
namespace c = core;

#include "cli.hh"
#include "opts.hh"
#include "transfer.hh"
#include "transfer_context.hh"
#include "signals.hh"
#include "poller.hh"
#include "common.hh"
#include "pid_file.hh"


namespace mode_transfer
{


namespace logging
{


/******************************************************************************
 * Log startup phase.
 ******************************************************************************/

void LogStartup()
{
	if (opts::Verbose() < 1)
		return;

	const string header(GetLogHeader());

	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	ss << boolalpha;

	ss << header << "tfcli-libtorrent starting up:" << endl;
	ss << header << "- torrent-file: " << opts::Torrent() << endl;

	// TFB stuff.
	if (opts::TFBMode())
	{
		ss << header << "- pid-file: " << opts::PID() << endl;
		ss << header << "- cmd-file: " << opts::Cmd() << endl;
		ss << header << "- stat-file: " << opts::Stat() << endl;
		ss << header << "- display-interval: " << opts::DisplayInterval() << "s" << endl;
		ss << header << "- owner: " << opts::Owner() << endl;
	}

	// Torrent stuff.
	ss << header << "- die-when-done: " << opts::AutoDie() << endl;
	if (!opts::AutoDie())
	{
		ss << header << "- seed-limit: ";
		if (opts::ShareKill() != 0L)
			ss << opts::ShareKill() << "%";
		else
			ss << "none";
		ss << endl;
	}
	ss << header << "- skip-hash-check: " << opts::SkipHashCheck() << endl;

	// Low-level network stuff.
	if (opts::PortMin() != opts::PortMax())
	{
		ss << header << "- min-port: " << opts::PortMin() << endl;
		ss << header << "- max-port: " << opts::PortMax() << endl;
	}
	else
		ss << header << "- port: " << opts::PortMin() << endl;
	if (opts::BindIP().is_bindable())
		ss << header << "- bind-ip: " << opts::BindIP().address_str() << endl;
	if (!opts::ReportIP().is_address_any())
		ss << header << "- report-ip: " << opts::ReportIP().address_str() << endl;

	// High-level network stuff.
	ss << header << "- max-download-speed: ";
	if (opts::Down() != 0ULL)
		ss << opts::Down() << " kB/s";
	else
		ss << "unlimited";
	ss << endl;
	ss << header << "- max-upload-speed: ";
	if (opts::Up() != 0ULL)
		ss << opts::Up() << " kB/s";
	else
		ss << "unlimited";
	ss << endl;
	ss << header << "- max-connections: ";
	if (opts::MaxConnections() != 0UL)
		ss << opts::MaxConnections();
	else
		ss << "auto";
	ss << endl;
	ss << header << "- max-uploads: ";
	if (opts::MaxUploads() != 0UL)
		ss << opts::MaxUploads();
	else
		ss << "auto";
	ss << endl;
	ss << header << "- encryption: " << opts::EncryptionTxt() << endl;

	cout << ss.str();
}


/******************************************************************************
 * Log torrent status for convenience.
 ******************************************************************************/

void LogStatus(const mt::TransferStats& stats)
{
	if (opts::Verbose() < 4)
		return;

	const string header(GetLogHeader());

	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	ss << header;

	switch (stats.Status())
	{

	case mt::TransferStats::UNKNOWN:
		ss << "# unknown" << endl;
		break;

	case mt::TransferStats::STARTING:
		ss << "# starting" << endl;
		break;

	case mt::TransferStats::HASHING:
		ss << "# hashing: " << fixed <<
			  setw(4) << setprecision(1) <<
			  (stats.HashingCompletion() * 100.) << "%" << endl;
		break;

	case mt::TransferStats::LEECHING:
		ss << "# leeching: " << fixed <<
			  setw(4) << setprecision(1) <<
			  (stats.Completion() * 100.) << "% @ " <<
			  setw(0) << setprecision(2) <<
			  stats.Ratio() << endl;
		break;

	case mt::TransferStats::SEEDING:
		ss << "# seeding: " << fixed <<
			  setw(0) << setprecision(2) <<
			  stats.Ratio() << endl;
		break;

	case mt::TransferStats::STOPPING:
		ss << "# stopping" << endl;
		break;

	case mt::TransferStats::STOPPED:
		ss << "# stopped" << endl;
		break;

	case mt::TransferStats::FAILED:
		ss << "# failed" << endl;
		break;

	default:
		assert(false);
		ss << "# unknown status" << endl;

	}

	cout << ss.str();
}


}

namespace l = logging;


namespace handlers
{


/******************************************************************************
 * Initial hashcheck done: start torrent.
 ******************************************************************************/

void hash_done(mt::TransferContext& transfer)
{
	if (opts::Verbose() >= 2)
		cout << GetLogHeader() << "> hashing done, starting transfer" << endl;

	transfer.Torrent().start2(0);
}


/******************************************************************************
 * Download done: log, and stop if in die-when-done mode.
 ******************************************************************************/

void download_done(mt::TransferContext& transfer)
{
	if (opts::Verbose() >= 1)
		cout << GetLogHeader() << "download complete" << endl;
}


/******************************************************************************
 * Tracker error: log and continue.
 ******************************************************************************/

void tracker_failed(const string& msg, mt::TransferContext& transfer)
{
	if (opts::Verbose() >= 1)
		if (msg != "Tried all trackers." || opts::Verbose() >= 3)	// Ugly...
			cout << GetLogHeader() << "tracker error: " << msg << endl;
}


/******************************************************************************
 * Network error: log and continue.
 ******************************************************************************/

void network_log(const string& msg, mt::TransferContext& transfer)
{
	if (opts::Verbose() >= 1)
		cout << GetLogHeader() << "network error: " << msg << endl;
}


/******************************************************************************
 * Storage error: abort.
 ******************************************************************************/

void storage_error(const string& msg, mt::TransferContext& transfer)
{
	if (opts::Verbose() >= 1)
		cout << GetLogHeader() << "storage error: " << msg << endl;

	transfer.Stats().SetFailed();

	throw runtime_error("Storage error: " + msg);
}


}


namespace
{


/******************************************************************************
 * Update libtorrent global speed limits.
 ******************************************************************************/

void UpdateSpeedLimits()
{
	t::set_down_throttle(int32_t(opts::Down()));
	t::set_up_throttle  (int32_t(opts::Up()  ));
}


/******************************************************************************
 * Get libtorrent encryption flags.
 ******************************************************************************/

uint32_t GetEncryptionFlags()
{
#define E(f) t::ConnectionManager::encryption_##f
	switch (opts::Encryption())
	{
	case opts::EM_NONE:        return E(none);
	case opts::EM_ACCEPT:      return E(allow_incoming) | E(prefer_plaintext);
	case opts::EM_ACTIVE:      return E(allow_incoming) | E(try_outgoing) | E(enable_retry);
	case opts::EM_REQUIRE:     return E(allow_incoming) | E(try_outgoing) | E(require);
	case opts::EM_REQUIREFULL: return E(allow_incoming) | E(try_outgoing) | E(require) | E(require_RC4);
	default:                   assert(false); throw runtime_error("Invalid encryption value"); return 0;
	}
#undef E
}


/******************************************************************************
 * Process one command.
 ******************************************************************************/

void ProcessCommand(mt::TransferContext& transfer, const tfb::CmdFile::Command& command)
{
	// If already going to exit, don't even bother.
	if (transfer.RequestedStop())
		return;

	const char    cmd (command.first );
	const string& args(command.second);

	ostringstream ss;
	ss.exceptions(ios_base::badbit | ios_base::failbit);
	ss << boolalpha;

	switch (cmd)
	{

	// q (quit).
	case 'q':
		if (opts::Verbose() >= 1)
			cout << GetLogHeader() << "...command: quit, setting shutdown flag..." << endl;
		transfer.RequestedStop(true);
		break;

	// d (down).
	case 'd':
		{
			const pair< uint64_t, bool > ret(opts::ParseDown(args.c_str()));
			if (!ret.second)
				cerr << GetLogHeader() << "error: " << "Invalid argument for command `" << cmd << "': " << args << endl;
			else
			{
				if (opts::Verbose() >= 1)
				{
					ss << GetLogHeader() << "...command: max-download-speed, setting to ";
					if (ret.first != 0ULL)
						ss << ret.first << " kB/s";
					else
						ss << "unlimited";
					if (opts::Verbose() >= 2)
						ss << "...";
					ss << endl;
					cout << ss.str();
				}

				opts::Down(ret.first);	// Change global setting.
				UpdateSpeedLimits();	// And apply it.
			}
		}
		break;

	// u (up).
	case 'u':
		{
			const pair< uint64_t, bool > ret(opts::ParseUp(args.c_str()));
			if (!ret.second)
				cerr << GetLogHeader() << "error: " << "Invalid argument for command `" << cmd << "': " << args << endl;
			else
			{
				if (opts::Verbose() >= 1)
				{
					ss << GetLogHeader() << "...command: max-upload-speed, setting to ";
					if (ret.first != 0ULL)
						ss << ret.first << " kB/s";
					else
						ss << "unlimited";
					if (opts::Verbose() >= 2)
						ss << "...";
					ss << endl;
					cout << ss.str();
				}

				opts::Up(ret.first);	// Change global setting.
				UpdateSpeedLimits();	// And apply it.
			}
		}
		break;

	// r/w (autodie).
	case 'r':
	case 'w':
		{
			const pair< bool, bool > ret(opts::ParseAutoDie(args.c_str()));
			if (!ret.second)
				cerr << GetLogHeader() << "error: " << "Invalid argument for command `" << cmd << "': " << args << endl;
			else
			{
				if (opts::Verbose() >= 1)
				{
					ss << GetLogHeader() << "...command: die-when-done, setting to " << ret.first;
					if (opts::Verbose() >= 2)
						ss << "...";
					ss << endl;
					cout << ss.str();
				}

				opts::AutoDie(ret.first);	// Change global setting (nothing to apply).
			}
		}
		break;

	// s/l (sharekill).
	case 's':
	case 'l':
		{
			const pair< long, bool > ret(opts::ParseShareKill(args.c_str()));
			if (!ret.second)
				cerr << GetLogHeader() << "error: " << "Invalid argument for command `" << cmd << "': " << args << endl;
			else
			{
				if (opts::Verbose() >= 1)
				{
					ss << GetLogHeader() << "...command: seed-limit, setting to ";
					if (ret.first != 0L)
						ss << ret.first << "%";
					else
						ss << "none";
					if (opts::Verbose() >= 2)
						ss << "...";
					ss << endl;
					cout << ss.str();
				}

				opts::ShareKill(ret.first);	// Change global setting (nothing to apply).
			}
		}
		break;

	// unknown.
	default:
		cerr << GetLogHeader() << "error: " << "Unknown command (`" << cmd << "'), ignoring" << endl;

	}
}


/******************************************************************************
 * Main transfer loop.
 ******************************************************************************/

void DoTransfer(c::CurlStack& curl, Poller& poller, mt::TransferContext& transfer)
{
	const double sharekill(double(opts::ShareKill()) / 100.);

	t::Download torrent(transfer.Torrent());
	mt::TransferStats& stats(transfer.Stats());
	tfb::CmdFile* cmd_file(transfer.CmdFile());

	bool global_stopping = false;
	bool stopping = false;
	r::timer timer_stop;

	while (torrent.is_hash_checking() || !t::is_inactive())
	{
		// Check stats, stop / log as needed.
		do
		{
			const r::timer timer_current(r::timer::current());

			// Only do stuff every second.
			static r::timer timer_last;
			if ((timer_current - timer_last).usec() < 999000)
				break;
			timer_last = timer_current;

			// Save stat-file at requested interval.
			static r::timer timer_last_save;
			const bool save(
				(timer_current - timer_last_save).usec() >= opts::DisplayInterval() * 1000000 - 1000
			);
			if (save)
				timer_last_save = timer_current;

			// Update stats, saving stat-file if appropriate.
			stats.Update(save);

			// Log.
			l::LogStatus(stats);

			// Process cmd-file if needed.
			if (cmd_file != NULL)
			{
				const pair< tfb::CmdFile::Commands, bool > commands(
					cmd_file->Perform(timer_current)
				);
				if (commands.second)
				{
					for (tfb::CmdFile::Commands::const_iterator it = commands.first.begin();
						 it != commands.first.end();
						 ++it)
						ProcessCommand(transfer, *it);
					if (opts::Verbose() >= 2)
						cout << GetLogHeader() << "...done" << endl;
				}
			}

			// Check if sharekill is reached.
			if (!transfer.RequestedStop() && stats.Done())
			{
				bool die = false;
				if (opts::AutoDie())
				{
					if (opts::Verbose() >= 1)
						cout << GetLogHeader() << "die-when-done set, setting shutdown flag..." << endl;
					die = true;
				}
				else if (opts::ShareKill() > 0 && stats.Ratio() >= sharekill)
				{
					if (opts::Verbose() >= 1)
						cout << GetLogHeader() << "seed-limit (" << opts::ShareKill() <<
								") reached, setting shutdown flag..." << endl;
					die = true;
				}

				if (die)
					transfer.RequestedStop(true);
			}
		} while (false);

		// Handle stop requests.
		if (transfer.RequestedStop() || signals::RequestedStop())
		{
			const string header(opts::Verbose() >= 1 ? GetLogHeader() : string());

			if (signals::RequestedStop() && !global_stopping)
			{
				// Log received signal.
				global_stopping = true;
				if (opts::Verbose() >= 1)
					cout << header << "received " << signals::Name(signals::Signal()) << ", shutting down" <<
							(signals::RequestedStopFast() ? " quickly" : "") << "..." << endl;
			}

			if (!stopping)
			{
				// Stop as requested.
				stopping = true;
				timer_stop = r::timer::current();

				if (opts::Verbose() >= 2)
					cout << header << "> stopping" << endl;

				stats.SetStopping();
				torrent.stop2(0);
				torrent.hash_stop();
			}
			else
			{
				// Make sure the whole evening isn't spent stopping -- allow 30s, unless
				// quick stop requested (hard signal) in which case allow only 3s.
				const int32_t delta((r::timer::current() - timer_stop).seconds());
				if (delta < 0 || delta > (signals::RequestedStopFast() ? 3 : 30))
				{
					if (opts::Verbose() >= 3)
						cout << header << "* transfer still active, shutting down anyway" << endl;
					break;
				}
			}
		}

		// Let libtorrent / libcurl live their life.
		poller.perform();
	}
}


}



/******************************************************************************
 * Perform transfer.
 ******************************************************************************/

int Main()
{
	// Log.
	l::LogStartup();

	// Install signal handlers.
	if (opts::Verbose() >= 3)
		cout << GetLogHeader() << "* setting up signal handlers" << endl;
	try
	{
		signals::Configure(true);

		// Initialize HTTP stuff.
		if (opts::Verbose() >= 3)
			cout << GetLogHeader() << "* initializing libcurl" << endl;
		try
		{
			c::CurlStack::global_init();

			c::CurlStack curl;
			t::Http::set_factory(curl.get_http_factory());

			// Create polling engine.
			if (opts::Verbose() >= 3)
				cout << GetLogHeader() << "* creating libtorrent polling engine" << endl;
			shared_ptr< Poller > poller(
				Poller::create(curl)
			);
			if (opts::Verbose() >= 3)
				cout << GetLogHeader() << "* using `" << poller->name() << "' engine" << endl;

			// Initialize library.
			if (opts::Verbose() >= 3)
				cout << GetLogHeader() << "* initializing libtorrent" << endl;
			if (opts::Verbose() >= 1)
				cout << GetLogHeader() << "using libtorrent v" << t::version() << endl;
			t::initialize(&poller->get_poll< t::Poll >());

			try
			{
				// Initialize IPs and port(s).
				if (opts::Verbose() >= 3)
					cout << GetLogHeader() << "* binding to listening port" << endl;
				if (opts::BindIP().is_bindable())
					t::connection_manager()->set_bind_address(opts::BindIP().c_sockaddr());
				if (!opts::ReportIP().is_address_any())
					t::connection_manager()->set_local_address(opts::ReportIP().c_sockaddr());
				if (!t::connection_manager()->listen_open(opts::PortMin(), opts::PortMax()))
					throw runtime_error("Could not open port for listening");

				try
				{
					// Load torrent metafile and create download.
					if (opts::Verbose() >= 3)
						cout << GetLogHeader() << "* loading torrent metafile" << endl;
					shared_ptr< t::Object > metafile(
						LoadTorrent(opts::Torrent())
					);
					if (opts::Verbose() >= 3)
						cout << GetLogHeader() << "* creating libtorrent download" << endl;
					t::Download torrent(t::download_add(metafile.get()));
					metafile.detach();

					try
					{
						// Set torrent options.
						t::connection_manager()->set_encryption_options(GetEncryptionFlags());
						torrent.set_pex_enabled(true);

						// Set network options.
						UpdateSpeedLimits();
						if (opts::MaxConnections() > 0)
						{
							torrent.set_peers_min((opts::MaxConnections() + 1) / 2);
							torrent.set_peers_max(opts::MaxConnections());
						}
						torrent.set_uploads_max(opts::MaxUploads());	// set_uploads_max already converts 0 to unlimited.

						// Things are looking good, initialize file facilities as needed.
						const shared_ptr< tfb::PIDFile > pid_file(
							opts::TFBMode() ? new tfb::PIDFile(opts::PID()) : NULL
						);
						mt::TransferContext transfer(torrent);
						tfb::StatFile* const stat_file(transfer.StatFile());
						if (stat_file != NULL)
						{
							stat_file->Owner(opts::Owner());
							stat_file->ShareKill(opts::ShareKill());
							stat_file->Save(false);	// First stat-file save must succeed.
						}

						// Initialize download.
						torrent.signal_download_done (sigc::bind(
							sigc::ptr_fun(&handlers::download_done ), sigc::ref(transfer)
						));
						torrent.signal_hash_done     (sigc::bind(
							sigc::ptr_fun(&handlers::hash_done     ), sigc::ref(transfer)
						));
						torrent.signal_tracker_failed(sigc::bind(
							sigc::ptr_fun(&handlers::tracker_failed), sigc::ref(transfer)
						));
						torrent.signal_network_log   (sigc::bind(
							sigc::ptr_fun(&handlers::network_log   ), sigc::ref(transfer)
						));
						torrent.signal_storage_error (sigc::bind(
							sigc::ptr_fun(&handlers::storage_error ), sigc::ref(transfer)
						));

						// Start it.
						if (opts::Verbose() >= 3)
							cout << GetLogHeader() << "* initializing libtorrent download" << endl;
						torrent.open();

						try
						{
							// Hash-check it.
							if (opts::SkipHashCheck())
							{
								if (opts::Verbose() >= 2)
									cout << GetLogHeader() << "> skipping hashing" << endl;
								// Skip hash check by pretending all chunks have been hash-checked.
								torrent.set_bitfield(true);
							}
							else
							{
								if (opts::Verbose() >= 2)
									cout << GetLogHeader() << "> starting hashing" << endl;
							}

							bool need_full_hash_check = true;
#ifndef TFCLILT_OPT_NO_QUICK_HASH
							// Not entirely sure what this "quick" hash-check thing does...
							if (torrent.hash_check(true))
								need_full_hash_check = false;
							else
							{
								if (opts::Verbose() >= 3)
									cout << GetLogHeader() << "* quick hashing failed, trying full hashing" << endl;
								torrent.hash_stop();
							}
#endif
							if (need_full_hash_check)
								torrent.hash_check(false);

							// Switch to running mode.
							transfer.Stats().SetRunning();

							if (opts::Verbose() >= 1)
								cout << GetLogHeader() << "tfcli-libtorrent up and running" << endl;

							// Main loop.
							DoTransfer(curl, *poller, transfer);
						}
						catch (...)
						{
							try
							{
								transfer.Stats().SetStopped();
								if (opts::Verbose() >= 3)
									cout << GetLogHeader() << "* aborting libtorrent download" << endl;
								torrent.stop2(0);
								torrent.hash_stop();
								torrent.close();
							}
							catch (...)
							{}
							throw;
						}

						transfer.Stats().SetStopped();
						if (opts::Verbose() >= 3)
							cout << GetLogHeader() << "* closing libtorrent download" << endl;
						torrent.stop2(0);
						torrent.hash_stop();
						torrent.close();
					}
					catch (...)
					{
						try
						{
							// Remove download.
							t::download_remove(torrent);
						}
						catch (...)
						{}
						throw;
					}

					// Remove download.
					if (opts::Verbose() >= 3)
						cout << GetLogHeader() << "* deleting libtorrent download" << endl;
					t::download_remove(torrent);
				}
				catch (...)
				{
					try
					{
						// Close listening port.
						t::connection_manager()->listen_close();
					}
					catch (...)
					{}
					throw;
				}

				// Close listening port.
				if (opts::Verbose() >= 3)
					cout << GetLogHeader() << "* closing listening port" << endl;
				t::connection_manager()->listen_close();
			}
			catch (...)
			{
				try
				{
					// Cleanup.
					t::cleanup();
				}
				catch (...)
				{}
				throw;
			}

			// Cleanup.
			if (opts::Verbose() >= 3)
				cout << GetLogHeader() << "* shutting down libtorrent" << endl;
			t::cleanup();
		}
		catch (...)
		{
			try
			{
				// curl cleanup.
				c::CurlStack::global_cleanup();
			}
			catch (...)
			{}
			throw;
		}

		// curl cleanup.
		if (opts::Verbose() >= 3)
			cout << GetLogHeader() << "* shutting down libcurl" << endl;
		c::CurlStack::global_cleanup();
	}
	catch (...)
	{
		try
		{
			// Reset signals.
			signals::Configure(false);
		}
		catch (...)
		{}
		throw;
	}

	// Reset signals.
	signals::Configure(false);

	if (opts::Verbose() >= 1)
		cout << GetLogHeader() << "tfcli-libtorrent exit" << endl;
	return EXIT_SUCCESS;
}


}
