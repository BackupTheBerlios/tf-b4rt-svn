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
# fluazu
from fluazu.output import printMessage, printError
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
    state_map = { \
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
        printMessage("initializing transfer %s ..." % self.name)

        # meta-file
        printMessage("loading metafile %s ..." % self.fileMeta)
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
                    printMessage("owner: %s" % self.owner)
                else:
                    printMessage("No owner found.")
                    return False
            else:
                printMessage("No owner found.")
                return False
        except:
            printError("Failed to read metafile %s " % self.fileMeta)
            return False

        # stat-file
        printMessage("loading statfile %s ..." % self.fileStat)
        self.sf = StatFile(self.fileStat)

        # verbose
        printMessage("transfer loaded.")

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ update                                                               """
    """ -------------------------------------------------------------------- """
    def update(self, download):

        # set state
        self.state = Transfer.state_map[download.getState()]

        # DEBUG
        printMessage("* update: %s (%s)" % (self.name, str(self.state)))

        # stat
        return self.statRunning()

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self, download):
        printMessage("starting transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statStartup()

        # write pid
        self.writePid()

        # TODO

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self, download):
        printMessage("stopping transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statShutdown()

        # TODO

        # delete pid
        self.deletePid()

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ inject                                                               """
    """ -------------------------------------------------------------------- """
    def inject(self, dm):
        printMessage("injecting new transfer %s (%s) ..." % (str(self.name), str(self.owner)))


        # TODO
        return True

    """ -------------------------------------------------------------------- """
    """ delete                                                               """
    """ -------------------------------------------------------------------- """
    def delete(self, dm):
        printMessage("deleting transfer %s (%s) ..." % (str(self.name), str(self.owner)))


        # TODO
        return False

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
            printMessage("Processing command-file %s ..." % self.fileCommand)
            try:
                # read file to mem
                f = open(self.fileCommand, 'r')
                data = f.read()
                f.close()
                # delete file
                try:
                    os.remove(self.fileCommand)
                except:
                    printError("Failed to delete command-file : %s" % self.fileCommand)
                    pass
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
                        printMessage("No commands found.")
                else:
                    printMessage("No commands found.")
            except:
                printError("Failed to read command-file : %s" % self.fileCommand)
                pass
        return False

    """ -------------------------------------------------------------------- """
    """ execCommand                                                          """
    """ -------------------------------------------------------------------- """
    def execCommand(self, download, command):

        # DEBUG
        printMessage("Command: %s (%s) (%s)" % (command, self.name, str(Transfer.state_map[download.getState()])))

        # TODO

        return False

    """ -------------------------------------------------------------------- """
    """ statStartup                                                          """
    """ -------------------------------------------------------------------- """
    def statStartup(self):
        # set some values
        self.sf.running = Transfer.TF_RUNNING;
        self.sf.percent_done = 0;
        self.sf.time_left = "Starting...";
        self.sf.down_speed = "0.00 kB/s";
        self.sf.up_speed = "0.00 kB/s";
        self.sf.transferowner = 0;
        self.sf.seeds = "";
        self.sf.peers = "";
        self.sf.sharing = "";
        self.sf.seedlimit = "";
        self.sf.uptotal = 0;
        self.sf.downtotal = 0;
        # write
        return self.sf.write()

    """ -------------------------------------------------------------------- """
    """ statRunning                                                          """
    """ -------------------------------------------------------------------- """
    def statRunning(self):
        # set some values
        self.sf.running = Transfer.TF_RUNNING;
        self.sf.percent_done = 0;
        self.sf.time_left = "Running...";
        self.sf.down_speed = "0.00 kB/s";
        self.sf.up_speed = "0.00 kB/s";
        self.sf.transferowner = 0;
        self.sf.seeds = "";
        self.sf.peers = "";
        self.sf.sharing = "";
        self.sf.seedlimit = "";
        self.sf.uptotal = 0;
        self.sf.downtotal = 0;
        # write
        return self.sf.write()
        """
        // set some values
        $this->_sf->percent_done = $percent_done;
        $this->_sf->time_left = $time_left;
        $this->_sf->down_speed = $down_speed;
        $this->_sf->downtotal = $downtotal;
        // write
        return $this->_sf->write();
        """

    """ -------------------------------------------------------------------- """
    """ statShutdown                                                         """
    """ -------------------------------------------------------------------- """
    def statShutdown(self, error = None):
        # set some values
        self.sf.running = Transfer.TF_STOPPED;
        self.sf.percent_done = 0;
        self.sf.time_left = "Stopping...";
        self.sf.down_speed = "0.00 kB/s";
        self.sf.up_speed = "0.00 kB/s";
        self.sf.transferowner = 0;
        self.sf.seeds = "";
        self.sf.peers = "";
        self.sf.sharing = "";
        self.sf.seedlimit = "";
        self.sf.uptotal = 0;
        self.sf.downtotal = 0;
        # write
        return self.sf.write()
        """
        // set some values
        $this->_sf->running = 0;
        if ($this->_done) {
        $this->_sf->percent_done = 100;
        $this->_sf->time_left = "Download Succeeded!";
        } else {
        $this->_sf->percent_done = ($this->_size > 0)
        ? (((intval((100.0 * $this->_downtotal / $this->_size))) + 100) * (-1))
        : "-100";
        $this->_sf->time_left = "Transfer Stopped";
        }
        if ($error)
        $this->_sf->time_left = "Error";
        $this->_sf->down_speed = "";
        $this->_sf->up_speed = "";
        $this->_sf->transferowner = $this->_owner;
        $this->_sf->seeds = "";
        $this->_sf->peers = "";
        $this->_sf->sharing = "";
        $this->_sf->seedlimit = "";
        $this->_sf->uptotal = 0;
        $this->_sf->downtotal = $this->_downtotal;
        // write
        return $this->_sf->write();
        """

    """ -------------------------------------------------------------------- """
    """ writePid                                                             """
    """ -------------------------------------------------------------------- """
    def writePid(self):
        printMessage("writing pid-file %s " % self.filePid)
        try:
            pidFile = open(self.filePid, 'w')
            pidFile.write("0\n")
            pidFile.flush()
            pidFile.close()
            return True
        except Exception, e:
            printError("Failed to write pid-file %s" % self.filePid)
            return False

    """ -------------------------------------------------------------------- """
    """ deletePid                                                            """
    """ -------------------------------------------------------------------- """
    def deletePid(self):
        printMessage("deleting pid-file %s " % self.filePid)
        try:
            os.remove(self.filePid)
            return True
        except Exception, e:
            printError("Failed to delete pid-file %s" % self.filePid)
            return False

