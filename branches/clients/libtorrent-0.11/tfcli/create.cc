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
 * Create mode entry point.
 ******************************************************************************/

#include <torrent/common.h>
namespace t = torrent;
#include <torrent/object.h>

#include "config.h"
#include <utils/sha1.h>

#include <rak/timer.h>
namespace r = rak;

#include <unistd.h>
#include <fcntl.h>
#include <dirent.h>
#include <errno.h>

#include "cli.hh"
#include "opts.hh"
#include "create.hh"
#include "common.hh"


namespace mode_create
{


namespace
{


/******************************************************************************
 * Constants and limits.
 ******************************************************************************/

static const char c_path_delim = '/';

static const unsigned int c_max_pathdepth = 128;

static const char c_announcetier_delims[] = "|;";	// Accept ';' for compatibility with transmissioncli.
static const char c_announceurl_delims[]  = ",";
static const char c_webseeds_delims[]     = "|,;";

static const uint32_t c_min_piecesize =                1024U;	// Min:   1KB.
static const uint32_t c_max_piecesize = 128U * 1024U * 1024U;	// Max: 128MB.

static const uint32_t c_hashbuffersize = 4U * 1024U * 1024U;	// 4MB reads while hashing.


/******************************************************************************
 * Test if source root/item's last path components are acceptable.
 ******************************************************************************/

bool IsValidRoot(const string& part)
{
	if (part.empty() || part == "." || part == "..")
		return false;

	assert(part.find_first_of(c_path_delim) == string::npos);
	if (part.find_first_of(c_path_delim) != string::npos)
		throw runtime_error("Internal error: path still contains delimiter (\"" + part + "\")");

#if 1
	if (part[0] == '.')
		throw runtime_error("Names starting with '.' disabled for security reasons (\"" + part + "\")");
#endif

	return true;
}

bool IsValidItem(const string& part)
{
	if (part.empty())
		throw runtime_error("OS error: readdir() returned empty name");

	if (part == "." || part == "..")
		return false;

	if (part.find_first_of(c_path_delim) != string::npos)
		throw runtime_error("OS error: readdir() returned name containing delimiter (\"" + part + "\")");

#if 1
	if (part[0] == '.')
	{
		if (opts::Verbose() >= 2)
			cerr << GetLogHeader() << "Warning: Skipping source item because name starts with '.' (\"" << part << "\")" << endl;
		return false;
	}
#endif

	return true;
}


/******************************************************************************
 * Get name from path's last component (if needed, prepend cwd and normalize).
 * Note: this prevents from using "/" as path... if you really want to do
 * that, just add a --name option to avoid calling this function :)
 ******************************************************************************/

string GetName(const string& path)
{
	// Try the simple way first.
	do
	{
		// If present, remove delims at end.
		const string::size_type lastgood(path.find_last_not_of(c_path_delim));
		// Only delims, need to normalize.
		if (lastgood == string::npos)
			break;

		// Isolate last path component.
		const string::size_type lastdelim(path.find_last_of(c_path_delim, lastgood));
		const string::size_type firstgood(lastdelim == string::npos ? 0 : lastdelim + 1);
		const string name(path.substr(firstgood, lastgood - firstgood + 1));

		// If not valid, need to normalize.
		if (!IsValidRoot(name))
			break;

		// Otherwise, return it as name.
		return name;
	} while (false);

	// Prepend cwd.
	char wd[1026];
	if (getcwd(wd, countof(wd)) == NULL)
	{
		const int getcwd_errno(errno);
		throw runtime_error("Could not get working dir: " + string(strerror(getcwd_errno)));
	}
	assert(wd[0] != '\0');
	const string fullpath(string(wd) + c_path_delim + path);
	assert(!fullpath.empty() && fullpath[0] == c_path_delim);
	if (fullpath.empty() || fullpath[0] != c_path_delim)
		throw runtime_error("OS error: getcwd() returned invalid working dir (\"" + string(wd) + "\")");

	// Split path into components.
	vector< string > components;
	for (string::size_type pos = 0; ; )
	{
		// Skip slash(es).
		const string::size_type start(fullpath.find_first_not_of(c_path_delim, pos + 1));
		if (start == string::npos)				// Past trailing slash(es).
			break;

		// Find next slash.
		pos = fullpath.find_first_of(c_path_delim, start + 1);
		const bool last(pos == string::npos);	// No trailing slash.

		// Extract and process component.
		const string component(
			fullpath.substr(start, (last ? fullpath.length() : pos) - start)
		);
		assert(!component.empty());
		if (component == "..")
		{
			if (!components.empty())
				components.pop_back();
		}
		else if (!component.empty() && component != ".")
			components.push_back(component);

		if (last)
			break;
	}

	// Return last component.
	if (components.empty())
		throw runtime_error("Empty path, cannot extract torrent name");
	const string ret(components.back());
	if (!IsValidRoot(ret))
		throw runtime_error("Internal error: invalid root path (\"" + ret + "\")");
	return ret;
}


/******************************************************************************
 * Check file size is valid.
 ******************************************************************************/

template< typename T >
uint64_t CheckSize(T size, const string& path)
{
	if (numeric_limits< T >::digits > numeric_limits< uint64_t >::digits ||
		size < T(0) ||
		uint64_t(size) > uint64_t(numeric_limits< int64_t >::max()))
		throw runtime_error("Invalid file size (\"" + path + "\")");

	return uint64_t(size);
}


/******************************************************************************
 * Get best piece size.
 ******************************************************************************/

uint32_t AutoPieceSize(uint64_t size)
{
	// Same algorithm as tornado: (size *= 4) <=> (piecesize *= 2)
	//   >=  32GB    4MB
	//   >=   8GB    2MB
	//   >=   2GB    1MB
	//   >= 512MB  512KB
	//   >=  64MB  256KB
	//   >=  16MB  128KB
	//   >=   4MB   64KB
	//       ----   32KB

	uint32_t ret = 32U * 1024U;

	size /= 4U * 1024U * 1024U;
	while (size > 0ULL && ret < 4U * 1024U * 1024U)
	{
		size >>= 2;
		ret <<= 1;
	}

	assert(ret >= c_min_piecesize && ret <= c_max_piecesize);
	return ret;
}


/******************************************************************************
 * Fill announce-list object.
 ******************************************************************************/

void FillAnnounceList(t::Object& obj_announcelist, const string& announcelist)
{
	// Split into tiers.
	const string::size_type allength(announcelist.length());
	for (string::size_type pos_tier = -1; pos_tier != allength; )
	{
		const string::size_type start_tier(++pos_tier);
		pos_tier = announcelist.find_first_of(c_announcetier_delims, pos_tier);
		if (pos_tier == string::npos)
			pos_tier = allength;

		const string tier(announcelist.substr(start_tier, pos_tier - start_tier));
		if (tier.empty())
			continue;

		t::Object* obj_tier = NULL;

		// Split into urls.
		const string::size_type tlength(tier.length());
		for (string::size_type pos_url = -1; pos_url != tlength; )
		{
			const string::size_type start_url(++pos_url);
			pos_url = tier.find_first_of(c_announceurl_delims, pos_url);
			if (pos_url == string::npos)
				pos_url = tlength;

			const string url(tier.substr(start_url, pos_url - start_url));
			if (url.empty())
				continue;

			// Create tier object if necessary, and add url to it.
			if (obj_tier == NULL)
				obj_tier = &obj_announcelist.insert_back(t::Object(t::Object::TYPE_LIST));
			obj_tier->insert_back(url);
		}
	}

	// If no url, yell.
	if (obj_announcelist.as_list().empty())
		throw runtime_error("No announce URLs given");
}


/******************************************************************************
 * Fill httpseeds or url-list object.
 ******************************************************************************/

void FillWebSeeds(t::Object& obj_webseeds, const string& urllist)
{
	// Split into urls.
	const string::size_type length(urllist.length());
	for (string::size_type pos = -1; pos != length; )
	{
		const string::size_type start(++pos);
		pos = urllist.find_first_of(c_announceurl_delims, pos);
		if (pos == string::npos)
			pos = length;

		const string url(urllist.substr(start, pos - start));
		if (url.empty())
			continue;

		// Add url.
		obj_webseeds.insert_back(url);
	}

	// If no url, yell.
	if (obj_webseeds.as_list().empty())
		throw runtime_error("No webseed URLs given");
}


/******************************************************************************
 * Filelist entry. Stores a file's local path and size until hashing, as well
 * as its obj_files entry in order to sort files by name in FillFiles.
 ******************************************************************************/

struct FileEntry
{
	FileEntry(const string& path, uint64_t size)
		: path(path)
		, size(size)
	{}

	FileEntry(const string& path, uint64_t size, const t::Object& obj_path, const string& name)
		: path(path)
		, size(size)
		, obj_file(new t::Object(t::Object::TYPE_MAP))
	{
		obj_file->insert_key("length", size);
		obj_file->insert_key("path", obj_path).insert_back(name);
	}

	string                  path;
	uint64_t                size;
	shared_ptr< t::Object > obj_file;	// So that t::Object's don't get copied around.

	bool operator==(const FileEntry& rhs) const { return path == rhs.path; }
	bool operator< (const FileEntry& rhs) const { return path <  rhs.path; }

	void swap(FileEntry& rhs)
	{
		using ::std::swap;
		swap(path, rhs.path);
		swap(size, rhs.size);
		obj_file.swap(rhs.obj_file);
	}
};

void swap(FileEntry& lhs, FileEntry& rhs) { lhs.swap(rhs); }

typedef vector< FileEntry > FileEntries;


/******************************************************************************
 * Fill file-related part of info object, and store file list and total size
 * used later for hashing and piece size guessing.
 ******************************************************************************/

struct FillFiles_Context
{
	FillFiles_Context(const string& root)
		: local_path(
			// Prevent '/' from being doubled later.
			root[root.length() - 1] == c_path_delim ?
				root.substr(0, root.length() - 1) : root
		)
		, obj_path(t::Object::TYPE_LIST)
		, dir(NULL)
	{}

	FillFiles_Context(const FillFiles_Context& parent, const string& path, const string& name)
		: local_path(path)
		, obj_path(parent.obj_path)
		, dir(NULL)
	{
		obj_path.insert_back(name);
	}

	~FillFiles_Context()
	{
		if (dir != NULL)
			closedir(dir);
	}

	void Open() { Open(local_path); }
	void Open(const string& path)
	{
		assert(dir == NULL);
		dir = opendir(path.c_str());
		if (dir == NULL)
		{
			const int opendir_errno(errno);
			throw runtime_error("Could not open source dir (\"" + path + "\"): " + string(strerror(opendir_errno)));
		}
	}

	const dirent* Next()
	{
		assert(dir != NULL);
		errno = 0;
		const dirent* const ret(readdir(dir));
		if (ret == NULL)
		{
			const int readdir_errno(errno);
			if (readdir_errno != 0)
				throw runtime_error("Could not read source dir (\"" + local_path + "\"): " + string(strerror(readdir_errno)));
		}
		return ret;
	}

	string    local_path;
	t::Object obj_path;
	DIR*      dir;			// MUST NOT be inited in c-tor.
};

void FillFiles(t::Object& obj_info, const string& source, FileEntries& files, uint64_t& size)
{
	if (opts::Verbose() >= 3)
		cout << GetLogHeader() << "Building file list" << endl;

	struct stat buf;
	if (stat(source.c_str(), &buf) == -1)
	{
		const int stat_errno(errno);
		throw runtime_error("Could not stat source root (\"" + source + "\"): " + string(strerror(stat_errno)));
	}


	//
	// Multi-file mode, glob.
	//

	if (S_ISDIR(buf.st_mode))
	{
		vector< shared_ptr< FillFiles_Context > > ctxts;
		ctxts.reserve(4);

		// Start at root.
		shared_ptr< FillFiles_Context > ctxt(
			new FillFiles_Context(source)
		);
		ctxt->Open(source);

		// Iterate recursively thru dir contents.
		while (true)
		{
			// Get next dir entry.
			const dirent* const dir(ctxt->Next());
			if (dir == NULL)			// End of dir.
			{
				if (ctxts.empty())		// Done.
					break;
				ctxt = ctxts.back();	// Restore parent dir's context.
				ctxts.pop_back();
				continue;
			}

			// Check it is valid.
			const string name(dir->d_name);
			if (!IsValidItem(name))
				continue;
			const string path(ctxt->local_path + c_path_delim + name);

			// Stat it.
			if (stat(path.c_str(), &buf) == -1)
			{
				const int stat_errno(errno);
				throw runtime_error("Could not stat source item (\"" + path + "\"): " + string(strerror(stat_errno)));
			}

			// Subdir.
			if (S_ISDIR(buf.st_mode))
			{
				if (ctxts.size() >= c_max_pathdepth)
					throw runtime_error("Source directory hierarchy too deep (\"" + path + "\")");
				// Push current dir's context and start iterating on child dir's contents.
				ctxts.push_back(ctxt);
				ctxt.reset(
					new FillFiles_Context(*ctxt, path, name)
				);
				ctxt->Open();
			}

			// File.
			else if (S_ISREG(buf.st_mode))
			{
				const uint64_t filesize(CheckSize(buf.st_size, path));
				files.push_back(
					FileEntry(path, filesize, ctxt->obj_path, name)
				);
				size += filesize;
			}

			// Other, yell.
			else
				throw runtime_error("Invalid source item type, neither dir nor file (\"" + path + "\")");
		}

		// If no files, yell.
		if (files.empty())
			throw runtime_error("Source contains no files (\"" + source + "\")");

		// Not actually required, but let's sort filenames, it's better for everyone.
		sort(files.begin(), files.end());

		// And fill files object.
		t::Object& obj_files(
			obj_info.insert_key("files", t::Object(t::Object::TYPE_LIST))
		);
		for (FileEntries::const_iterator it = files.begin(); it != files.end(); ++it)
			obj_files.insert_back(t::Object()).swap(*it->obj_file);
	}


	//
	// Single-file mode.
	//

	else if (S_ISREG(buf.st_mode))
	{
		const uint64_t filesize(CheckSize(buf.st_size, source));
		obj_info.insert_key("length", filesize);
		files.push_back(
			FileEntry(source, filesize)
		);
		size += filesize;
	}


	//
	// Other, yell.
	//

	else
		throw runtime_error("Invalid source root type, neither dir nor file (\"" + source + "\")");
}


/******************************************************************************
 * Fill pieces part of info object.
 ******************************************************************************/

void FillPieces(string& pieces, uint32_t piecesize, const FileEntries& files, uint64_t size)
{
	if (opts::Verbose() >= 3)
		cout << GetLogHeader() << "Hashing files" << endl;


	// Do hashing "by hand". Would be interesting to see if libtorrent's hashing
	// engine (w/ mmaping and read-ahead benefits) can't be (ab)used to do it,
	// although it's not directly exported, so... probably pretty hard.

	const uint64_t nbpieces = (size / piecesize) + ((size % piecesize) != 0ULL ? 1ULL : 0ULL);
	if (nbpieces > 1024ULL * 1024ULL * 1024ULL / 20ULL)
		throw runtime_error("Too many pieces (source too large or piece size too small)");
	pieces.resize(uint32_t(nbpieces * 20ULL));

	uint8_t* const buffer(new uint8_t[c_hashbuffersize]);

	const struct AutoDelete
	{
		AutoDelete(uint8_t* buffer) : m_buffer(buffer) {}
		~AutoDelete() { delete[] m_buffer; }
	protected:
		uint8_t* const m_buffer;
	} autodelete(buffer);

	uint64_t totalpieces = 0ULL;
	uint64_t totalsize   = 0ULL;


	//
	// Iterate on files.
	//

	bool last = false;
	uint32_t donepiecesize = 0U;	// Size already done in current piece (between 0 and piecesize).
	t::Sha1 hash;
	hash.init();
	for (FileEntries::const_iterator it = files.begin(); !last; ++it)
	{
		if (it == files.end())
			last = true;

		const string&  filepath(last ? ""   : it->path);
		const uint64_t filesize(last ? 0ULL : it->size);
		if (!last && opts::Verbose() >= 3)
			cout << GetLogHeader() << "...hashing \"" << filepath << "\" (" << filesize << ")" << endl;


		//
		// Open file,
		//

		static const int flags(O_RDONLY);

		const int fd = last ? -1 : open(filepath.c_str(), flags, 0);
		if (!last && fd == -1)
		{
			const int open_errno(errno);
			throw runtime_error("Could not open source file (\"" + filepath + "\"): " + string(strerror(open_errno)));
		}

		const struct AutoClose
		{
			AutoClose(int fd) : m_fd(fd) {}
			~AutoClose() { if (m_fd != -1) close(m_fd); }
		protected:
			const int m_fd;
		} autoclose(fd);


		//
		// and hash its contents.
		//

		uint64_t remainingfilesize(filesize);
		do
		{
			// Try to read one big chunk from file.
			const bool eof(remainingfilesize == 0ULL);
			const uint32_t wantedsize(
				eof ? 1ULL : uint32_t(min(remainingfilesize, uint64_t(c_hashbuffersize)))
			);
			const ssize_t ret(
				last ? 0 : read(fd, buffer, wantedsize)
			);
			if (!last)
			{
				if (ret == -1)
				{
					const int read_errno(errno);
					if (read_errno == EINTR)
						continue;
					throw runtime_error("Could not read source file (\"" + filepath + "\"): " + string(strerror(read_errno)));
				}
				if (ret == 0 && !eof)
					throw runtime_error("File size decreased during torrent creation (\"" + filepath + "\")");
				if (ret != 0 && eof)
					throw runtime_error("File size increased during torrent creation (\"" + filepath + "\")");
				if (eof)
					break;
			}
			remainingfilesize -= ret;
 
			// And make pieces from that.
			const uint8_t* bufferpos(buffer);
			uint32_t buffersize((uint32_t(ret)));
			if (last || !eof)
				do
				{
					const uint32_t remainingpiecesize(piecesize - donepiecesize);
					assert(remainingpiecesize != 0U && remainingpiecesize <= piecesize);

					// If not at end, hash.
					if (!last)
					{
						const uint32_t hashsize(
							min(remainingpiecesize, buffersize)
						);
						assert(hashsize != 0U);
						hash.update(bufferpos, hashsize);
						donepiecesize += hashsize;
						bufferpos     += hashsize;
						buffersize    -= hashsize;
						totalsize     += hashsize;
						if (totalsize > size)
							throw runtime_error("Internal error: sizes mismatch");
					}

					// If at end or a piece is complete, store its hash.
					if ((last && donepiecesize != 0U) || donepiecesize == piecesize)
					{
						if (totalpieces >= nbpieces)
							throw runtime_error("Internal error: pieces count mismatch");
						hash.final_c(&pieces[totalpieces * 20ULL]);
						if (opts::Verbose() >= 4)
							cout << GetLogHeader() << "Piece #" << totalpieces << " done" << endl;
						totalpieces++;
						donepiecesize = 0U;
						hash.init();
					}
				} while (buffersize != 0U);
		} while (!last);
	}

	if (totalsize != size)
		throw runtime_error("Internal error: sizes mismatch");
	if (totalpieces != nbpieces)
		throw runtime_error("Internal error: pieces count mismatch");
}


/******************************************************************************
 * Fill info object.
 ******************************************************************************/

void FillInfo(t::Object& obj_info)
{
	const string source(opts::Source());
	if (source.empty())
		throw runtime_error("Invalid source");

	// Name.
	obj_info.insert_key("name", GetName(source));

	// Files.
	FileEntries files;
	files.reserve(64);
	uint64_t size = 0ULL;
	FillFiles(obj_info, source, files, size);

	// Piece length.
	uint32_t piecesize = opts::PieceSize();
	if (piecesize == 0U)
		piecesize = AutoPieceSize(size);
	if (piecesize < c_min_piecesize)
		throw runtime_error("Piece size too small");
	if (piecesize > c_max_piecesize)
		throw runtime_error("Piece size too large");
	obj_info.insert_key("piece length", piecesize);

	// Pieces.
	FillPieces(
		obj_info.insert_key("pieces", string()).as_string(),
		piecesize, files, size
	);

	// Private (optional).
	if (opts::Private())
		obj_info.insert_key("private", 1);
}


/******************************************************************************
 * Fill root object.
 ******************************************************************************/

void FillRoot(t::Object& obj_root)
{
	// Announce.
	const char* const announce(opts::Announce());
	if (announce == NULL || *announce == '\0')
		throw runtime_error("No announce URL given");
	obj_root.insert_key("announce", announce);

	// Announce-list (optional).
	const char* const announcelist(opts::AnnounceList());
	if (announcelist != NULL && *announcelist != '\0')
		FillAnnounceList(
			obj_root.insert_key("announce-list", t::Object(t::Object::TYPE_LIST)),
			announcelist
		);

	// Comment (optional).
	const char* const comment(opts::Comment());
	if (comment != NULL && *comment != '\0')
		obj_root.insert_key("comment", comment);

	// Encoding (optional).
	//[FIXME] this probably isn't right... check this
	// maybe only do it if after setlocale(LC_ALL,""), nl_langinfo(CODESET) is "UTF-8"?
	// but then what should we store as encoding in other cases? nothing?
	obj_root.insert_key("encoding", "UTF-8");

	// HTTP seeds (optional).
	const char* const httpseeds(opts::HTTPSeeds());
	if (httpseeds != NULL && httpseeds != '\0')
		FillWebSeeds(
			obj_root.insert_key("httpseeds", t::Object(t::Object::TYPE_LIST)),
			httpseeds
		);
	const char* const httpseedsgr(opts::HTTPSeedsGR());
	if (httpseedsgr != NULL && httpseedsgr != '\0')
		FillWebSeeds(
			obj_root.insert_key("url-list", t::Object(t::Object::TYPE_LIST)),
			httpseedsgr
		);


	// Info.
	FillInfo(
		obj_root.insert_key("info", t::Object(t::Object::TYPE_MAP))
	);


	// Created by (auto).
	obj_root.insert_key("created by", "tfcli-libtorrent v" TFCLILT_VER_STR);

	// (End-of-)Creation date (auto).
	obj_root.insert_key("creation date", r::timer::current().seconds());
}


}



/******************************************************************************
 * Create torrent file.
 ******************************************************************************/

int Main()
{
	// Create and fill root object.
	t::Object obj_root(t::Object::TYPE_MAP);
	FillRoot(obj_root);

	// Save metafile.
	SaveTorrent(opts::Torrent(), obj_root);

	if (opts::Verbose() >= 3)
		cout << GetLogHeader() << "Done" << endl;

	return EXIT_SUCCESS;
}


}
