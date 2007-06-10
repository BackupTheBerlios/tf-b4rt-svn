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
import socket
import threading
from threading import Thread
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.interfaces.IServer import IServer
from fluxd.decorators.synchronized import synchronized
from fluxd.server.Server.SocketRequestDispatcher import SocketRequestDispatcher
################################################################################

""" ------------------------------------------------------------------------ """
""" ServerGenericSocket                                                      """
""" ------------------------------------------------------------------------ """
class ServerGenericSocket(IServer):

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # set name
        self.name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger(self.name)

        # request-handler
        self.requestHandler = None

        # onStop-delegates
        self.onStopDelegates = []

        # running-flag
        self.running = False

        # sockets
        self.serversocket = None
        self.clientsockets = {}

        # dispatcher-threads
        self.dispatcherThreads = {}

        # served-counter
        self.clientsServed = 0

        # listener-thread
        self.thread = Thread(target = self.run)
        self.thread.setName(self.name)
        self.thread.setDaemon(True)

    """ -------------------------------------------------------------------- """
    """ run                                                                  """
    """ -------------------------------------------------------------------- """
    def run(self):

        # log
        self.logger.info("up and running")

        # running-flag
        self.running = True

        # main-loop
        while self.running:

            try:
                # accept connection
                (clientsocket, address) = self.serversocket.accept()

                # handle connection
                self.onConnect(clientsocket, address)

            except Exception, e:
                if self.running:
                    self.logger.error("Exception in Server-Thread (%s)" % (e))

        # shutdown
        self.shutdown()

    """ -------------------------------------------------------------------- """
    """ onConnect                                                            """
    """ -------------------------------------------------------------------- """
    def onConnect(self, clientsocket, address):

        # increment counter
        self.clientsServed += 1

        # get name
        name = id(clientsocket)

        # debug-log
        self.logger.debug('client connected: %s (%s)' % (str(address), str(name)))

        # add socket to dict
        self.clientsockets[name] = clientsocket

        # new request-Dispatcher
        requestDispatcher = SocketRequestDispatcher(
            name,
            address,
            clientsocket,
            self.requestHandler,
            self.onDisconnect)

        # add request-Dispatcher to dict
        self.dispatcherThreads[name] = requestDispatcher

        # start request-Dispatcher
        requestDispatcher.start()

    """ -------------------------------------------------------------------- """
    """ onDisconnect                                                         """
    """ -------------------------------------------------------------------- """
    def onDisconnect(self, name):

        # remove socket from dict
        if self.clientsockets.has_key(name):
            try:
                self.clientsockets.__delitem__(name)
            except Exception, e:
                self.logger.error("failed to remove socket from dict %s (%s)" % (name, e))

        # remove dispatcher from dict
        if self.dispatcherThreads.has_key(name):
            try:
                self.dispatcherThreads.__delitem__(name)
            except Exception, e:
                self.logger.error("failed to remove dispatcher from dict %s (%s)" % (name, e))

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def start(self, requestHandler, onStopDelegate = None):

        # check if already running
        if self.running:
            self.logger.error('start: server already running')
            raise Exception, 'server already running'

        # log
        self.logger.info('Starting Server...')

        # request-handler
        self.requestHandler = requestHandler

        # add onStop-delegate
        if not onStopDelegate is None:
            if not self.onStopDelegates.__contains__(onStopDelegate):
                self.onStopDelegates.append(onStopDelegate)

        # get server-socket
        self.serversocket = self.getServerSocket()

        # listen
        self.serversocket.listen(5)

        # start thread
        self.thread.start()

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def stop(self):

        # check if running
        if not self.running:
            self.logger.error('stop: server not running')
            raise Exception, 'server not running'

        # log
        self.logger.info('Stopping Server...')

        # set running flag
        self.running = False

        # shutdown server-Socket
        self.logger.info('shutdown server-socket...')
        try:
            self.serversocket.shutdown(socket.SHUT_RDWR)
        except Exception, e:
            self.logger.error("failed to shutdown serversocket (%s)" % e)

        # join thread
        self.thread.join(2.0)

        # shutdown if still alive
        if self.thread.isAlive():
            self.shutdown()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):

        # close socket
        self.logger.info('close server-socket...')
        try:
            self.serversocket.close()
        except Exception, e:
            self.logger.error("failed to close serversocket (%s)" % e)

        # stop all dispatcher-threads
        self.logger.info('terminate all dispatcher-threads...')
        for n, d in self.dispatcherThreads.iteritems():
            try:
                self.logger.info('terminate %s' % n)
                d.stop()
            except Exception, e:
                self.logger.error("failed to stop dispatcher-thread (%s)" % (e))

        # shutdown sockets
        self.logger.info('shutdown all client-sockets...')
        for n, s in self.clientsockets.iteritems():
            try:
                self.logger.info('shutdown %s' % n)
                s.shutdown(socket.SHUT_RDWR)
            except Exception, e:
                self.logger.error("failed to close socket (%s)" % (e))

        # close sockets
        self.logger.info('close all client-sockets...')
        for n, s in self.clientsockets.iteritems():
            try:
                self.logger.info('close %s' % n)
                s.close()
            except Exception, e:
                self.logger.error("failed to close socket (%s)" % (e))

        # cleanup server-socket
        self.cleanupServerSocket()

        # publish event
        for onStopDelegate in self.onStopDelegates:
            try:
                onStopDelegate(self.name)
            except Exception, e:
                self.logger.error("failed to publish stop-event for %s (%s)" % (self.name, e))

    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        return self.running and self.thread.isAlive()

    """ -------------------------------------------------------------------- """
    """ setRequestHandler                                                    """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def setRequestHandler(self, requestHandler):
        # set request-handler
        self.requestHandler = requestHandler

    """ -------------------------------------------------------------------- """
    """ addOnStopDelegate                                                    """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def addOnStopDelegate(self, onStopDelegate):
        # add onStop-delegate
        if not self.onStopDelegates.__contains__(onStopDelegate):
            self.onStopDelegates.append(onStopDelegate)

    """ -------------------------------------------------------------------- """
    """ removeOnStopDelegate                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def removeOnStopDelegate(self, onStopDelegate):
        # remove onStop-delegate
        if self.onStopDelegates.__contains__(onStopDelegate):
            self.onStopDelegates.remove(onStopDelegate)

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        raise Exception, 'IServer.status not implemented'

    """ -------------------------------------------------------------------- """
    """ getServerSocket                                                      """
    """ -------------------------------------------------------------------- """
    def getServerSocket(self):
        raise Exception, 'ServerGenericSocket.getServerSocket not implemented'

    """ -------------------------------------------------------------------- """
    """ cleanupServerSocket                                                  """
    """ -------------------------------------------------------------------- """
    def cleanupServerSocket(self):
        raise Exception, 'ServerGenericSocket.cleanupServerSocket not implemented'
