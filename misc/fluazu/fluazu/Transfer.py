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
# fluazu
from fluazu.output import printMessage, printError, getOutput, getPrefix
from fluazu.StatFile import StatFile
################################################################################

""" ------------------------------------------------------------------------ """
""" Transfer                                                                 """
""" ------------------------------------------------------------------------ """
class Transfer(object):

    """ tf states """
    TF_STOPPED = 0
    TF_RUNNING = 1
    TF_NEW = 2
    TF_QUEUED = 3

    """ azu states """
    AZ_DOWNLOADING = 4
    AZ_ERROR = 8
    AZ_PREPARING = 2
    AZ_QUEUED = 9
    AZ_READY = 3
    AZ_SEEDING = 5
    AZ_STOPPED = 7
    AZ_STOPPING = 6
    AZ_WAITING = 1

    """ azu -> flu map """
    STATE_MAP = { \
        AZ_DOWNLOADING: TF_RUNNING, \
        AZ_ERROR: TF_STOPPED, \
        AZ_PREPARING: TF_RUNNING, \
        AZ_QUEUED: TF_RUNNING, \
        AZ_READY: TF_STOPPED, \
        AZ_SEEDING: TF_RUNNING, \
        AZ_STOPPED: TF_STOPPED, \
        AZ_STOPPING: TF_RUNNING, \
        AZ_WAITING: TF_STOPPED \
    }

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, tf_pathTransfers, flu_pathTransfers, file):
        self.state = Transfer.TF_STOPPED
        self.tf_pathTransfers = tf_pathTransfers
        self.flu_pathTransfers = flu_pathTransfers
        self.name = file
        self.fileTorrent = self.tf_pathTransfers + file
        self.fileMeta = self.flu_pathTransfers + file

        # owner
        self.owner = ''

        # file-vars
        self.fileStat = self.fileTorrent + ".stat"
        self.fileCommand = self.fileTorrent + ".cmd"
        self.fileLog = self.fileTorrent + ".log"
        self.filePid = self.fileTorrent + ".pid"

        # stat-object
        self.sf = None

        # initialize
        self.initialize()

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

        # out
        self.log("initializing transfer %s ..." % self.name)

        # meta-file
        self.log("loading metafile %s ..." % self.fileMeta)
        try:
            # read file to mem
            f = open(self.fileMeta, 'r')
            data = f.read()
            f.close()
            # process data
            if len(data) > 0:
                content = data.split("\n")
                if len(content) > 0:
                    # owner
                    self.owner = content[0]
                    self.log("owner: %s" % self.owner)
                else:
                    self.log("No owner found.")
                    return False
            else:
                self.log("No owner found.")
                return False
        except:
            self.log("Failed to read metafile %s " % self.fileMeta)
            return False

        # stat-file
        self.log("loading statfile %s ..." % self.fileStat)
        self.sf = StatFile(self.fileStat)

        # verbose
        self.log("transfer loaded.")

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ update                                                               """
    """ -------------------------------------------------------------------- """
    def update(self, download):

        # DEBUG
        self.log("* update: %s" % self.name)

        # set state
        self.state = Transfer.STATE_MAP[download.getState()]

        # only when running
        if self.state == Transfer.TF_RUNNING:

            # stat
            self.statRunning(download)

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self, download):
        self.log("starting transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statStartup(download)

        # write pid
        self.writePid()

        # start transfer
        try:
            if download.getState() == Transfer.AZ_READY:
                download.start()
            else:
                download.restart()
            # refresh
            download.refresh_object()
            # set state
            self.state = Transfer.STATE_MAP[download.getState()]
            # return
            return True
        except:
            self.log("exception when starting transfer :")
            print getPrefix(), sys.exc_info()
            return False

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self, download):
        self.log("stopping transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statShutdown(download)

        # stop transfer
        retVal = True
        try:
            download.stop()
            retVal = True
        except:
            self.log("exception when stopping transfer :")
            print getPrefix(), sys.exc_info()
            retVal = False

        # delete pid
        self.deletePid()

        # return
        return retVal

    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        return (self.state == Transfer.TF_RUNNING)

    """ -------------------------------------------------------------------- """
    """ processCommandStack                                                  """
    """ -------------------------------------------------------------------- """
    def processCommandStack(self, download):
        if os.path.isfile(self.fileCommand):
            # process file
            self.log("Processing command-file %s ..." % self.fileCommand)
            try:
                # read file to mem
                f = open(self.fileCommand, 'r')
                data = f.read()
                f.close()
                # delete file
                try:
                    os.remove(self.fileCommand)
                except:
                    self.log("Failed to delete command-file : %s" % self.fileCommand)
                # exec commands
                if len(data) > 0:
                    commands = data.split("\n")
                    if len(commands) > 0:
                        for command in commands:
                            if len(command) > 0:
                                # exec, early out when reading a quit-command
                                if self.execCommand(download, command):
                                    return True
                    else:
                        self.log("No commands found.")
                else:
                    self.log("No commands found.")
            except:
                self.log("Failed to read command-file : %s" % self.fileCommand)
        return False

    """ -------------------------------------------------------------------- """
    """ execCommand                                                          """
    """ -------------------------------------------------------------------- """
    def execCommand(self, download, command):

        # DEBUG
        self.log("Command: %s (%s) (%s)" % (command, self.name, str(Transfer.STATE_MAP[download.getState()])))

        # TODO

        return False

    """ -------------------------------------------------------------------- """
    """ statStartup                                                          """
    """ -------------------------------------------------------------------- """
    def statStartup(self, download):
        # set some values
        self.sf.running = Transfer.TF_RUNNING
        self.sf.percent_done = 0
        self.sf.time_left = "Starting..."
        self.sf.down_speed = "0.00 kB/s"
        self.sf.up_speed = "0.00 kB/s"
        self.sf.transferowner = self.owner
        self.sf.seeds = ""
        self.sf.peers = ""
        self.sf.sharing = ""
        self.sf.seedlimit = ""
        self.sf.uptotal = 0
        self.sf.downtotal = 0
        try:
            # get size
            try:
                size = str(download.getTorrent().getSize())
                self.sf.size = size
            except:
                print getPrefix(), sys.exc_info()
            # write
            return self.sf.write()
        except:
            print getPrefix(), sys.exc_info()
            return False

    """ -------------------------------------------------------------------- """
    """ statRunning                                                          """
    """ -------------------------------------------------------------------- """
    def statRunning(self, download):
        # set some values
        self.sf.running = Transfer.TF_RUNNING
        try:
            # stats
            try:
                stats = download.getStats()
                # completed
                try:
                    pctf = float(stats.getCompleted())
                    pctf /= 10
                    self.sf.percent_done = str(pctf)
                except:
                    print getPrefix(), sys.exc_info()
                # time_left
                try:
                    self.sf.time_left = str(stats.getETA())
                except:
                    print getPrefix(), sys.exc_info()
                # down_speed
                try:
                    self.sf.down_speed = "%.1f kB/s" % ((float(stats.getDownloadAverage())) / 1024)
                except:
                    print getPrefix(), sys.exc_info()
                # up_speed
                try:
                    self.sf.up_speed = "%.1f kB/s" % ((float(stats.getUploadAverage())) / 1024)
                except:
                    print getPrefix(), sys.exc_info()
                # uptotal
                try:
                    self.sf.uptotal = str(stats.getUploaded())
                except:
                    print getPrefix(), sys.exc_info()
                # downtotal
                try:
                    self.sf.downtotal = str(stats.getDownloaded())
                except:
                    print getPrefix(), sys.exc_info()
            except:
                print getPrefix(), sys.exc_info()
            # hosts
            try:
                ps = download.getPeerManager().getStats()
                scrape = download.getLastScrapeResult()
                # seeds
                try:
                    self.sf.seeds = "%d (%d)" % (ps.getConnectedSeeds(), scrape.getSeedCount())
                except:
                    print getPrefix(), sys.exc_info()
                # peers
                try:
                    self.sf.peers = "%d (%d)" % (ps.getConnectedLeechers(), scrape.getNonSeedCount())
                except:
                    print getPrefix(), sys.exc_info()
            except:
                print getPrefix(), sys.exc_info()
            # write
            return self.sf.write()
        except:
            print getPrefix(), sys.exc_info()
            return False

    """ -------------------------------------------------------------------- """
    """ statShutdown                                                         """
    """ -------------------------------------------------------------------- """
    def statShutdown(self, download, error = None):
        # set some values
        self.sf.running = Transfer.TF_STOPPED
        self.sf.down_speed = "0.00 kB/s"
        self.sf.up_speed = "0.00 kB/s"
        self.sf.transferowner = self.owner
        self.sf.seeds = ""
        self.sf.peers = ""
        self.sf.sharing = ""
        self.sf.seedlimit = ""
        try:
            # stats
            try:
                stats = download.getStats()
                # done
                if download.isComplete():
                    self.sf.percent_done = 100
                    self.sf.time_left = "Download Succeeded!"
                # not done
                else:
                    try:
                        pctf = float(stats.getCompleted())
                        pctf /= 10
                        pcts = "-" + str(pctf)
                        pctf = float(pcts)
                        pctf -= 100
                        self.sf.percent_done = str(pctf)
                    except:
                        print getPrefix(), sys.exc_info()
                    self.sf.time_left = "Transfer Stopped"
                # uptotal
                try:
                    self.sf.uptotal = str(stats.getUploaded())
                except:
                    print getPrefix(), sys.exc_info()
                # downtotal
                try:
                    self.sf.downtotal = str(stats.getDownloaded())
                except:
                    print getPrefix(), sys.exc_info()
            except:
                print getPrefix(), sys.exc_info()
            # size
            try:
                self.sf.size = str(download.getTorrent().getSize())
            except:
                print getPrefix(), sys.exc_info()
            # error
            if error is not None:
                self.sf.time_left = "Error: %s" % error
            # write
            return self.sf.write()
        except:
            print getPrefix(), sys.exc_info()
            return False

    """ -------------------------------------------------------------------- """
    """ writePid                                                             """
    """ -------------------------------------------------------------------- """
    def writePid(self):
        self.log("writing pid-file %s " % self.filePid)
        try:
            pidFile = open(self.filePid, 'w')
            pidFile.write("0\n")
            pidFile.flush()
            pidFile.close()
            return True
        except Exception, e:
            self.log("Failed to write pid-file %s" % self.filePid)
            return False

    """ -------------------------------------------------------------------- """
    """ deletePid                                                            """
    """ -------------------------------------------------------------------- """
    def deletePid(self):
        self.log("deleting pid-file %s " % self.filePid)
        try:
            os.remove(self.filePid)
            return True
        except Exception, e:
            self.log("Failed to delete pid-file %s" % self.filePid)
            return False

    """ -------------------------------------------------------------------- """
    """ log                                                                  """
    """ -------------------------------------------------------------------- """
    def log(self, message):
        printMessage(message)
        try:
            f = open(self.fileLog, "a+")
            f.write(getOutput(message))
            f.flush()
            f.close()
        except Exception, e:
            printError("Failed to write log-file %s\n" % self.fileLog)
