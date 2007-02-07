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
    ST_DOWNLOADING = 4
    ST_ERROR = 8
    ST_PREPARING = 2
    ST_QUEUED = 9
    ST_READY = 3
    ST_SEEDING = 5
    ST_STOPPED = 7
    ST_STOPPING = 6
    ST_WAITING = 1

    """ azu -> flu map """
    azu_state_map = { \
        ST_DOWNLOADING: TF_RUNNING, \
        ST_ERROR: TF_STOPPED, \
        ST_PREPARING: TF_RUNNING, \
        ST_QUEUED: TF_RUNNING, \
        ST_READY: TF_STOPPED, \
        ST_SEEDING: TF_RUNNING, \
        ST_STOPPED: TF_STOPPED, \
        ST_STOPPING: TF_RUNNING, \
        ST_WAITING: TF_STOPPED \
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

        # stat-file
        self.statFile = None

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
        self.statFile = StatFile(self.fileStat)

        # verbose
        printMessage("transfer loaded.")

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ processCommandStack                                                  """
    """ -------------------------------------------------------------------- """
    def processCommandStack(self, download):
        # DEBUG
        printMessage("* processCommandStack: %s (%s)" % (self.name, self.fileCommand))
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
        printMessage("Command: %s (%s) (%s)" % (command, self.name, str(Transfer.azu_state_map[download.getState()])))

        # TODO

        return False

    """ -------------------------------------------------------------------- """
    """ update                                                               """
    """ -------------------------------------------------------------------- """
    def update(self, download):

        # set state
        self.state = Transfer.azu_state_map[download.getState()]

        # DEBUG
        printMessage("* update: %s (%s)" % (self.name, str(self.state)))

        # stat
        self.statRunning()

        # return
        return False

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self, download):
        printMessage("starting transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statStartup()

        # return
        return False

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self, download):
        printMessage("stopping transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # stat
        self.statShutdown()

        # return
        return False

    """ -------------------------------------------------------------------- """
    """ inject                                                               """
    """ -------------------------------------------------------------------- """
    def inject(self, dm):
        printMessage("injecting new transfer %s (%s) ..." % (str(self.name), str(self.owner)))

        # TODO

        return False

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
    """ statStartup                                                          """
    """ -------------------------------------------------------------------- """
    def statStartup(self):
        # set some values
        self.statFile.running = Transfer.TF_RUNNING;
        self.statFile.percent_done = 0;
        self.statFile.time_left = "Starting...";
        self.statFile.down_speed = "0.00 kB/s";
        self.statFile.up_speed = "0.00 kB/s";
        self.statFile.transferowner = 0;
        self.statFile.seeds = "";
        self.statFile.peers = "";
        self.statFile.sharing = "";
        self.statFile.seedlimit = "";
        self.statFile.uptotal = 0;
        self.statFile.downtotal = 0;
        # write
        return self.statFile.write()

    """ -------------------------------------------------------------------- """
    """ statRunning                                                          """
    """ -------------------------------------------------------------------- """
    def statRunning(self):
        # set some values
        self.statFile.running = Transfer.TF_RUNNING;
        self.statFile.percent_done = 0;
        self.statFile.time_left = "Running...";
        self.statFile.down_speed = "0.00 kB/s";
        self.statFile.up_speed = "0.00 kB/s";
        self.statFile.transferowner = 0;
        self.statFile.seeds = "";
        self.statFile.peers = "";
        self.statFile.sharing = "";
        self.statFile.seedlimit = "";
        self.statFile.uptotal = 0;
        self.statFile.downtotal = 0;
        # write
        return self.statFile.write()
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
        self.statFile.running = Transfer.TF_STOPPED;
        self.statFile.percent_done = 0;
        self.statFile.time_left = "Stopping...";
        self.statFile.down_speed = "0.00 kB/s";
        self.statFile.up_speed = "0.00 kB/s";
        self.statFile.transferowner = 0;
        self.statFile.seeds = "";
        self.statFile.peers = "";
        self.statFile.sharing = "";
        self.statFile.seedlimit = "";
        self.statFile.uptotal = 0;
        self.statFile.downtotal = 0;
        # write
        return self.statFile.write()
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

