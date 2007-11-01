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
 * Client signals management.
 ******************************************************************************/

#include <csignal>

#include "cli.hh"
#include "signals.hh"


namespace signals
{


/******************************************************************************
 * Global signal-related variables.
 ******************************************************************************/

namespace
{

int  p_signal              = 0;
bool p_requested_stop_fast = false;
bool p_requested_stop      = false;

}


/******************************************************************************
 * Global signal-related variables.
 ******************************************************************************/

int  Signal()            { return p_signal;              }
bool RequestedStopFast() { return p_requested_stop_fast; }
bool RequestedStop()     { return p_requested_stop;      }



namespace
{


/******************************************************************************
 * Signal handler: request normal stop.
 ******************************************************************************/

void HandleSignalNorm(int sig)
{
	if (!p_requested_stop)
		p_signal = sig;
	p_requested_stop = true;
}


/******************************************************************************
 * Signal handler: request quick stop.
 ******************************************************************************/

void HandleSignalFast(int sig)
{
	if (!p_requested_stop_fast)
		p_signal = sig;
	p_requested_stop_fast = true;
	p_requested_stop      = true;
}


}


/******************************************************************************
 * Install / reset signal handlers.
 ******************************************************************************/

void Configure(bool init)
{
	signal(SIGPIPE, init ? SIG_IGN           : SIG_DFL);

	signal(SIGINT,  init ? &HandleSignalNorm : SIG_DFL);

	signal(SIGHUP,  init ? &HandleSignalFast : SIG_DFL);
	signal(SIGQUIT, init ? &HandleSignalFast : SIG_DFL);
	signal(SIGTERM, init ? &HandleSignalFast : SIG_DFL);
}


/******************************************************************************
 * Get signal's name.
 ******************************************************************************/

const char* Name(int sig)
{
	switch (sig)
	{
#define SN(s)	case SIG##s:	return "SIG"#s;
		SN(HUP);
		SN(INT);
		SN(QUIT);
		SN(ILL);
		SN(ABRT);
		SN(FPE);
		SN(KILL);
		SN(SEGV);
		SN(PIPE);
		SN(ALRM);
		SN(TERM);
		SN(BUS);
#undef SN
				default:		return "unknown signal";
	}
}


}
