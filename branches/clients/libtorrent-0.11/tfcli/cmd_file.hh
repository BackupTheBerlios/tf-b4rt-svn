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

#ifndef TFCLILT_CMD_FILE_HH
#define TFCLILT_CMD_FILE_HH


#include <rak/timer.h>
namespace r = rak;

#include <unistd.h>


namespace tfb
{

class CmdFile
{
public:

	//
	// Structors.
	//

	CmdFile(const string& path)
		: m_path(path)
		, m_fd(-1)
	{}

	~CmdFile()
	{
		if (m_fd != -1)
			close(m_fd);
	}


	//
	// API.
	//

	typedef pair< char, string > Command;
	typedef vector< Command > Commands;

	pair< Commands, bool > Perform(const r::timer& now);


	//
	// Implementation.
	//

protected:

	const string m_path;

	r::timer m_readytime;
	int      m_fd;

	bool     Exists() const;
	void     Delete();
	Commands Read(int fd);
	Commands Parse(const string& str);

};

}


#endif
