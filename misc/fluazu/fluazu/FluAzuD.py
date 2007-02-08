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
from fluazu.output import printMessage, printError, getPrefix
from fluazu.Transfer import Transfer
# dopal
from dopal.main import make_connection
from dopal.errors import LinkError
################################################################################

""" ------------------------------------------------------------------------ """
""" FluAzuD                                                                  """
""" ------------------------------------------------------------------------ """
class FluAzuD(object):

    """ class-fields """
    MAX_RECONNECT_TRIES = 5

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
        self.flu_pathTransfersRun = ''
        self.flu_pathTransfersDel = ''
        self.flu_fileCommand = ''
        self.flu_filePid = ''
        # azu-settings
        self.azu_host = '127.0.0.1'
        self.azu_port = 6884
        self.azu_secure = False
        self.azu_user = ''
        self.azu_pass = ''
        self.azu_version_str = ''
        # dopal
        self.connection = None
        self.interface = None
        self.dm = None

    """ -------------------------------------------------------------------- """
    """ run                                                                  """
    """ -------------------------------------------------------------------- """
    def run(self, path, host, port, secure, username, password):
        printMessage("fluazu starting up:")

        # set vars
        self.tf_path = path
        self.tf_pathTransfers = self.tf_path + '.transfers/'
        self.flu_path = self.tf_path + '.fluazu/'
        self.flu_fileCommand = self.flu_path + 'fluazu.cmd'
        self.flu_filePid = self.flu_path + 'fluazu.pid'
        self.flu_pathTransfers = self.flu_path + 'cur/'
        self.flu_pathTransfersRun = self.flu_path + 'run/'
        self.flu_pathTransfersDel = self.flu_path + 'del/'
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

        # check dirs
        if not self.checkDirs():
            printError("Error checking dirs. path: %s" % self.tf_path)
            return False

        # write pid-file
        self.pid = (str(os.getpid())).strip()
        printMessage("writing pid-file %s (%s)" % (self.flu_filePid, self.pid))
        try:
            pidFile = open(self.flu_filePid, 'w')
            pidFile.write(self.pid + "\n")
            pidFile.flush()
            pidFile.close()
        except:
            printError("Failed to write pid-file %s (%s)" % (self.flu_filePid, self.pid))
            return False

        # delete command-file if exists
        if os.path.isfile(self.flu_fileCommand):
            try:
                printMessage("removing command-file %s ..." % self.flu_fileCommand)
                os.remove(self.flu_fileCommand)
            except:
                printError("Failed to delete commandfile %s" % self.flu_fileCommand)
                return False

        # load transfers
        self.loadTransfers()

        # azu
        printMessage("connecting to Azureus-Server (%s:%d)..." % (self.azu_host, self.azu_port))

        # set connection details
        connection_details = {}
        connection_details['host'] = self.azu_host
        connection_details['port'] = self.azu_port
        connection_details['secure'] = self.azu_secure
        if len(self.azu_user) > 0:
            connection_details['user'] = self.azu_user
            connection_details['password'] = self.azu_pass

        # connect
        self.connection = make_connection(**connection_details)
        self.connection.is_persistent_connection = True
        try:
            self.interface = self.connection.get_plugin_interface()
        except LinkError, error:
            self.interface = None
            connection_error = error
        else:
            connection_error = None
        if connection_error is not None:
            printError("could not connect to Azureus-Server")
            return False

        # azureus version
        self.azu_version_str = str(self.connection.get_azureus_version())
        self.azu_version_str = self.azu_version_str.replace(", ", ".")
        self.azu_version_str = self.azu_version_str.replace("(", "")
        self.azu_version_str = self.azu_version_str.replace(")", "")
        printMessage("connected. Azureus-Version: %s" % self.azu_version_str)

        # download-manager
        self.dm = self.interface.getDownloadManager()
        if self.dm is None:
            printError("Error getting Download-Manager object")
            return False

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):
        printMessage("fluazu shutting down...")

        # delete pid-file
        printMessage("deleting pid-file %s ..." % self.flu_filePid)
        try:
            os.remove(self.flu_filePid)
        except:
            printError("Failed to delete pid-file %s " % self.flu_filePid)

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running > 0:

            # check if connection still valid, shutdown if it is not
            if not self.checkAzuConnection():
                # shutdown
                self.shutdown()
                # return
                return 1

            # update downloads
            self.updateDownloads()

            # update and sync transfers
            for transfer in self.transfers:
                if transfer.name in self.downloads:
                    # update
                    transfer.update(self.downloads[transfer.name])
                else:
                    # add
                    self.addTransfer(transfer.name)
                    # update downloads
                    self.updateDownloads()

            # inner loop
            for i in range(5):

                # process daemon command stack
                if self.processCommandStack():
                    # shutdown
                    self.running = 0
                    break;

                # process transfers command stacks
                for transfer in self.transfers:
                    if transfer.isRunning():
                        if transfer.processCommandStack(self.downloads[transfer.name]):
                            # stop it
                            transfer.stop(self.downloads[transfer.name])
                            # update downloads
                            self.updateDownloads()

                # sleep
                time.sleep(1)

        # shutdown
        self.shutdown()

        # return
        return 0

    """ -------------------------------------------------------------------- """
    """ reload                                                               """
    """ -------------------------------------------------------------------- """
    def reload(self):
        printMessage("reloading...")

        # delete-requests
        self.processDeleteRequests()

        # run-requests
        self.processRunRequests()

        # transfers
        self.loadTransfers()

    """ -------------------------------------------------------------------- """
    """ processDeleteRequests                                                """
    """ -------------------------------------------------------------------- """
    def processDeleteRequests(self):
        printMessage("processing delete-requests...")
        # read requests
        requests = []
        try:
            for fileName in os.listdir(self.flu_pathTransfersDel):
                # add
                requests.append(fileName)
                # del file
                delFile = self.flu_pathTransfersDel + fileName
                try:
                    os.remove(delFile)
                except:
                    printError("Failed to delete file : %s" % delFile)
        except:
            return False
        # process requests
        if len(requests) > 0:
            for fileName in requests:
                printMessage("deleting %s ..." % fileName)

                # update downloads
                self.updateDownloads()

                # remove if needed
                if fileName in self.downloads:
                    # remove transfer
                    self.removeTransfer(fileName)

                # del file
                delFile = self.flu_pathTransfers + fileName
                try:
                    os.remove(delFile)
                except:
                    printError("Failed to delete file : %s" % delFile)
        # return
        return True

    """ -------------------------------------------------------------------- """
    """ processRunRequests                                                   """
    """ -------------------------------------------------------------------- """
    def processRunRequests(self):
        printMessage("processing run-requests...")
        # read requests
        requests = []
        try:
            for fileName in os.listdir(self.flu_pathTransfersRun):
                inputFile = self.flu_pathTransfersRun + fileName
                outputFile = self.flu_pathTransfers + fileName
                # move file + add to requests
                try:
                    # read file to mem
                    f = open(inputFile, 'r')
                    data = f.read()
                    f.close()
                    # delete
                    os.remove(inputFile)
                    # write file
                    f = open(outputFile, 'w')
                    f.write(data)
                    f.flush()
                    f.close()
                    # add
                    requests.append(fileName)
                except:
                    printError("Failed to move file : %s" % inputFile)
        except:
            return False
        # process requests
        if len(requests) > 0:
            self.updateDownloads()
            for fileName in requests:
                transfer = Transfer(self.tf_pathTransfers, self.flu_pathTransfers, fileName)
                # add if needed
                if transfer.name not in self.downloads:
                    # add
                    self.addTransfer(transfer.name)
                    # update downloads
                    self.updateDownloads()
                # start transfer
                transfer.start(self.downloads[transfer.name])
        # return
        return True

    """ -------------------------------------------------------------------- """
    """ addTransfer                                                          """
    """ -------------------------------------------------------------------- """
    def addTransfer(self, tname):
        printMessage("adding new transfer %s ..." % tname)
        # call azu-method
        try:
            self.dm.addDownload(self.tf_pathTransfers + tname)
            return True
        except:
            printMessage("exception when adding transfer:")
            print getPrefix(), sys.exc_info()
            return False

    """ -------------------------------------------------------------------- """
    """ removeTransfer                                                       """
    """ -------------------------------------------------------------------- """
    def removeTransfer(self, tname):
        printMessage("removing transfer %s ..." % tname)
        # call azu-method
        try:
            self.downloads[tname].remove()
            return True
        except:
            printMessage("exception when removing transfer:")
            print getPrefix(), sys.exc_info()
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
    """ updateDownloads                                                      """
    """ -------------------------------------------------------------------- """
    def updateDownloads(self):
        azu_dls = self.dm.getDownloads()
        for download in azu_dls:
            tfile = (os.path.split(str(download.getTorrentFileName())))[1]
            self.downloads[tfile] = download

    """ -------------------------------------------------------------------- """
    """ processCommandStack                                                  """
    """ -------------------------------------------------------------------- """
    def processCommandStack(self):
        if os.path.isfile(self.flu_fileCommand):
            # process file
            printMessage("Processing command-file %s ..." % self.flu_fileCommand)
            try:
                try:
                    # read file to mem
                    f = open(self.flu_fileCommand, 'r')
                    data = f.read()
                    f.close()
                except:
                    printError("Failed to read command-file : %s" % self.flu_fileCommand)
                    raise
                # delete file
                try:
                    os.remove(self.flu_fileCommand)
                except:
                    printError("Failed to delete command-file : %s" % self.flu_fileCommand)
                try:
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
                    printError("Failed to exec commands.")
                    raise
            except:
                printError("Failed to process command-stack : %s" % self.flu_fileCommand)
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

        # r
        elif opCode == 'r':
            printMessage("command: reload-request, reloading...")
            self.reload()
            return False

        # default
        else:
            printError("op-code unknown: " + opCode)
            return False

    """ -------------------------------------------------------------------- """
    """ checkDirs                                                            """
    """ -------------------------------------------------------------------- """
    def checkDirs(self):

        # tf-paths
        if not os.path.isdir(self.tf_path):
            printError("Invalid path-dir: %s" % self.tf_path)
            return False
        if not os.path.isdir(self.tf_pathTransfers):
            printError("Invalid tf-transfers-dir: %s" % self.tf_pathTransfers)
            return False

        # flu-paths
        if not os.path.isdir(self.flu_path):
            try:
                printMessage("flu-main-path %s does not exist, trying to create ..." % self.flu_path)
                os.mkdir(self.flu_path, 0700)
                printMessage("done.")
            except:
                printError("Failed to create flu-main-path %s" % self.flu_path)
                return False
        if not os.path.isdir(self.flu_pathTransfers):
            try:
                printMessage("flu-transfers-path %s does not exist, trying to create ..." % self.flu_pathTransfers)
                os.mkdir(self.flu_pathTransfers, 0700)
                printMessage("done.")
            except:
                printError("Failed to create flu-main-path %s" % self.flu_pathTransfers)
                return False
        if not os.path.isdir(self.flu_pathTransfersRun):
            try:
                printMessage("flu-transfers-run-path %s does not exist, trying to create ..." % self.flu_pathTransfersRun)
                os.mkdir(self.flu_pathTransfersRun, 0700)
                printMessage("done.")
            except:
                printError("Failed to create flu-main-path %s" % self.flu_pathTransfersRun)
                return False
        if not os.path.isdir(self.flu_pathTransfersDel):
            try:
                printMessage("flu-transfers-del-path %s does not exist, trying to create ..." % self.flu_pathTransfersDel)
                os.mkdir(self.flu_pathTransfersDel, 0700)
                printMessage("done.")
            except:
                printError("Failed to create flu-main-path %s" % self.flu_pathTransfersDel)
                return False

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ checkAzuConnection                                                   """
    """ -------------------------------------------------------------------- """
    def checkAzuConnection(self):

        # con valid
        try:
            if self.connection.is_connection_valid():
                return True
            else:
                raise

        # con not valid
        except:

            # out
            printMessage("connection to Azureus-server lost, reconnecting to %s:%d ..." % (self.azu_host, self.azu_port))

            # try to reconnect
            for i in range(FluAzuD.MAX_RECONNECT_TRIES):

                # sleep
                time.sleep(i << 2)

                # out
                printMessage("reconnect-try %d ..." % (i + 1))

                # establish con
                try:
                    self.connection.establish_connection(True)
                    printMessage("established connection to Azureus-server")
                except:
                    printError("Error establishing connection to Azureus-server")
                    continue

                # interface
                try:
                    self.interface = self.connection.get_plugin_interface()
                except LinkError, error:
                    self.interface = None
                if self.interface is None:
                    printError("Error getting interface object")
                    continue

                # download-manager
                self.dm = None
                self.dm = self.interface.getDownloadManager()
                if self.dm is None:
                    printError("Error getting Download-Manager object")
                    continue
                else:
                    return True

            # seems like azu is down. give up
            printError("no connection after %d tries, i give up, azu is gone" % FluAzuD.MAX_RECONNECT_TRIES)
            return False
