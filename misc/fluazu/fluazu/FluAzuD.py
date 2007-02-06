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
# fluazu
from fluazu.StatFile import StatFile
from fluazu.Transfer import Transfer
# dopal
from dopal.main import make_connection
from dopal.errors import LinkError
################################################################################

class FluAzuD(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):
        self.state = 1
        self.running = 1
        self.errors = []
        self.transfers = []
        # flux-settings
        self.flu_path = ''
        self.flu_pathTransfers = ''
        self.flu_fileCommand = ''
        self.flu_filePid = ''
        # azu-settings
        self.azu_host = '127.0.0.1'
        self.azu_port = 6884
        self.azu_secure = False
        self.azu_user = ''
        self.azu_pass = ''
        # dopal
        self.connection_details = {}
        self.connection = None
        self.interface = None
        self.dm = None

    """ -------------------------------------------------------------------- """
    """ run                                                                  """
    """ -------------------------------------------------------------------- """
    def run(self, path, host, port, secure, username, password):

        # set vars
        self.flu_path = path
        self.flu_pathTransfers = path + '.transfers/'
        self.flu_fileCommand = path + '.fluazu/fluazu.cmd'
        self.flu_filePid = path + '.fluazu/fluazu.pid'
        self.azu_host = host
        self.azu_port = int(port)
        if secure == '1':
            self.azu_secure = True
        else:
            self.azu_secure = False
        self.azu_user = username
        self.azu_pass = password

        # print vars
        self.printMessage("flu-path: %s" % str(self.flu_path))
        self.printMessage("azu-host: %s" % str(self.azu_host))
        self.printMessage("azu-port: %s" % str(self.azu_host))
        self.printMessage("azu-secure: %s" % str(self.azu_secure))
        if len(self.azu_user) > 0:
            self.printMessage("azu-user: %s" % str(self.azu_user))
            self.printMessage("azu-pass: %s" % str(self.azu_pass))

        # set connection details
        self.connection_details['host'] = self.azu_host
        self.connection_details['port'] = self.azu_port
        self.connection_details['secure'] = self.azu_secure
        if len(self.azu_user) > 0:
            self.connection_details['user'] = self.azu_user
            self.connection_details['password'] = self.azu_pass

        # initialize
        self.initialize()

        # check all dopal-objects
        if self.connection is None or self.interface is None or self.dm is None:
            self.printError("there were problems, not starting up daemon-mainloop.")
            return 0

        # main
        return self.main()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):
        # shutdown
        self.printMessage("fluazu shutting down...")


        # return
        return 0

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running > 0:

            # check if connection still valid
            if not self.azu_checkConnection():
                return 0

            # process downloads
            downloads = self.dm.getDownloads()
            for download in downloads:
                # download
                self.printMessage("* %s" % str(download))
                self.printMessage("  %s" % str(download.getName()))
                self.printMessage("  %s" % str(download.getState()))
                tfile = str(download.getTorrentFileName())
                self.printMessage("  %s" % tfile)
                comp = tfile.split('/')
                tfile = comp.pop()
                self.printMessage("  %s" % tfile)
                self.printMessage("  %s" % str(download.getSavePath()))
                self.printMessage("  %s" % str(download.getLastScrapeResult()))
                # torrent
                torrent = download.getTorrent()
                self.printMessage("  %s" % str(torrent.getName()))
                self.printMessage("  getState: %s" % str(download.getState()))
                self.printMessage("  getSize: %s" % str(torrent.getSize()))
                # stats
                stats = download.getStats()
                self.printMessage("  getUploaded: %s" % str(stats.getUploaded()))
                self.printMessage("  getDownloaded: %s" % str(stats.getDownloaded()))
                self.printMessage("  getUploadAverage: %s" % str(stats.getUploadAverage()))
                self.printMessage("  getDownloadAverage: %s" % str(stats.getDownloadAverage()))
                self.printMessage("  getETA: %s" % str(stats.getETA()))

            # inner loop
            for i in range(5):

                # debug
                self.printMessage(str(i))

                # process command stack
                if self.processCommandStack():
                    # shutdown
                    self.running = 0
                    break;

                # sleep
                time.sleep(1)

        # shutdown
        return self.shutdown()

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

        # tf


        # azu

        # connect
        self.connection = make_connection(**self.connection_details)
        self.connection.is_persistent_connection = True
        try:
            self.interface = self.connection.get_plugin_interface()
        except LinkError, error:
            self.interface = None
            connection_error = error
        else:
            connection_error = None
        if connection_error is None:
            self.printMessage("Connected to %s" % self.azu_host)
        else:
            self.printError("Error getting plugin interface object - could not connect to Azureus, error:\n %s" % connection_error.to_error_string())

        # azureus version
        self.printMessage("Azureus-Version: " + str(self.connection.get_azureus_version()))

        # download-manager
        self.dm = self.interface.getDownloadManager()
        if self.dm is None:
            self.printError("Error getting plugin Download-Manager object")
            return False

    """ -------------------------------------------------------------------- """
    """ processCommandStack                                                  """
    """ -------------------------------------------------------------------- """
    def processCommandStack(self):
        if os.path.isfile(self.flu_fileCommand):
            # process file
            self.printMessage("Processing command-file " + self.flu_fileCommand + "...")
            try:
                # read file to mem
                f = open(self.flu_fileCommand, 'r')
                commands = f.readlines()
                f.close
                # remove file
                try:
                    os.remove(self.flu_fileCommand)
                except:
                    self.printError("Failed to remove command-file : " + self.flu_fileCommand)
                    pass
                # exec commands
                if len(commands) > 0:
                    for command in commands:
                        command = command.replace("\n", "")
                        if len(command) > 0:
                            # exec, early out when reading a quit-command
                            if self.execCommand(command):
                                return True
                else:
                    self.printMessage("No commands found.")
            except:
                self.printError("Failed to read command-file : " + self.flu_fileCommand)
                pass
        return False

    """ -------------------------------------------------------------------- """
    """ execCommand                                                          """
    """ -------------------------------------------------------------------- """
    def execCommand(self, command):

        # op-code
        opCode = command[0]

        # q
        if opCode == 'q':
            self.printMessage("command: stop-request, setting shutdown-flag...")
            return True

        # t
        elif opCode == 't':
            self.printMessage("command: transfers-reload-request, reloading...")
            self.reloadTransfers()
            return False

        # default
        else:
            self.printError("op-code unknown: " + opCode)
            return False

    """ -------------------------------------------------------------------- """
    """ reloadTransfers                                                      """
    """ -------------------------------------------------------------------- """
    def reloadTransfers(self):
        return True


    """ -------------------------------------------------------------------- """
    """ transfer_processCommandStack                                         """
    """ -------------------------------------------------------------------- """
    def transfer_processCommandStack(self, transfer):
        return False
        """
        if os.path.isfile(transferCommandFile):
            # process file
            transferLog("Processing command-file " + transferCommandFile + "...\n", True)
            try:
                # read file to mem
                f = open(transferCommandFile, 'r')
                commands = f.readlines()
                f.close
                # remove file
                try:
                    os.remove(transferCommandFile)
                except:
                    transferLog("Failed to remove command-file : " + transferCommandFile + "\n", True)
                    pass
                # exec commands
                if len(commands) > 0:
                    for command in commands:
                        command = command.replace("\n", "")
                        if len(command) > 0:
                            # exec, early out when reading a quit-command
                            if self.execCommand(command):
                                return True
                else:
                    transferLog("No commands found.\n", True)
            except:
                transferLog("Failed to read command-file : " + transferCommandFile + "\n", True)
                pass
        return False
        """

    """ -------------------------------------------------------------------- """
    """ transfer_execCommand                                                 """
    """ -------------------------------------------------------------------- """
    def transfer_execCommand(self, transfer, command):
        return False
        """
        # op-code
        opCode = command[0]

        # q
        if opCode == 'q':
            transferLog("command: stop-request, setting shutdown-flag...\n", True)
            return True

        # u
        elif opCode == 'u':
            if len(command) < 2:
                transferLog("invalid rate.\n", True)
                return False
            rateNew = command[1:]
            transferLog("command: setting upload-rate to " + rateNew + "...\n", True)
            self.multitorrent.set_option('max_upload_rate', int(rateNew), None, False)
            return False

        # d
        elif opCode == 'd':
            if len(command) < 2:
                transferLog("invalid rate.\n", True)
                return False
            rateNew = command[1:]
            transferLog("command: setting download-rate to " + rateNew + "...\n", True)
            self.multitorrent.set_option('max_download_rate', int(rateNew), None, False)
            return False

        # r
        elif opCode == 'r':
            if len(command) < 2:
                transferLog("invalid runtime-code.\n", True)
                return False
            runtimeNew = command[1]
            rt = ''
            if runtimeNew == '0':
                rt = 'False'
            elif runtimeNew == '1':
                rt = 'True'
            else:
                transferLog("runtime-code unknown: " + runtimeNew + "\n", True)
                return False
            transferLog("command: setting die-when-done to " + rt + "...\n", True)
            self.d.dieWhenDone = rt
            return False

        # s
        elif opCode == 's':
            if len(command) < 2:
                transferLog("invalid sharekill.\n", True)
                return False
            sharekillNew = command[1:]
            transferLog("command: setting sharekill to " + sharekillNew + "...\n", True)
            self.d.seedLimit = sharekillNew
            return False

        # default
        else:
            transferLog("op-code unknown: " + opCode + "\n", True)
            return False
        """


    """ -------------------------------------------------------------------- """
    """ azu_checkConnection                                                  """
    """ -------------------------------------------------------------------- """
    def azu_checkConnection(self):

        # con valid
        if self.connection.is_connection_valid():
            return True

        # con not valid
        else:

            # establish con
            try:
                self.connection.establish_connection(True)
                self.printMessage("established connection to " + self.azu_host)
            except:
                self.printError("Error establishing connection to " + self.azu_host)
                return False

            # interface
            try:
                self.interface = self.connection.get_plugin_interface()
            except LinkError, error:
                self.interface = None
            if self.interface is None:
                self.printError("Error getting plugin interface object")
                return False

            # download-manager
            self.dm = None
            self.dm = self.interface.getDownloadManager()
            if self.dm is None:
                self.printError("Error getting plugin Download-Manager object")
                return False
            else:
                return True


    """ -------------------------------------------------------------------- """
    """ printMessage                                                         """
    """ -------------------------------------------------------------------- """
    def printMessage(self, message):
        sys.stdout.write(time.strftime('[%Y/%m/%d - %H:%M:%S]') + " " + message + "\n")

    """ -------------------------------------------------------------------- """
    """ printError                                                           """
    """ -------------------------------------------------------------------- """
    def printError(self, message):
        sys.stderr.write(time.strftime('[%Y/%m/%d - %H:%M:%S]') + " " + message + "\n")
