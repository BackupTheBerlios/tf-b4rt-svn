################################################################################
# $Id$
# $Date$
# $Revision$
################################################################################
#                                                                              #
# LICENSE                                                                      #
#                                                                              #
# This program is free software; you can redistribute it and/or                #
# modify it under the terms of the GNU General Public License (GPL)            #
# as published by the Free Software Foundation; either version 2               #
# of the License, or (at your option) any later version.                       #
#                                                                              #
# This program is distributed in the hope that it will be useful,              #
# but WITHOUT ANY WARRANTY; without even the implied warranty of               #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                 #
# GNU General Public License for more details.                                 #
#                                                                              #
# To read the license please visit http://www.gnu.org/copyleft/gpl.html        #
#                                                                              #
#                                                                              #
################################################################################

""" ------------------------------------------------------------------------ """
""" transferStart                                                            """
""" ------------------------------------------------------------------------ """
def transferStart(name):

    # imports
    from fluxd.activator.Activator import Activator

    # get Fluxcli-instance
    fluxcli = Activator().getInstance('Fluxcli')

    # invoke fluxcli
    try:
        # invoke and return
        return fluxcli.invoke(['start', name], True).strip()
    except Exception, e:
        raise Exception, "Exception when invoking fluxcli to start transfer %s (%s)" % (name, e)

""" ------------------------------------------------------------------------ """
""" isTransferRunning                                                        """
""" ------------------------------------------------------------------------ """
def isTransferRunning(name):

    # this method is not reliable as it does not count azu-downloads

    # imports
    import os

    # check
    try:

        # ps
        p = os.popen("ps -o pid= -ww")
        ps = p.read().strip()
        p.close()

        # return match
        return (ps.__contains__(name))

    except:
        pass

    # return
    return False
