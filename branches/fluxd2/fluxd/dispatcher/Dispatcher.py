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
import time
import threading
from threading import Thread
from threading import Lock
from Queue import Queue
from Queue import Empty
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IActivator import IActivator
from fluxd.activator.Activator import Activator
from fluxd.dispatcher.RequestHandler import RequestHandler
from fluxd.decorators.synchronized import synchronized
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if Dispatcher.Instance is None:
        raise Exception, 'Dispatcher not initialized'
    return Dispatcher.Instance

""" ------------------------------------------------------------------------ """
""" Dispatcher                                                               """
""" ------------------------------------------------------------------------ """
class Dispatcher(IActivator):

    # instance
    Instance = None

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if Dispatcher.Instance is None:
            Dispatcher.Instance = object.__new__(cls, *p, **k)
        return Dispatcher.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('Dispatcher')

        # onStop-delegates
        self.__onStopDelegates = []

        # request-queue
        self.__queueRequest = Queue()

        # request-handler
        self.__requestHandler = RequestHandler(self.logger)

        # running-flag
        self.__running = False

        # request-count
        self.requestCount = 0

        # thread
        self.__thread = Thread(target = self.run)
        self.__thread.setName(self.__name)
        self.__thread.setDaemon(True)

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ start                                                              """
    """ -------------------------------------------------------------------- """
    def start(self):

        # log
        self.logger.info('Starting...')

        # start thread
        self.__thread.start()

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):

        # running-flag
        self.__running = False

        # join
        self.__thread.join(2.0)

        # shutdown if still alive
        if self.__thread.isAlive():
            self.shutdown()

    """ -------------------------------------------------------------------- """
    """ run                                                                  """
    """ -------------------------------------------------------------------- """
    def run(self):

        # running-flag
        self.__running = True

        # debug
        self.logger.info("up and running")

        # main-loop
        while self.__running:

            # process queue
            try:
                # get and process request
                request = self.__queueRequest.get()
                # handle request
                self.__running = self.__requestHandler.handleRequest(request)
            except Empty, emp:
                self.logger.error("request-queue is empty (%s)" % emp)
            except Exception, e:
                self.logger.error("failed to process request (%s)" % e)

        # shutdown
        self.shutdown()

    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        return self.__running and self.__thread.isAlive()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                               """
    """ -------------------------------------------------------------------- """
    def shutdown(self):

        # log
        self.logger.info('Stopping...')

        # publish stop-event
        for onStopDelegate in self.__onStopDelegates:
            try:
                onStopDelegate()
            except Exception, e:
                self.logger.error("failed to publish shutdown-event (%s)" % e)

        # log
        self.logger.info('stopped')

    """ -------------------------------------------------------------------- """
    """ requestHandler                                                       """
    """ -------------------------------------------------------------------- """
    def requestHandler(self, request):

        # increment request-count
        self.requestCount += 1

        # check if we are running
        if self.isRunning():

            # debug-log
            self.logger.debug("incoming request: %s (%s)" % (request.data, request.sender))

            # put request into queue
            self.__queueRequest.put(request)

        # thread is down
        else:

            # debug-log
            self.logger.debug("incoming request but we are not running (%s)" % request.data)

    """ -------------------------------------------------------------------- """
    """ addOnStopDelegate                                                    """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def addOnStopDelegate(self, onStopDelegate):
        # add onStop-delegate
        if not self.__onStopDelegates.__contains__(onStopDelegate):
            self.__onStopDelegates.append(onStopDelegate)

    """ -------------------------------------------------------------------- """
    """ removeOnStopDelegate                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def removeOnStopDelegate(self, onStopDelegate):
        # remove onStop-delegate
        if self.__onStopDelegates.__contains__(onStopDelegate):
            self.__onStopDelegates.remove(onStopDelegate)
