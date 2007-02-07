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
from fluazu.output import printMessage, printError
from fluazu.Transfer import Transfer
# dopal
from dopal.main import make_connection
from dopal.errors import LinkError
################################################################################

""" ------------------------------------------------------------------------ """
""" FluAzuD                                                                  """
""" ------------------------------------------------------------------------ """
class FluAzuD(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):
        self.running = 1
        self.transfers = []
        self.downloads = {}
        self.pid = '0'
        # tf-settings
        self.tf_path = ''
        self.tf_pathTransfers = ''
        # flu-settings
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
        self.tf_path = path
        self.tf_pathTransfers = self.tf_path + '.transfers/'
        self.flu_path = self.tf_path + '.fluazu/'
        self.flu_fileCommand = self.flu_path + 'fluazu.cmd'
        self.flu_filePid = self.flu_path + 'fluazu.pid'
        self.flu_pathTransfers = self.flu_path + 'transfers/'
        self.azu_host = host
        self.azu_port = int(port)
        if secure == '1':
            self.azu_secure = True
        else:
            self.azu_secure = False
        self.azu_user = username
        self.azu_pass = password

        # print vars
        printMessage("flu-path: %s" % str(self.flu_path))
        printMessage("azu-host: %s" % str(self.azu_host))
        printMessage("azu-port: %s" % str(self.azu_port))
        printMessage("azu-secure: %s" % str(self.azu_secure))
        if len(self.azu_user) > 0:
            printMessage("azu-user: %s" % str(self.azu_user))
            printMessage("azu-pass: %s" % str(self.azu_pass))

        # set connection details
        self.connection_details['host'] = self.azu_host
        self.connection_details['port'] = self.azu_port
        self.connection_details['secure'] = self.azu_secure
        if len(self.azu_user) > 0:
            self.connection_details['user'] = self.azu_user
            self.connection_details['password'] = self.azu_pass

        # initialize
        if not self.initialize():
            printError("there were problems initializing fluazu, shutting down...")
            self.shutdown()
            return 1

        # main
        return self.main()

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

        # flu

        # write pid-file
        self.pid = (str(os.getpid())).strip()
        printMessage("writing pid-file %s (%s)" % (self.flu_filePid, self.pid))
        try:
            pidFile = open(self.flu_filePid, 'w')
            pidFile.write(self.pid + "\n")
            pidFile.flush()
            pidFile.close()
        except Exception, e:
            printError("Failed to write pid-file %s (%s)" % (self.flu_filePid, self.pid))

        # tf

        # delete command-file if exists
        if os.path.isfile(self.flu_fileCommand):
            try:
                transferLog("removing command-file %s ..." % self.flu_fileCommand)
                os.remove(self.flu_fileCommand)
            except:
                printError("Failed to delete commandfile %s" % self.flu_fileCommand)
                pass

        # load transfers
        self.loadTransfers()

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
            printMessage("Connected to %s" % self.azu_host)
        else:
            printError("Error getting plugin interface object - could not connect to Azureus-Server %s:%s" % (str(self.azu_host), str(self.azu_port)))
            printError(connection_error.to_error_string())
            return False

        # azureus version
        printMessage("Azureus-Version: " + str(self.connection.get_azureus_version()))

        # download-manager
        self.dm = self.interface.getDownloadManager()
        if self.dm is None:
            printError("Error getting plugin Download-Manager object")
            return False

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):

        # shutdown
        printMessage("fluazu shutting down...")

        # delete pid-file
        printMessage("deleting pid-file %s ..." % self.flu_filePid)
        try:
            os.remove(self.flu_filePid)
        except Exception, e:
            printError("Failed to delete pid-file %s " % self.flu_filePid)

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running > 0:

            # check if connection still valid
            if not self.azu_checkConnection():
                return 1

            # update downloads
            self.updateDownloads()

            # update and sync transfers
            for transfer in self.transfers:
                if transfer.name in self.downloads:
                    # DEBUG
                    printMessage("* update %s (%s)" % (str(transfer.name), str(transfer.owner)))
                    # update
                    transfer.update(self.downloads[transfer.name])
                else:
                    # inject
                    printMessage("inject new transfer %s (%s) ..." % (str(transfer.name), str(transfer.owner)))
                    transfer.inject(self.dm)
                    # update downloads
                    self.updateDownloads()

            # inner loop
            for i in range(5):

                # process command stack
                if self.processCommandStack():
                    # shutdown
                    self.running = 0
                    break;

                # process transfers
                for transfer in self.transfers:
                    # DEBUG
                    printMessage("* process %s (%s)" % (str(transfer.name), str(transfer.owner)))
                    transfer.processCommandStack(self.downloads[transfer.name])

                # sleep
                time.sleep(1)

        # shutdown
        self.shutdown()

        # return
        return 0

    """ -------------------------------------------------------------------- """
    """ updateDownloads                                                      """
    """ -------------------------------------------------------------------- """
    def updateDownloads(self):
        azu_dls = self.dm.getDownloads()
        for download in azu_dls:
            tfile = str(download.getTorrentFileName())
            comp = tfile.split('/')
            tfile = comp.pop()
            self.downloads[tfile] = download

    """ -------------------------------------------------------------------- """
    """ processCommandStack                                                  """
    """ -------------------------------------------------------------------- """
    def processCommandStack(self):
        if os.path.isfile(self.flu_fileCommand):
            # process file
            printMessage("Processing command-file %s ..." % self.flu_fileCommand)
            try:
                # read file to mem
                f = open(self.flu_fileCommand, 'r')
                data = f.read()
                f.close
                # delete file
                try:
                    os.remove(self.flu_fileCommand)
                except:
                    printError("Failed to delete command-file : %s" % self.flu_fileCommand)
                    pass
                # exec commands
                if len(data) > 0:
                    commands = data.split("\n")
                    if len(commands) > 0:
                        for command in commands:
                            if len(command) > 0:
                                # exec, early out when reading a quit-command
                                if self.execCommand(command):
                                    return True
                    else:
                        printMessage("No commands found.")
                else:
                    printMessage("No commands found.")
            except:
                printError("Failed to read command-file : %s" % self.flu_fileCommand)
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
            printMessage("command: stop-request, setting shutdown-flag...")
            return True

        # t
        elif opCode == 't':
            printMessage("command: transfers-reload-request, reloading...")
            self.loadTransfers()
            return False

        # default
        else:
            printError("op-code unknown: " + opCode)
            return False

    """ -------------------------------------------------------------------- """
    """ loadTransfers                                                        """
    """ -------------------------------------------------------------------- """
    def loadTransfers(self):
        printMessage("loading transfers...")
        self.transfers = []
        try:
            for fileName in os.listdir(self.flu_pathTransfers):
                self.transfers.append(Transfer(self.tf_pathTransfers, self.flu_pathTransfers, fileName))
            return True
        except:
            return False

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
                printMessage("established connection to " + self.azu_host)
            except:
                printError("Error establishing connection to " + self.azu_host)
                return False

            # interface
            try:
                self.interface = self.connection.get_plugin_interface()
            except LinkError, error:
                self.interface = None
            if self.interface is None:
                printError("Error getting plugin interface object")
                return False

            # download-manager
            self.dm = None
            self.dm = self.interface.getDownloadManager()
            if self.dm is None:
                printError("Error getting plugin Download-Manager object")
                return False
            else:
                return True

