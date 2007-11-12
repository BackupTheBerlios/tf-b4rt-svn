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
 * Info mode entry point.
 ******************************************************************************/

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/poll.h>
#include <torrent/http.h>
#include <torrent/torrent.h>
#include <torrent/download.h>
#include <torrent/hash_string.h>
#include <torrent/data/file_list.h>
#include <torrent/data/file.h>
#include <torrent/tracker_list.h>
#include <torrent/tracker.h>

#ifndef __UNUSED
#define __UNUSED
#endif
#include <rak/string_manip.h>
namespace r = rak;

#include <sstream>
#include <iomanip>

#include "cli.hh"
#include "opts.hh"
#include "info.hh"
#include "common.hh"


namespace mode_info
{


namespace
{


/******************************************************************************
 * Fake implementations, see comment in Main_Default.
 ******************************************************************************/

void not_impl()
{
	throw runtime_error("Internal error: not implemented");
}

class FakePoll : public t::Poll
{
public:
	uint32_t open_max() const { return 1024; }

	void open (t::Event*) { not_impl(); }
	void close(t::Event*) { not_impl(); }

	bool in_read (t::Event*) { not_impl(); return false; }
	bool in_write(t::Event*) { not_impl(); return false; }
	bool in_error(t::Event*) { not_impl(); return false; }

	void insert_read (t::Event*) { not_impl(); }
	void insert_write(t::Event*) { not_impl(); }
	void insert_error(t::Event*) { not_impl(); }

	void remove_read (t::Event*) { not_impl(); }
	void remove_write(t::Event*) { not_impl(); }
	void remove_error(t::Event*) { not_impl(); }
};

class FakeHTTP : public t::Http
{
public:
	static FakeHTTP* create() { return new FakeHTTP(); }

	void start() { not_impl(); }
	void close() { not_impl(); }
};


}



namespace
{


/******************************************************************************
 * Dump torrent file info.
 ******************************************************************************/

int Main_Default()
{
	// Initialize library.
		// Needed since unfortunately the only exposed way to
		// create a t::Torrent object is to t::download_add,
		// which requires all internal state (objects) to be
		// inited -- although it won't actually be used since
		// torrent is never going to be started.
		// Since we don't have an actual poller / http factory
		// handy, pass a dummy / empty one.
	t::Http::set_factory(sigc::ptr_fun(&FakeHTTP::create));
	FakePoll poll;
	t::initialize(&poll);
	
	try
	{
		// Load torrent metafile and create download.
		shared_ptr< t::Object > metafile(
			LoadTorrent(opts::Torrent())
		);
		t::Download torrent(t::download_add(metafile.get()));
		metafile.detach();

		try
		{
			const char* pfx;


			//
			// Dump generic info.
			//

			cout << "name:       " << torrent.name() << endl;
			const t::HashString& info_hash(torrent.info_hash());
			cout << "hash:       " << r::transform_hex(info_hash.begin(), info_hash.end()) << endl;
			const t::FileList* const file_list(torrent.file_list());
			const uint64_t size_bytes(file_list->size_bytes());
			const uint32_t chunk_size(file_list->chunk_size());
			cout << "size:       " << size_bytes << endl;
			cout << "piece size: " << chunk_size << endl;
			assert(file_list->size_chunks() == (size_bytes / chunk_size) + ((size_bytes % chunk_size) ? 1 : 0));
			cout << "pieces:     " << (size_bytes / chunk_size);
			if (size_bytes % chunk_size)
				cout << " + 1 (" << (size_bytes % chunk_size) << ")";
			cout << endl;


			//
			// Dump tracker info.
			//

			cout << endl;
			if (torrent.is_private())
				cout << "private:    yes" << endl;

			const t::TrackerList tracker_list(torrent.tracker_list());
			const uint32_t trackers(tracker_list.size());
			const bool multi_tracker(trackers > 1);
			if (multi_tracker)
			{
				cout << "trackers (" << trackers << "):" << endl;
				pfx = "    ";
			}
			else if (trackers == 1)
			{
				cout << "tracker:    ";
				pfx = "";
			}
			else
			{
				cout << "no tracker" << endl;
				pfx = "";
			}
			for (uint32_t i = 0; i < trackers; i++)
			{
				cout << pfx;
				if (multi_tracker)	// No need to output group if only one tracker.
					cout << '[' << tracker_list.get(i).group() << "] ";
				cout << tracker_list.get(i).url() << endl;
			}


			//
			// Dump file info.
			//

			cout << endl;
			if (file_list->is_multi_file())
			{
				// Remove leading "./" from root dir if present.
				const string root_dir(file_list->root_dir());
				cout << "directory:  " << (root_dir.compare(0, 2, "./", 2) ? root_dir : root_dir.substr(2)) << endl;

				cout << "files (" << file_list->size_files() << "):" << endl;
				pfx = "    ";
			}
			else
			{
				cout << "file:       ";
				pfx = "";
			}

			for (t::FileList::const_iterator it = file_list->begin(); it != file_list->end(); ++it)
			{
				// Remove leading "/" from path if present.
				const string path((*it)->path()->as_string());
				cout << pfx << ((path.empty() || path[0] != '/') ? path : path.substr(1)) << " (" << (*it)->size_bytes() << ")" << endl;
			}
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
		t::download_remove(torrent);
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
	t::cleanup();

	return EXIT_SUCCESS;
}


/******************************************************************************
 * Dump object raw contents.
 ******************************************************************************/

struct IsPrimitive
{
	typedef t::Object argument_type;
	typedef bool result_type;

	result_type operator()(const argument_type& obj) const
	{
		const t::Object::type_type type(obj.type());
		return
			type == t::Object::TYPE_NONE   ||
			type == t::Object::TYPE_VALUE  || 
			type == t::Object::TYPE_STRING;
	}
};

void Dump(const t::Object& obj, int off = 0)
{
	switch (obj.type())
	{

#if 0
	case t::Object::TYPE_NONE:
		{
			cout << "???";
			break;
		}
#endif

	case t::Object::TYPE_VALUE:
		{
			cout << obj.as_value();
			break;
		}

	case t::Object::TYPE_STRING:
		{
			const t::Object::string_type& cnt(obj.as_string());
			bool bin = false;
			for (t::Object::string_type::const_iterator it = cnt.begin(); !bin && it != cnt.end(); ++it)
				if (!isalnum((int)*it) && !ispunct((int)*it) && !isspace((int)*it))
					bin = true;
			if (!bin)
				cout << '"' << cnt << '"';
			else
			{
				const bool multiline(opts::Verbose() >= 1 && cnt.length() > 20);

				ostringstream ss;
				ss.exceptions(ios_base::badbit | ios_base::failbit);

				ss << hex << uppercase << setfill('0');

				ss << "<";
				if (!cnt.empty())
				{
					if (multiline)
						ss << endl << string(off + 2, ' ');
					unsigned int i = 0;
					for (t::Object::string_type::const_iterator it = cnt.begin(); ; )
					{
						ss << setw(2) << (uint16_t)(uint8_t)*it++;
						if (it == cnt.end())
							break;
						if (!(++i % 20))
						{
							if (multiline)
								ss << endl << string(off + 2, ' ');
							else
							{
								ss << "...";
								break;
							}
						}
					}
					if (multiline)
						ss << endl << string(off, ' ');
				}
				ss << '>';

				cout << ss.str();
			}
			break;
		}

	case t::Object::TYPE_LIST:
		{
			const t::Object::list_type& cnt(obj.as_list());
			if (cnt.empty())
				cout << '(';
			else
			{
				const bool primitive(
					find_if(cnt.begin(), cnt.end(), not1(IsPrimitive())) == cnt.end()
				);
				for (t::Object::list_type::const_iterator it = cnt.begin(); it != cnt.end(); ++it)
				{
					if (primitive)
						cout << (it == cnt.begin() ? "( " : ", ");
					else
						cout << (it == cnt.begin() ? '(' : ',') << endl << string(off + 2, ' ');
					Dump(*it, off + 2);
				}
				if (primitive)
					cout << ' ';
				else
					cout << endl << string(off, ' ');
			}
			cout << ')';
			break;
		}

	case t::Object::TYPE_MAP:
		{
			const t::Object::map_type& cnt(obj.as_map());
			if (cnt.empty())
				cout << '{';
			else
			{
				for (t::Object::map_type::const_iterator it = cnt.begin(); it != cnt.end(); ++it)
				{
					cout << (it == cnt.begin() ? '{' : ',') << endl << string(off + 2, ' ') << '[';
					Dump(it->first, off + 2);
					cout << "] => ";
					Dump(it->second, off + 2);
				}
				cout << endl << string(off, ' ');
			}
			cout << '}';
			break;
		}

	default:
		throw runtime_error("Invalid object type");

	}
}


/******************************************************************************
 * Dump torrent file raw contents.
 ******************************************************************************/

int Main_Dump()
{
	// Load torrent metafile.
	shared_ptr< t::Object > metafile(
		LoadTorrent(opts::Torrent())
	);

	// Dump contents.
	Dump(*metafile);
	cout << endl;

	return EXIT_SUCCESS;
}


}



/******************************************************************************
 * Display torrent file info.
 ******************************************************************************/

int Main()
{
	return opts::Dump() ? Main_Dump() : Main_Default();
}


}
