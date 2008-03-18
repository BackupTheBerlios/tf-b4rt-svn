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
from threading import Lock
from Queue import Queue
from Queue import Empty
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.modules.Modules.BasicModule import BasicModule
from fluxd.decorators.synchronized import synchronized
# Qmgr-imports
from fluxd.modules.Modules.qmgr.QueueManager import QueueManager
from fluxd.modules.Modules.qmgr.QueueEntry import QueueEntry
from fluxd.modules.Modules.qmgr.QueueRequest import QueueRequest
################################################################################

__version__ = (0, 0, 1)
__version_str__ = '%s.%s' % (__version__[0], ''.join([str(part) for part in __version__[1:]]))

""" ------------------------------------------------------------------------ """
""" Qmgr                                                                     """
""" ------------------------------------------------------------------------ """
class Qmgr(BasicModule):

    # lock
    InstanceLock = Lock()
    
    # delim
    DELIM = ';'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)
        
        # interval
        self.interval = int(Config().getExt(name, 'interval').strip())
        
        # request-queue
        self._queueRequest = Queue()
        
        # Queue-Manager
        self._queueManager = QueueManager(\
            self.logger, \
            Config().get('dir', 'pathFluxd').strip() + "qmgr/", \
            Config().get('dir', 'pathTf').strip() + '.transfers/', \
            int(Config().getExt(name, 'maxTotalTransfers').strip()), \
            int(Config().getExt(name, 'maxUserTransfers').strip()))
            
        # request-map
        self._requestMap = {
            QueueRequest.TYPE_ADD: self._queueManager.queueAdd,
            QueueRequest.TYPE_REMOVE: self._queueManager.queueRemove
        }

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        data = {}
        data['version'] = __version_str__
        data['interval'] = self.interval
        data['maxTotalTransfers'] = self._queueManager.maxTotalTransfers
        data['maxUserTransfers'] = self._queueManager.maxUserTransfers
        data['queueCount'] = str(self._queueManager.queueCount())
        data['queueList'] = self._queueManager.queueList()
        for sn, sv in self._queueManager.stats.iteritems():
            data[sn] = sv
        return data

    """ -------------------------------------------------------------------- """
    """ command                                                              """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def command(self, cmd):

        # log
        self.logger.debug('command: %s' % cmd)

        # stop
        if cmd == 'stop':
            if self.running:
                self.running = False
                return 'initialize Module-shutdown...'
            else:
                return 'Module not running'

        # queue-count
        elif cmd == 'count-queue':
            return self._queueManager.queueCount()

        # queue-list
        elif cmd == 'list-queue':
            return self._queueManager.queueList()
        
        # enqueue
        elif cmd.startswith('enqueue'):
            cmdAry = cmd.strip().split(Qmgr.DELIM)
            if len(cmdAry) == 3:
            
                # get name and user
                name = cmdAry[1].strip()
                user = cmdAry[2].strip()
                
                # request-object
                qRequest = QueueRequest(QueueRequest.TYPE_ADD, QueueEntry(name, user))
                
                # add
                self._requestAdd(qRequest)
                
                # return
                return 'Added Enqueue-Request: %s/%s' % (name, user)
                
            else:
                return 'Enqueue-Command in wrong format: %s' % cmd
        
        # dequeue
        elif cmd.startswith('dequeue'):
            cmdAry = cmd.strip().split(Qmgr.DELIM)
            if len(cmdAry) == 3:
            
                # get name and user
                name = cmdAry[1].strip()
                user = cmdAry[2].strip()
                
                # request-object
                qRequest = QueueRequest(QueueRequest.TYPE_REMOVE, QueueEntry(name, user))
                
                # add
                self._requestAdd(qRequest)
                
               # return
                return 'Added Dequeue-Request: %s/%s' % (name, user)
                
            else:
                return 'Dequeue-Command in wrong format: %s' % cmd
        
        # unknown
        else:
            return 'Command unknown: %s' % cmd

        # return
        return cmd

    """ -------------------------------------------------------------------- """
    """ getVersion                                                           """
    """ -------------------------------------------------------------------- """
    def getVersion(self):
        return __version_str__

    """ -------------------------------------------------------------------- """
    """ onStart                                                              """
    """ -------------------------------------------------------------------- """
    def onStart(self):

        # debug
        self.logger.debug('onStart')
        
        # start QueueManager
        self._queueManager.start()

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # counter
        ctr = self.interval

        # main-loop
        while self.running:

            try:

                # requestProcess
                self._requestProcess(1)
                
                # process queue
                if ctr >= self.interval and self.running:

                    # reset counter
                    ctr = 0

                    # process queue
                    self._processQueue()

                else:
                
                    # increase counter
                    ctr += 1

            except Exception, e:
                if self.running:
                    self.logger.error("Exception in Module-Thread (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ onStop                                                               """
    """ -------------------------------------------------------------------- """
    def onStop(self):

        # debug
        self.logger.debug('onStop')

        # stop QueueManager
        self._queueManager.stop()

        # join queue, only python 2.5
        if hasattr(self.__queueRequest, 'join'):
            try:
                
                # debug
                self.logger.debug("joining request-queue")
                
                # join
                self._queueRequest.join()
                
            except Exception, e:
                self.logger.error("Exception when trying to join the Request-Queue (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ _requestProcess                                                      """
    """ -------------------------------------------------------------------- """
    def _requestProcess(self, timeout):
        
        # check request-queue
        try:

            # try to get request from the queue
            request = self._queueRequest.get(True, timeout)

            # handle request
            self._requestHandle(request)
            
            # queue-task done, only python 2.5
            if hasattr(self._queueRequest, 'task_done'):
                try: 
                    
                    # task_done
                    self._queueRequest.task_done()
                    
                except Exception, e:
                    self.logger.error("Exception when trying to mark task as done in the Request-Queue (%s)" % (e))
            
        except Empty:
        
            # debug
            self.logger.debug("request-queue is empty")

        except Exception, e:
            self.logger.error("Exception in Qmgr-requestCheck (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ _requestAdd                                                          """
    """ -------------------------------------------------------------------- """
    def _requestAdd(self, qRequest):
    
        # add request to queue
        try:
            self._queueRequest.put(qRequest)
        except Exception, e:
            self.logger.error("Exception when trying to put Request to the Request-Queue (%s)(%s)" % (qRequest, e))
        
    """ -------------------------------------------------------------------- """
    """ _requestHandle                                                       """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def _requestHandle(self, qRequest):
        
        # invoke handler, if present call callback with handler-result
        if self._requestMap.has_key(qRequest.type):
            result = self._requestMap[qRequest.type](qRequest.data)
            if qRequest.callback is not None and result is not None:
	            qRequest.callback(result)
 
        # unmapped type
        else:
            self.logger.error("Invalid Request-Type %s" % qRequest.type)
 
    """ -------------------------------------------------------------------- """
    """ _processQueue                                                        """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def _processQueue(self):
    
        # queueManager-queueProcess
        self._queueManager.queueProcess()
