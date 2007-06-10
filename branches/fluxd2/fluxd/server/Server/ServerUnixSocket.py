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
# standard-imports
import os
import socket
# fluxd-imports
from fluxd.Config import Config
from fluxd.server.Server.ServerGenericSocket import ServerGenericSocket
################################################################################

""" ------------------------------------------------------------------------ """
""" ServerUnixSocket                                                         """
""" ------------------------------------------------------------------------ """
class ServerUnixSocket(ServerGenericSocket):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        ServerGenericSocket.__init__(self, name, *p, **k)

        # socket-path
        self.socketPath = Config().get(name, 'path').strip()

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        data = {}
        data['clientsServed'] = str(self.clientsServed)
        data['path'] = str(self.socketPath)
        return data

    """ -------------------------------------------------------------------- """
    """ getServerSocket                                                      """
    """ -------------------------------------------------------------------- """
    def getServerSocket(self):

        # log
        self.logger.info("create server-socket... (%s)" % self.socketPath)

        # create socket
        sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)

        # bind the socket
        sock.bind(self.socketPath)

        # return the socket
        return sock

    """ -------------------------------------------------------------------- """
    """ cleanupServerSocket                                                  """
    """ -------------------------------------------------------------------- """
    def cleanupServerSocket(self):

        # log
        self.logger.info('remove socket... (%s)' % self.socketPath)

        # remove socket
        try:
            os.remove(self.socketPath)
        except Exception, e:
            self.logger.error("failed to remove socket %s (%s)" % (self.socketPath, e))
