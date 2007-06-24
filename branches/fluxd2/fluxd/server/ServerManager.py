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
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IActivator import IActivator
from fluxd.activator.Activator import Activator
from fluxd.functions.generic import getClassByName
from fluxd.decorators.synchronized import synchronized
from fluxd.interfaces.IServer import IServer
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if ServerManager.Instance is None:
        raise Exception, 'ServerManager not initialized'
    return ServerManager.Instance

""" ------------------------------------------------------------------------ """
""" ServerManager                                                            """
""" ------------------------------------------------------------------------ """
class ServerManager(IActivator):

    # instance
    Instance = None

    # lock
    InstanceLock = Lock()

    # servers
    Servers = {}

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if ServerManager.Instance is None:
            ServerManager.Instance = object.__new__(cls, *p, **k)
        return ServerManager.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('ServerManager')

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ startServer                                                          """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def startServer(self, requestHandler, name = None):
        try:

            # start all servers
            if name is None:
                self.logger.info('Starting all Servers...')
                serverNames = Config().get('server', 'Servers').strip().split(',')
                for name in serverNames:
                    name = name.strip()
                    if Config().get(name, 'enabled').strip() == 'True':
                        try:
                            # check if exists
                            if ServerManager.Servers.has_key(name):
                                raise Exception, "Server does already exist: %s" % (name)
                            # start
                            ServerManager.Servers[name] = getClassByName(Config().get(name, 'module').strip(), Config().get(name, 'class').strip())(name)
                            ServerManager.Servers[name].start(requestHandler, self.onServerStop)
                        except Exception, e:
                            self.logger.error("failed to start Server %s (%s)" % (name, e))

            # start single server
            else:
                self.logger.info('Starting Server %s...' % name)
                if Config().get(name, 'enabled').strip() == 'True':
                    try:
                        # check if exists
                        if ServerManager.Servers.has_key(name):
                            raise Exception, "Server does already exist: %s" % (name)
                        # start
                        ServerManager.Servers[name] = getClassByName(Config().get(name, 'module').strip(), Config().get(name, 'class').strip())(name)
                        ServerManager.Servers[name].start(requestHandler, self.onServerStop)
                    except Exception, e:
                        self.logger.error("failed to start Server %s (%s)" % (name, e))

        except Exception, e:
            self.logger.error("Exception in startServer (%s)" % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ stopServer                                                           """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def stopServer(self, name = None):
        try:

            # stop all servers
            if name is None:
                self.logger.info('Stopping all Servers...')
                serverCopy = ServerManager.Servers.copy()
                for name, server in serverCopy.iteritems():
                    try:
                        self.logger.info('Stopping Server %s...' % name)
                        server.stop()
                    except Exception, e:
                        self.logger.error("failed to stop Server %s (%s)" % (name, e))

            # stop single server
            else:
                self.logger.info('Stopping Server %s...' % name)
                try:
                    if not ServerManager.Servers.has_key(name):
                        raise Exception, "Server does not exist: %s" % (name)
                    self.logger.info('Stopping Server %s...' % (name))
                    ServerManager.Servers[name].stop()
                except Exception, e:
                    self.logger.error("failed to stop Server %s (%s)" % (name, e))

        except Exception, e:
            self.logger.error("Exception in stopServer (%s)" % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ setRequestHandler                                                    """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def setRequestHandler(self, requestHandler, name = None):

        # all servers
        if name is None:
            for name, server in ServerManager.Servers.iteritems():
                try:
                    server.setRequestHandler(requestHandler)
                except Exception, e:
                    self.logger.error("failed to set Request-Handler at Server %s (%s)" % (name, e))

        # single server
        else:
            try:
                if not ServerManager.Servers.has_key(name):
                    raise Exception, "Server does not exist: %s" % (name)
                ServerManager.Servers[name].setRequestHandler(requestHandler)
            except Exception, e:
                self.logger.error("failed to set Request-Handler at Server %s (%s)" % (name, e))

    """ -------------------------------------------------------------------- """
    """ isServerRunning                                                      """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def isServerRunning(self, name = None):

        # all servers
        if name is None:
            for name, server in ServerManager.Servers.iteritems():
                if server.isRunning():
                    return True
            return False

        # single server
        else:
            if ServerManager.Servers.has_key(name):
                return ServerManager.Servers[name].isRunning()
            else:
                return False

    """ -------------------------------------------------------------------- """
    """ runningServers                                                       """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def runningServers(self):
        retVal = []
        try:
            try:
                for name in ServerManager.Servers.iterkeys():
                    retVal.append(name)
            except Exception, e:
                self.logger.error("Exception in runningServers (%s)" % (e))
        finally:
            return retVal

    """ -------------------------------------------------------------------- """
    """ serverStatus                                                         """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def serverStatus(self, name = None):
        try:
            if not ServerManager.Servers.has_key(name):
                raise Exception, "Server does not exist: %s" % (name)
            return ServerManager.Servers[name].status()
        except Exception, e:
            self.logger.error("Exception in serverStatus (%s)" % (e))
            return None

    """ -------------------------------------------------------------------- """
    """ onServerStop                                                         """
    """ -------------------------------------------------------------------- """
    def onServerStop(self, name):
        # log
        self.logger.info("Server stopped: %s" % (name))
        # remove
        try:
            ServerManager.Servers.__delitem__(name)
        except Exception, e:
            self.logger.error("failed to remove Server %s (%s)" % (name, e))
