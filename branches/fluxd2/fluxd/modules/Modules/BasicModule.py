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
import threading
from threading import Thread
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.interfaces.IModule import IModule
from fluxd.decorators.synchronized import synchronized
################################################################################

""" ------------------------------------------------------------------------ """
""" BasicModule                                                              """
""" ------------------------------------------------------------------------ """
class BasicModule(IModule):

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

        # module-main
        try:
            self.main()
        except Exception, e:
            if self.running:
                self.logger.error("Exception in Module-Thread (%s)" % (e))

        # shutdown
        self.shutdown()

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def start(self, requestHandler, onStopDelegate = None):

        # check if already running
        if self.running:
            self.logger.error('start: module already running')
            raise Exception, 'module already running'

        # log
        self.logger.info('Starting Module...')

        # request-handler
        self.requestHandler = requestHandler

        # add onStop-delegate
        if not onStopDelegate is None:
            if not self.onStopDelegates.__contains__(onStopDelegate):
                self.onStopDelegates.append(onStopDelegate)

        # on-start
        self.onStart()

        # start thread
        self.thread.start()

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def stop(self):

        # check if running
        if not self.running:
            self.logger.error('stop: module not running')
            raise Exception, 'module not running'

        # log
        self.logger.info('Stopping Module...')

        # set running flag
        self.running = False

        # join thread
        self.thread.join(2.0)

        # shutdown if still alive
        if self.thread.isAlive():
            self.shutdown()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):

        # on-stop
        self.onStop()

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
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.name

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
        raise Exception, 'IModule.status not implemented'

    """ -------------------------------------------------------------------- """
    """ command                                                              """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def command(self, cmd):
        raise Exception, 'IModule.command not implemented'

    """ -------------------------------------------------------------------- """
    """ getVersion                                                           """
    """ -------------------------------------------------------------------- """
    def getVersion(self):
        raise Exception, 'IModule.getVersion not implemented'

    """ -------------------------------------------------------------------- """
    """ onStart                                                              """
    """ -------------------------------------------------------------------- """
    def onStart(self):
        raise Exception, 'BasicModule.onStart not implemented'

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):
        raise Exception, 'BasicModule.main not implemented'

    """ -------------------------------------------------------------------- """
    """ onStop                                                               """
    """ -------------------------------------------------------------------- """
    def onStop(self):
        raise Exception, 'BasicModule.onStop not implemented'
