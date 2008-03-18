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
import time
import socket
import threading
from threading import Thread
from threading import Lock
from Queue import Queue
from Queue import Empty
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.classes.Request import Request
from fluxd.classes.Result import Result
################################################################################

""" ------------------------------------------------------------------------ """
""" SocketRequestDispatcher                                                  """
""" ------------------------------------------------------------------------ """
class SocketRequestDispatcher(object):

    # lock
    InstanceLock = Lock()

    # delim
    DELIM = '\n'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, address, socket, requestHandler, onCloseDelegate = None):

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('RequestDispatcher')

        # set name
        self.__name = name

        # set address
        self.__address = address

        # set socket
        self.__socket = socket

        # request-handler
        self.__requestHandler = requestHandler

        # onClose-delegates
        self.__onCloseDelegates = []
        if not onCloseDelegate is None:
            self.__onCloseDelegates.append(onCloseDelegate)

        # result-queue
        self.__queueResult = Queue()

        # done-flag
        self.__done = False

        # running-flag
        self.__running = False

        # thread
        self.__thread = Thread(target = self.run)
        self.__thread.setName(self.__name)
        self.__thread.setDaemon(True)

    """ -------------------------------------------------------------------- """
    """ run                                                                  """
    """ -------------------------------------------------------------------- """
    def run(self):

        # running-flag
        self.__running = True

        # handle request
        self.handleRequest()

        # get and process result
        while self.__running and not self.__done:

            try:
                # get result from queue
                result = self.__queueResult.get()
                # handle result
                self.handleResult(result)
            except Empty, emp:
                self.logger.debug("result-queue is empty (%s) (%s)" % (self.__name, emp))
            except Exception, e:
                self.logger.error("failed to process queue (%s) (%s)" % (self.__name, e))
                # running-flag
                self.__running = False

        # shutdown
        self.shutdown()

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self):

        # start thread
        self.__thread.start()

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):

        # running-flag
        self.__running = False

        # join
        self.__thread.join(1.0)

        # shutdown if still alive
        if self.__thread.isAlive():
            self.shutdown()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):

        # shutdown socket
        self.logger.debug('shutdown client-socket... (%s)' % self.__name)
        try:
            self.__socket.shutdown(socket.SHUT_RDWR)
        except Exception, e:
            self.logger.error("failed to shutdown socket (%s) (%s)" % (self.__name, e))

        # close socket
        self.logger.debug('close client-socket... (%s)' % self.__name)
        try:
            self.__socket.close()
        except Exception, e:
            self.logger.error("failed to close socket (%s) (%s)" % (self.__name, e))

        # publish event
        for onCloseDelegate in self.__onCloseDelegates:
            try:
                onCloseDelegate(self.__name)
            except Exception, e:
                self.logger.error("failed to publish close-event (%s) (%s)" % (self.__name, e))

        # log
        self.logger.debug('stopped (%s)' % self.__name)

    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        return self.__running and self.__thread.isAlive()

    """ -------------------------------------------------------------------- """
    """ handleRequest                                                        """
    """ -------------------------------------------------------------------- """
    def handleRequest(self):

        # debug
        self.logger.debug('handle request... (%s)' % self.__name)

        # receive
        data = ''
        try:
            data = self.recvall()
        except Exception, e:
            self.logger.error("failed to receive data from client (%s) (%s)" % (self.__name, e))
            # running-flag
            self.__running = False
            # return
            return

        # request-object
        request = Request(self.__name, data, self.requestCallback)

        # invoke request-Handler
        try:
            if self.__requestHandler is None:
                raise Exception, "No Request-Handler (%s)" % self.__name
            self.__requestHandler(request)
        except Exception, e:
            self.logger.error(e)
            # running-flag
            self.__running = False

    """ -------------------------------------------------------------------- """
    """ requestCallback                                                      """
    """ -------------------------------------------------------------------- """
    def requestCallback(self, result):

        # check if we are alive
        if self.isRunning():

            # debug-log
            self.logger.debug("incoming result: %s (%s)" % (result.data, self.__name))

            # put result into queue
            self.__queueResult.put(result)

        else:

            # debug-log
            self.logger.debug("incoming result but we are not alive: %s (%s)" % (result.data, self.__name))

    """ -------------------------------------------------------------------- """
    """ handleResult                                                         """
    """ -------------------------------------------------------------------- """
    def handleResult(self, result):

        try:

            try:

                # data to send back
                data = ''

                # check if error
                if result.exception is None:

                    # debug-log
                    self.logger.debug("handle result: %s (%s)" % (result.data, self.__name))

                    # set data
                    data = result.data

                else:
                    # error to data
                    data = "error occured: %s (%s) (%s)" % (result.data, self.__name, result.exception)

                    # log error
                    self.logger.error(data)

                # send data
                try:
                    # debug-log
                    self.logger.debug("sending data to client: %s (%s)" % (data, self.__name))
                    # send
                    self.sendall(data)
                except Exception, e:
                    self.logger.error("failed to send data to client (%s) (%s)" % (self.__name, e))

            except Exception, e:
                self.logger.error('error when handling result' % e)

        finally:

            # done flag
            self.__done = True

            # running-flag
            self.__running = False

    """ -------------------------------------------------------------------- """
    """ recvall                                                              """
    """ -------------------------------------------------------------------- """
    def recvall(self):
        total_data = []
        data = ''
        while self.__running and not self.__done:
            data = self.__socket.recv(512)
            if SocketRequestDispatcher.DELIM in data:
                total_data.append(data[:data.find(SocketRequestDispatcher.DELIM)])
                break
            total_data.append(data)
            if len(total_data) > 1:
                #check if end_of_data was split
                last_pair = total_data[-2] + total_data[-1]
                if SocketRequestDispatcher.DELIM in last_pair:
                    total_data[-2] = last_pair[:last_pair.find(SocketRequestDispatcher.DELIM)]
                    total_data.pop()
                    break
        return ''.join(total_data)

    """ -------------------------------------------------------------------- """
    """ sendall                                                              """
    """ -------------------------------------------------------------------- """
    def sendall(self, data):
        self.__socket.sendall('%s%s' % (data, SocketRequestDispatcher.DELIM))
