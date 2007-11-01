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
 * TorrentFlux-b4rt pid-file management.
 ******************************************************************************/

#include <unistd.h>
#include <fcntl.h>
#include <sstream>
#include <cerrno>

#include "cli.hh"
#include "opts.hh"
#include "common.hh"
#include "pid_file.hh"


namespace tfb
{


/******************************************************************************
 * Delete PID file on cleanup.
 ******************************************************************************/

PIDFile::~PIDFile()
{
	// D-tor, cannot throw.
	try
	{
		try
		{
			Delete();
		}
		catch (exception& rex)
		{
			cerr << GetLogHeader() << "error: " << rex.what() << endl;
		}
		catch (...)
		{
			cerr << GetLogHeader() << "unknown error" << endl;
		}
	}
	catch (...)
	{}
}


/******************************************************************************
 * Write out PID file.
 ******************************************************************************/

void PIDFile::Save()
{
	const pid_t pid(getpid());

	if (opts::Verbose() >= 1)
		cout << GetLogHeader() << "writing pid-file (pid: " << pid << ")" << endl;

#if 0
	ofstream strm(m_path.c_str(), ios_base::out | ios_base::trunc);
	if (strm.fail())
		throw runtime_error("Could not save PID file: " + m_path);

	strm << pid << endl << flush;

	strm.close();
	if (strm.fail())
		throw runtime_error("Could not write PID file: " + m_path);
#else
	static const int flags(O_CREAT | O_WRONLY | O_TRUNC);
	static const mode_t mode(S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH);

	int fd = open(m_path.c_str(), flags | O_EXCL, mode);
	if (fd == -1 && errno == EEXIST)
	{
		if (opts::Verbose() >= 1)
			cout << GetLogHeader() << "...pid-file exists, overwriting" << endl;

		// Only overwrite if file is a regular file (or a symlink to one).

		struct stat buf;
		if (stat(m_path.c_str(), &buf) == -1)
		{
			const int stat_errno(errno);
			throw runtime_error("Could not stat existing PID file: " + string(strerror(stat_errno)));
		}

		if (!S_ISREG(buf.st_mode))
			throw runtime_error("Existing PID file is not a regular file, will not overwrite it");

		fd = open(m_path.c_str(), flags, mode);
	}
	if (fd == -1)
	{
		const int open_errno(errno);
		throw runtime_error("Could not create PID file: " + string(strerror(open_errno)));
	}

	try
	{
		ostringstream ss;
		ss.exceptions(ios_base::badbit | ios_base::failbit);
		ss << pid << endl;
		const string s(ss.str());

		errno = 0;
		if (write(fd, s.c_str(), s.size()) != ssize_t(s.size()))
		{
			const int write_errno(errno);
			throw runtime_error("Could not write PID file: " + string(strerror(write_errno)));
		}
	}
	catch (...)
	{
		close(fd);
		throw;
	}

	if (close(fd) == -1)
	{
		const int close_errno(errno);
		throw runtime_error("Could not save PID file: " + string(strerror(close_errno)));
	}
#endif

	m_saved = true;
}


/******************************************************************************
 * Delete PID file.
 ******************************************************************************/

void PIDFile::Delete(bool force)
{
	// Log if not in force mode.
	if (!force)
		if (opts::Verbose() >= 1)
			cout << GetLogHeader() << "removing pid-file" << endl;

	if (!force && !m_saved)
		return;						// Nothing to do.

	// Check file exists and is a regular file.
	struct stat buf;
	const bool stat_error(stat(m_path.c_str(), &buf) == -1);
	const int stat_errno(stat_error ? errno : 0);

	// Log if in force mode and file exists or error.
	if (force && (!stat_error || stat_errno != ENOENT))
		if (opts::Verbose() >= 1)
			cout << GetLogHeader() << "removing existing pid-file" << endl;

	if (stat_error)
	{
		if (stat_errno == ENOENT)	// File does not exist.
		{
			if (m_saved)			// If it was saved: warn. Otherwise (force mode): ignore.
			{
				m_saved = false;
				if (opts::Verbose() >= 1)
					cout << GetLogHeader() << "...pid-file does not exist" << endl;
			}
			return;
		}
		throw runtime_error("Could not stat PID file: " + string(strerror(stat_errno)));
	}

	if (!S_ISREG(buf.st_mode))
		throw runtime_error("PID file is not a regular file, will not delete it");

	if (unlink(m_path.c_str()) == -1)
	{
		const int unlink_errno(errno);
		if (unlink_errno != ENOENT)
			throw runtime_error("Could not delete PID file: " + string(strerror(unlink_errno)));
	}
}


}
