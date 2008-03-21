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
import sys
import os
import time
import glob
# fluxd-imports
from fluxd.classes.StatFile import StatFile
from fluxd.functions.transfer import transferStart
from fluxd.functions.string import parseInt, parseLong, parseFloat
# Qmgr-imports
from fluxd.modules.Modules.qmgr.QueueEntry import QueueEntry
################################################################################

""" ------------------------------------------------------------------------ """
""" QueueManager                                                             """
""" ------------------------------------------------------------------------ """
class QueueManager(object):

    # delim
    DELIM = '/'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, logger, tf_pathQmgr, tf_pathTransfers, \
        maxTotal, maxDownloadTotal, maxSeedTotal, \
        maxUser, maxDownloadUser, maxSeedUser):
    
        # logger
        self.logger = logger
        
        # main-vars
        self.pathQmgr = tf_pathQmgr
        self.pathTransfers = tf_pathTransfers
        self.maxTotalTransfers = maxTotal
        self.maxTotalDownloadingTransfers = maxDownloadTotal
        self.maxTotalSeedingTransfers = maxSeedTotal
        self.maxUserTransfers = maxUser
        self.maxUserDownloadingTransfers = maxDownloadUser
        self.maxUserSeedingTransfers = maxSeedUser

        # file-vars
        self.fileQueue = self.pathQmgr + "qmgr.queue"
        
        # stats
        self.stats = {}
        self.stats['startCount'] = 0
        
        # transfer-queue
        self._queue = []
        
        # transfers
        #  dict-key: transfer-name
        #  dict-value: statfile-object
        self._transfers = {}
        
        # transfers
        #  dict-key: running|downloading|seeding
        #  dict-value: count of transfers
        self._transferStats = {}
        
        # transfer-stats
        #  dict-key: username
        #  dict-value: dict:
        #   dict-key: running-total|downloading-total|seeding-total|running|downloading|seeding|sf
        #   dict-value: count of transfers or stat-file-object (key = sf)
        self._userStats = {}

        # initialize
        self.initialize()

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

        # info
        self.logger.info("initializing QueueManager (%d/%d/%d %d/%d/%d) ..." \
            % (self.maxTotalTransfers, self.maxTotalDownloadingTransfers, self.maxTotalSeedingTransfers, \
            self.maxUserTransfers, self.maxUserDownloadingTransfers, self.maxUserSeedingTransfers))

        # main-path
        if not os.path.isdir(self.pathQmgr):
            try:
                self.logger.info("main-path %s does not exist, trying to create ..." % self.pathQmgr)
                os.mkdir(self.pathQmgr, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create main-path %s" % self.pathQmgr)
                return False

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self):
    
        # info
        self.logger.info("starting QueueManager")
        
        # load queue
        self._queueLoad()
        
    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):
        
        # info
        self.logger.info("stopping QueueManager")
                
         # save queue
        self._queueSave()
        
    """ -------------------------------------------------------------------- """
    """ queueProcess                                                         """
    """ -------------------------------------------------------------------- """
    def queueProcess(self):
    
        # debug
        self.logger.debug("processing queue...")
        
        # update transfers
        self._updateTransfers()
        
        # QueueEntry-count
        qCount = self.queueCount()
        
        # process the queue if there are entries
        if qCount < 1:
        
            # debug
            self.logger.debug("queue is empty")
        
        else:
        
            # debug
            self.logger.debug("number of entries in queue: %d" % qCount)

            # process it
            self._queueProcessMain()

        # save queue
        self._queueSave()
       
    """ -------------------------------------------------------------------- """
    """ queueAdd                                                             """
    """ -------------------------------------------------------------------- """
    def queueAdd(self, qEntry):
    
        # log
        self.logger.info("adding to queue: %s/%s" % (qEntry.name, qEntry.user))
        
        # check
        for qE in self._queue:

            if qE.name == qEntry.name:
            
                # message
                msg = "entry already exists: %s/%s" % (qEntry.name, qEntry.user)
                
                # log
                self.logger.warning(msg)
                
                # return
                return msg
        
        # add to queue
        self._queue.append(qEntry)
        
        # return
        return "added transfer: %s" % qEntry.name

    """ -------------------------------------------------------------------- """
    """ queueRemove                                                          """
    """ -------------------------------------------------------------------- """
    def queueRemove(self, qEntry):
    
        # log
        self.logger.info("removing from queue: %s/%s" % (qEntry.name, qEntry.user))
        
        # remove
        for qE in self._queue:

            if qE.name == qEntry.name:
                
                # remove from queue
                self._queue.remove(qE)
                
                # return
                return "removed transfer: %s" % qEntry.name
        
        # message
        msg = "entry does not exist: %s/%s" % (qEntry.name, qEntry.user)

        # log
        self.logger.warning(msg)

        # return
        return msg

    """ -------------------------------------------------------------------- """
    """ queueCount                                                           """
    """ -------------------------------------------------------------------- """
    def queueCount(self):
    
        # return
        return len(self._queue)

    """ -------------------------------------------------------------------- """
    """ queueList                                                            """
    """ -------------------------------------------------------------------- """
    def queueList(self):

        # return
        return '; '.join(self._queueList())
        
    """ -------------------------------------------------------------------- """
    """ _queueProcessMain                                                    """
    """ -------------------------------------------------------------------- """
    def _queueProcessMain(self):

        # get a local copy of the queue
        queue = []
        for qEntry in self._queue:
            queue.append(qEntry)

        # process it
        while len(queue) > 0:

            # process next entry
            if self._queueProcessEntry(queue.pop(0)):
                break;
            
    """ -------------------------------------------------------------------- """
    """ _queueProcessEntry                                                   """
    """ -------------------------------------------------------------------- """
    def _queueProcessEntry(self, qEntry):
    
        # debug
        self.logger.debug("processing entry: %s/%s" % (qEntry.name, qEntry.user))
        
        ## get vars ##
        
        # get transfer stats
        runningTotal = 0
        downloadingTotal = 0
        seedingTotal = 0
        if self._transferStats.has_key('running'):
            runningTotal = self._transferStats['running']
        if self._transferStats.has_key('downloading'):
            downloadingTotal = self._transferStats['downloading']
        if self._transferStats.has_key('seeding'):
            seedingTotal = self._transferStats['seeding']

        # DEBUG
        #self.logger.debug("runningTotal: %d" % runningTotal)
        #self.logger.debug("downloadingTotal: %d" % downloadingTotal)
        #self.logger.debug("seedingTotal: %d" % seedingTotal)

        # get user stats
        runningUser = 0
        downloadingUser = 0
        seedingUser = 0
        if self._userStats.has_key(qEntry.user):
            runningUser = self._userStats[qEntry.user]['running']
            downloadingUser = self._userStats[qEntry.user]['downloading']
            seedingUser = self._userStats[qEntry.user]['seeding']

        # DEBUG
        #self.logger.debug("runningUser: %d" % runningUser)
        #self.logger.debug("downloadingUser: %d" % downloadingUser)
        #self.logger.debug("seedingUser: %d" % seedingUser)
        
        # get statfile
        sf = None
        if self._transfers.has_key(qEntry.name):
            sf = self._transfers[qEntry.name]
        else:
            try:
                sf = StatFile("%s%s.stat" % (self.pathTransfers, qEntry.name))
            except Exception, e:
                sf = None
                self.logger.warning("Error loading statfile for transfer %s (%s)" % (name, e))
        
        # get percentage from statfile
        percentage = parseFloat(sf.percent_done)

        # DEBUG
        #self.logger.debug("percentage: %d" % percentage)
        
        ## evaluate ##
        
        ## global limit
        if runningTotal < self.maxTotalTransfers:
            
            # debug
            self.logger.debug("global limit not reached")

        else:

            # debug
            self.logger.debug("global limit reached")
            
            # return
            return True

        ## user limit
        if  runningUser < self.maxUserTransfers:
        
            # debug
            self.logger.debug("user limit not reached")
        
        else:
        
            # debug
            self.logger.debug("user limit reached")
            
            # return
            return False

        # is download ?
        if percentage > -200 and percentage < 100:

            # check global download limit
            if downloadingTotal < self.maxTotalDownloadingTransfers:
            
                # debug
                self.logger.debug("global downloading limit not reached")
            
                # check user download limit
                if downloadingUser < self.maxUserDownloadingTransfers:
                
                    # debug
                    self.logger.debug("user downloading limit not reached")
                    
                    # start it
                    if self._startTransfer(qEntry, sf):

                        # remove it from the queue
                        self.queueRemove(qEntry)
                
                else:
                
                    # debug
                    self.logger.debug("user downloading limit reached")
            
            else:
            
                # debug
                self.logger.debug("global downloading limit reached")

        # is seed ?
        else:

            # check global seeding limit
            if seedingTotal < self.maxTotalSeedingTransfers:
            
                # debug
                self.logger.debug("global seeding limit not reached")
                
                # check user seeding limit
                if seedingUser < self.maxUserSeedingTransfers:
                
                    # debug
                    self.logger.debug("user seeding limit not reached")
                    
                    # start it
                    if self._startTransfer(qEntry, sf):

                        # remove it from the queue
                        self.queueRemove(qEntry)
                
                else:
                
                    # debug
                    self.logger.debug("user seeding limit reached")
            
            else:
            
                # debug
                self.logger.debug("global seeding limit reached")
        
        # return
        return False

    """ -------------------------------------------------------------------- """
    """ _startTransfer                                                       """
    """ -------------------------------------------------------------------- """
    def _startTransfer(self, qEntry, sf):

        # log
        self.logger.info("starting transfer %s" % qEntry.name)
        
        # start it
        try:
            
            # start
            result = transferStart(qEntry.name)
            
            # log
            self.logger.debug('start-result:\n%s' % result)
        
        except Exception, e:
        
            # log
            self.logger.error(e)
            
            # return
            return False
        
        # increment start-counter
        self.stats['startCount'] += 1
        
        # add it to transfers
        self._addTransfer(qEntry.name, qEntry.user, sf)
        
        # return
        return True

    """ -------------------------------------------------------------------- """
    """ _queueLoad                                                           """
    """ -------------------------------------------------------------------- """
    def _queueLoad(self):
    
        # debug
        self.logger.debug("loading queue")
        
        # read in queue-file
        if os.path.isfile(self.fileQueue):
        
            # info
            self.logger.info("loading saved queue %s" % self.fileQueue)
        
            try:

                # read file to mem
                f = open(self.fileQueue, 'r')
                data = f.read()
                f.close()

                # process data
                lines = data.strip().split("\n")
                for line in lines:

                    # strip
                    line = line.strip()
                    
                    # check
                    if line == '':
                        continue

                    # get name and user
                    name = ''
                    user = ''
                    tAry = line.split(QueueManager.DELIM)
                    if len(tAry) == 2:
                        name = tAry[0].strip()
                        user = tAry[1].strip()
                    else:
                         # debug
                        self.logger.debug("skipping transfer in wrong format: %s" % line)
                        # continue
                        continue
                    
                    # check name
                    if name == '':
                        # debug
                        self.logger.debug("skipping transfer with empty name: %s" % line)
                        # continue
                        continue

                    # process transfer
                    file = "%s%s.stat" % (self.pathTransfers, name)
                    try:
                    
                        # load statfile
                        sf = StatFile(file)
                        
                        # only if queued state
                        if not sf.running == '3':
                            # debug
                            self.logger.debug("skipping transfer not in queue state: %s" % name)
                            # continue
                            continue
 
                        # check user
                        if sf.transferowner != '' and sf.transferowner != user:
                            # debug
                            self.logger.debug("updating owner from statfile: %s->%s" % (user, sf.transferowner))
                            user = sf.transferowner
                            
                        # add transfer
                        self.queueAdd(QueueEntry(name, user))
                        
                    except Exception, e:
                        self.logger.error("error when loading statfile %s for transfer %s (%s)" % (file, name, e))

            except Exception, e:
                raise Exception, "_queueLoad: Failed to process file %s (%s)" % (self.fileQueue, e)

        else:
        
            # info
            self.logger.info("no saved queue present %s" % self.fileQueue)
        
    """ -------------------------------------------------------------------- """
    """ _queueSave                                                           """
    """ -------------------------------------------------------------------- """
    def _queueSave(self):
    
        # debug
        self.logger.debug("saving queue")
        
        # content
        content = ''
        for qEntry in self._queue:
            content += "%s%s%s\n" % (qEntry.name, QueueManager.DELIM, qEntry.user)
        
        # write file
        try:
        
            # write
            f = open(self.fileQueue, 'w')
            f.write(content)
            f.flush()
            f.close()
            
            # return
            return True
            
        except Exception, e:
        
            # log
            self.logger.error("Error when saving queue file (%s)" % e)
            
        # return
        return False

    """ -------------------------------------------------------------------- """
    """ _queueList                                                           """
    """ -------------------------------------------------------------------- """
    def _queueList(self):
        
        # retval
        retval = []
        
        # fill
        for qEntry in self._queue:
            retval.append(qEntry.name)
 
        # return
        return retval

    """ -------------------------------------------------------------------- """
    """ _updateTransfers                                                     """
    """ -------------------------------------------------------------------- """
    def _updateTransfers(self):

        # DEBUG
        #self.logger.debug("updating transfers...")

        # reset member
        self._transfers = {}
        self._transferStats = {}
        self._userStats = {}

        # glob silences access errors -- but loss of access to transfers path needs to
        # be flagged explicitely in order to avoid event storms when it comes back.
        if not os.access(self.pathTransfers, os.R_OK | os.X_OK):
            self.logger.warning("Cannot access transfers path: %s" % self.pathTransfers)
            return

        # For every .pid file in transfers path,
        for pidfile in glob.glob("%s*.pid" % self.pathTransfers):

            # extract transfer name,
            name = os.path.splitext(os.path.basename(pidfile))[0]

            # and load stat file.
            sf = None
            try:
                sf = StatFile("%s%s.stat" % (self.pathTransfers, name))
            except Exception, e:
                self.logger.warning("Error loading state for transfer %s (%s)" % (name, e))
                continue
                
            # check transfer-running
            if not sf.running == '1':
                continue
 
            # add it to transfers
            self._addTransfer(name, sf.transferowner, sf)
                
    """ -------------------------------------------------------------------- """
    """ _addTransfer                                                         """
    """ -------------------------------------------------------------------- """
    def _addTransfer(self, name, user, sf = None):
        
        # check user
        if user == '':
        
            # log
            self.logger.warning("transfer has no owner, using n/a (%s)" % name)
            
            # use n/a
            user = 'n/a'
        
        # transfer stats
        if not self._transferStats.has_key('running'):
            self._transferStats['running'] = 0
        if not self._transferStats.has_key('downloading'):
            self._transferStats['downloading'] = 0
        if not self._transferStats.has_key('seeding'):
            self._transferStats['seeding'] = 0
        
        # user stats
        if not self._userStats.has_key(user):
            self._userStats[user] = {}
            self._userStats[user]['running'] = 0
            self._userStats[user]['downloading'] = 0
            self._userStats[user]['seeding'] = 0
        
        # running
        self._userStats[user]['running'] += 1
        self._transferStats['running'] += 1
        
        # sf is provided ?
        if sf == None:
            try:
                sf = StatFile("%s%s.stat" % (self.pathTransfers, name))
            except Exception, e:
                sf = StatFile()
                self.logger.warning("Error loading state for transfer %s (%s)" % (name, e))
                
        # add it to transfers
        self._transfers[name] = sf
        
        # percentage
        percentage = parseFloat(sf.percent_done)
        
        # download/seed
        if percentage > -200 and percentage < 100:
        
            # downloading
            self._userStats[user]['downloading'] += 1
            self._transferStats['downloading'] += 1
            
        else:
        
            # seeding
            self._userStats[user]['seeding'] += 1
            self._transferStats['seeding'] += 1

