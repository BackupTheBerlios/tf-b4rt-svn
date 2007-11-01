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
 * Client entry point.
 ******************************************************************************/

#include <rak/timer.h>
namespace r = rak;

#include "cli.hh"
#include "opts.hh"
#include "common.hh"
#include "transfer.hh"
#include "create.hh"
#include "info.hh"


/******************************************************************************
 * Real entry point.
 ******************************************************************************/

int Main(int argc, char** argv)
{
	// Parse command-line.
	const int ret(opts::ParseCommandLine(argc, argv));
	if (ret != EXIT_SUCCESS)
		return ret;

	//ios_base::sync_with_stdio(false);

	// If requested, show help and exit.
	if (opts::Mode() == opts::HELP)
	{
		opts::ShowHelp(argv[0]);
		return EXIT_SUCCESS;
	}

	// Otherwise, a torrent filename must have been specified.
	assert(opts::Torrent() != NULL);

	// Initialize RNGs as required by libtorrent.
	{
		const r::timer tim(r::timer::current());
		const int64_t data64(tim.usec());
		srandom((long int)data64);
		const unsigned short data16v[3] = {
			(unsigned short)(data64 >>  0),
			(unsigned short)(data64 >> 16),
			(unsigned short)(data64 >> 32),
		};
		seed48(const_cast< unsigned short* >(data16v));
	}

	// Call appropriate method depending on mode.
	switch (opts::Mode())
	{
	case opts::TRANSFER:
		return mt::Main();

	case opts::CREATE:
		return mc::Main();

	case opts::INFO:
		return mi::Main();

	default:
		return EXIT_SUCCESS;
	}
}


/******************************************************************************
 * Entry point.
 ******************************************************************************/

int main(int argc, char** argv)
{
	// Invoke real entry point, catching errors.
	try
	{
		try
		{
			return Main(argc, argv);
		}
		catch (exception& rex)
		{
			cerr << GetLogHeader() << argv[0] << ": error: " << rex.what() << endl;
		}
		catch (...)
		{
			cerr << GetLogHeader() << argv[0] << ": unknown error" << endl;
		}
	}
	catch (...)
	{}

	return EXIT_FAILURE;
}
