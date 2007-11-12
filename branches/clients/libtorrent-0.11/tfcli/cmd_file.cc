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
 * TorrentFlux-b4rt cmd-file management.
 ******************************************************************************/

#include <unistd.h>
#include <fcntl.h>
#include <cerrno>

#include "cli.hh"
#include "opts.hh"
#include "common.hh"
#include "cmd_file.hh"


namespace tfb
{


/******************************************************************************
 * If there is some cmd file contents to process, return it.
 ******************************************************************************
 * Implementation is a tad complicated to try to avoid racing with the webapp:
 * if the client just opens/reads/unlinks the cmd file, it can very well miss
 * commands (written by webapp between client's read and its unlink) -- and
 * that's not just a vague possibility, it does actually happen.
 * So when a cmd file is detected:
 * - open it and keep the fd open (m_fd),
 * - unlink it,
 * - wait a bit (m_readytime -- delay is 1s now, may need to be adjusted),
 * - read it and close it.
 * That way if the webapp didn't perform its write when the client opened the
 * file, it will within the client's wait time. If new commands arrive within
 * that time, a new cmd file will be created for them, so they are no problem.
 *
 * Note that this requires sane filesystem semantics (i.e. unlinked files must
 * still exist and be useable normally while there are open fds on them),
 * meaning it might very well not work on SMB/NFS.
 ******************************************************************************/

pair< CmdFile::Commands, bool > CmdFile::Perform(const r::timer& now)
{
	Commands ret;
	bool present = false;

	// If there is a pending cmd file, see if it is time to actually read it.
	if (m_fd != -1)
	{
		if (now < m_readytime)	// Not yet, nothing to do.
			return make_pair(ret, present);

		// It's time, read it.
		if (opts::Verbose() >= 2)
			cout << GetLogHeader() << "...reading cmd-file..." << endl;
		const int fd(m_fd);
		m_fd = -1;
		ret = Read(fd);
		present = true;
	}

	assert(m_fd == -1);

	// Check if cmd file exists.
	if (Exists())
	{
		// It does, open and unlink it, keeping fd open.
		if (opts::Verbose() >= 1)
			cout << GetLogHeader() << "detected cmd-file, " <<
					(opts::Verbose() >= 2 ? "waiting" : "reading") << "..." << endl;

		static const int flags(O_RDONLY);

		m_readytime = now + r::timer::from_seconds(1);	// Could prolly be lowered a bit.
		m_fd = open(m_path.c_str(), flags, 0);
		if (m_fd == -1)
		{
			const int open_errno(errno);
			if (open_errno == ENOENT)
			{
				if (opts::Verbose() >= 1)
					cout << GetLogHeader() << "...cmd-file no longer exists, ignoring" << endl;
			}
			else
				cerr << GetLogHeader() << "error: " <<
						"Could not open cmd file (\"" << m_path << "\"): " <<
						strerror(open_errno) << endl;

			return make_pair(ret, present);
		}

		Delete();
	}

	return make_pair(ret, present);
}


/******************************************************************************
 * Check for cmd file presence.
 ******************************************************************************/

bool CmdFile::Exists() const
{
	// Check file exists and is a regular file.
	struct stat buf;
	const bool stat_error(stat(m_path.c_str(), &buf) == -1);
	const int stat_errno(stat_error ? errno : 0);

	if (stat_error)
	{
		// File does not exist.
		if (stat_errno == ENOENT)
			return false;

		cerr << GetLogHeader() << "error: " <<
				"Could not stat cmd file (\"" << m_path << "\"): " <<
				strerror(stat_errno) << endl;
		return false;
	}

	if (!S_ISREG(buf.st_mode))
		throw runtime_error("Cmd file is not a regular file, will not go on (\"" + m_path + "\")");

	return true;
}


/******************************************************************************
 * Delete cmd file. Only call after cmd file has been checked (i.e. exists
 * and is confirmed to be a regular file).
 ******************************************************************************/

void CmdFile::Delete()
{
	if (unlink(m_path.c_str()) == -1)
	{
		const int unlink_errno(errno);
		if (unlink_errno != ENOENT)
			cerr << GetLogHeader() << "error: " <<
					"Could not delete cmd file (\"" << m_path << "\"): " <<
					strerror(unlink_errno) << endl;
	}
}


/******************************************************************************
 * Read from cmd file and close it.
 ******************************************************************************/

CmdFile::Commands CmdFile::Read(int fd)
{
	// Whatever happens, close fd on exit.
	const struct AutoClose
	{
		AutoClose(int fd) : m_fd(fd) {}
		~AutoClose() { close(m_fd); }
	protected:
		int m_fd;
	} guard(fd);

	// Read commands.
	uint8_t buf[4096];
	size_t len = 0;
	while (true)
	{
		assert(len < sizeof(buf));
		const ssize_t siz(read(fd, buf + len, sizeof(buf) - len));
		assert(siz <= ssize_t(sizeof(buf) - len));
		if (siz == -1)
		{
			const int read_errno(errno);
			cerr << GetLogHeader() << "error: " <<
					"Could not read cmd file (\"" << m_path << "\"): " <<
					strerror(read_errno) << endl;
			return Commands();
		}
		else if (siz == 0)
			break;

		len += siz;
		if (len >= sizeof(buf))
		{
			cerr << GetLogHeader() << "error: " <<
					"Could not read cmd file (\"" << m_path << "\"): " <<
					"File is too large" << endl;
			return Commands();
		}
	}

	// Split contents into commands.
	return Parse(string(buf, buf + len));
}


/******************************************************************************
 * Split cmd file contents into individual commands.
 ******************************************************************************/

CmdFile::Commands CmdFile::Parse(const string& str)
{
	static const char seps[] = "\r\n";
	static const char ws[]   = " \t";

	Commands ret;

	const size_t len(str.size());
	size_t pos = 0;
	while (true)
	{
		// Find start of next line.
		pos = str.find_first_not_of(seps, pos);
		if (pos == string::npos || pos >= len)
			break;

		// Find end of line.
		size_t nextpos(str.find_first_of(seps, pos));
		if (nextpos == string::npos)
			nextpos = len;
		assert(nextpos > pos);

		// Get line.
		const string line(str.substr(pos, nextpos - pos));
		const size_t linelen(line.size());
		const size_t cmdpos(line.find_first_not_of(ws));
		if (cmdpos != string::npos && cmdpos < linelen)
		{
			// Split into cmd/arg.
			const char cmd(line[cmdpos]);
			string arg;
			const size_t argpos(line.find_first_not_of(ws, cmdpos + 1));
			if (argpos != string::npos && argpos < linelen)
			{
				const size_t endpos(line.find_last_not_of(ws));
				if (endpos != string::npos && endpos >= argpos)
					arg = line.substr(argpos, endpos + 1 - argpos);
			}
			ret.push_back(make_pair(cmd, arg));
		}

		// Advance.
		pos = nextpos;
	}

	return ret;
}


}
