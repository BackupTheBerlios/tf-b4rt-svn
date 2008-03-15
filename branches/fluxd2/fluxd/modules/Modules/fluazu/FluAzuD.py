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
# fluazu-imports
from fluxd.modules.Modules.fluazu.Transfer import Transfer
# dopal-imports
sys.path.append("%s/fluxd/modules/Modules/fluazu" % sys.path[0])
from dopal.main import make_connection
from dopal.errors import LinkError
import dopal.aztypes
################################################################################

""" ------------------------------------------------------------------------ """
""" FluAzuD                                                                  """
""" ------------------------------------------------------------------------ """
class FluAzuD(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, logger):
    
        # logger
        self.logger = logger
    
        # main
        self.running = 1
        self.transfers = []
        self.downloads = {}

        # fluxd-settings
        self.fluxd_path = ''

        # tf-settings
        self.tf_path = ''
        self.tf_pathTransfers = ''

        # flu-settings
        self.flu_path = ''
        self.flu_pathTransfers = ''
        self.flu_pathTransfersRun = ''
        self.flu_pathTransfersDel = ''
        self.flu_fileCommand = ''
        self.flu_fileStat = ''
        self.maxReconnectTries = 10

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
    def run(self, path_tf, path_fluxd, host, port, secure, username, password, maxReconTries):
    
        # log
        self.logger.info("fluazu starting up:")

        ## reset vars
        
        # main
        self.running = 1
        self.transfers = []
        self.downloads = {}

        # fluxd-settings
        self.fluxd_path = ''

        # tf-settings
        self.tf_path = ''
        self.tf_pathTransfers = ''

        # flu-settings
        self.flu_path = ''
        self.flu_pathTransfers = ''
        self.flu_pathTransfersRun = ''
        self.flu_pathTransfersDel = ''
        self.flu_fileCommand = ''
        self.flu_fileStat = ''
        self.maxReconnectTries = 10

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

        ## set vars
        self.tf_path = path_tf
        self.tf_pathTransfers = self.tf_path + '.transfers/'
        self.fluxd_path = path_fluxd
        self.flu_path = self.fluxd_path + 'fluazu/'
        self.flu_fileCommand = self.flu_path + 'fluazu.cmd'
        self.flu_fileStat = self.flu_path + 'fluazu.stat'
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
        self.maxReconnectTries = maxReconTries

        # more vars
        self.logger.info("flu-path: %s" % str(self.flu_path))
        self.logger.info("azu-host: %s" % str(self.azu_host))
        self.logger.info("azu-port: %s" % str(self.azu_port))
        self.logger.info("azu-secure: %s" % str(self.azu_secure))
        if len(self.azu_user) > 0:
            self.logger.info("azu-user: %s" % str(self.azu_user))
            self.logger.info("azu-pass: %s" % str(self.azu_pass))

        # initialize
        if not self.initialize():
            self.logger.error("there were problems initializing fluazu, shutting down...")
            self.stop()

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

        # flu

        # check dirs
        if not self.checkDirs():
            self.logger.error("Error checking dirs. path: %s" % self.tf_path)
            return False

        # delete command-file if exists
        if os.path.isfile(self.flu_fileCommand):
            try:
                self.logger.info("removing command-file %s ..." % self.flu_fileCommand)
                os.remove(self.flu_fileCommand)
            except:
                self.logger.error("Failed to delete commandfile %s" % self.flu_fileCommand)
                return False

        # load transfers
        self.loadTransfers()

        # azu
        self.logger.info("connecting to Azureus-Server (%s:%d)..." % (self.azu_host, self.azu_port))

        # set connection details
        connection_details = {}
        connection_details['host'] = self.azu_host
        connection_details['port'] = self.azu_port
        connection_details['secure'] = self.azu_secure
        if len(self.azu_user) > 0:
            connection_details['user'] = self.azu_user
            connection_details['password'] = self.azu_pass

        # make connection
        try:
            self.connection = make_connection(**connection_details)
            self.connection.is_persistent_connection = True
            self.interface = self.connection.get_plugin_interface()
        except Exception, e:
            self.logger.error("could not connect to Azureus-Server (%s)" % e)
            return False

        # azureus version
        self.azu_version_str = str(self.connection.get_azureus_version())
        self.azu_version_str = self.azu_version_str.replace(", ", ".")
        self.azu_version_str = self.azu_version_str.replace("(", "")
        self.azu_version_str = self.azu_version_str.replace(")", "")
        self.logger.info("connected. Azureus-Version: %s" % self.azu_version_str)

        # download-manager
        self.dm = self.interface.getDownloadManager()
        if self.dm is None:
            self.logger.error("Error getting Download-Manager object")
            return False

        # write stat-file and return
        return self.writeStatFile()

    """ -------------------------------------------------------------------- """
    """ shutdown                                                             """
    """ -------------------------------------------------------------------- """
    def shutdown(self):
    
        # log
        self.logger.info("fluazu shutting down...")

        # delete stat-file if exists
        if os.path.isfile(self.flu_fileStat):
            try:
                self.logger.info("deleting stat-file %s ..." % self.flu_fileStat)
                os.remove(self.flu_fileStat)
            except:
                self.logger.error("Failed to delete stat-file %s " % self.flu_fileStat)

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):
    
        # log
        self.logger.info("fluazu stopping...")
    
        # flag
        self.running = 0
        
    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        return (self.running == 1)

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running > 0:

            # check if connection still valid, shutdown if it is not
            if not self.checkAzuConnection():
                # stop
                self.stop()
                # return
                return 1

            # update downloads
            self.updateDownloads()

            # update transfers
            for transfer in self.transfers:
                if transfer.name in self.downloads:
                    # update
                    transfer.update(self.downloads[transfer.name])

            # inner loop
            for i in range(4):

                # process daemon command stack
                if self.processCommandStack():
                    # stop
                    self.stop()
                    # return
                    return 0

                # process transfers command stacks
                for transfer in self.transfers:
                    if transfer.isRunning():
                        if transfer.processCommandStack(self.downloads[transfer.name]):
                            # update downloads
                            self.updateDownloads()

                # sleep
                time.sleep(1)

        # return
        return 0

    """ -------------------------------------------------------------------- """
    """ reload                                                               """
    """ -------------------------------------------------------------------- """
    def reload(self):
    
        # log
        self.logger.info("reloading...")

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
    
        # log
        self.logger.info("processing delete-requests...")

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
                    self.logger.error("Failed to delete file : %s" % delFile)
        except:
            return False

        # process requests
        if len(requests) > 0:
            for fileName in requests:
                self.logger.info("deleting %s ..." % fileName)
                # update downloads
                self.downloads = {}
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
                    self.logger.error("Failed to delete file : %s" % delFile)

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ processRunRequests                                                   """
    """ -------------------------------------------------------------------- """
    def processRunRequests(self):
    
        # log
        self.logger.info("processing run-requests...")

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
                    self.logger.error("Failed to move file : %s" % inputFile)
        except:
            return False

        # process requests
        if len(requests) > 0:
            try:
                # update downloads
                self.downloads = {}
                self.updateDownloads()
                for fileName in requests:
                    # add if needed
                    if fileName not in self.downloads:
                        try:
                            # add
                            self.addTransfer(fileName)
                        except:
                            self.logger.error("exception when adding new transfer %s" % fileName)
                            raise
                    # downloads
                    tries = 0
                    while tries < 5 and fileName not in self.downloads:
                        #if fileName not in self.downloads:
                        self.logger.info("download %s missing, update downloads..." % fileName)
                        self.updateDownloads()
                        # sleep + increment
                        time.sleep(1)
                        tries += 1
                    # start transfer
                    if fileName in self.downloads:
                        try:
                            transfer = Transfer(self.logger, self.tf_pathTransfers, self.flu_pathTransfers, fileName)
                            transfer.start(self.downloads[fileName])
                        except:
                            self.logger.error("exception when starting new transfer %s" % fileName)
                            raise
                    else:
                        self.logger.error("download %s not in azureus-downloads, cannot start it." % fileName)
            except Exception, e:
                self.logger.info("exception when processing run-requests: %s" % e)

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ addTransfer                                                          """
    """ -------------------------------------------------------------------- """
    def addTransfer(self, tname):
    
        # log
        self.logger.info("adding new transfer %s ..." % tname)
        
        # add
        try:
            # transfer-object
            transfer = Transfer(self.logger, self.tf_pathTransfers, self.flu_pathTransfers, tname)

            # torrent-object
            torrent = self.interface.getTorrentManager().createFromBEncodedFile(transfer.fileTorrent)

            # file-objects
            fileSource = dopal.aztypes.wrap_file(transfer.fileTorrent)
            fileTarget = dopal.aztypes.wrap_file(transfer.tf.savepath)

            # add
            self.dm.addDownload(torrent, fileSource, fileTarget)

            # return
            return True
        except Exception, e:
            self.logger.info("exception when adding transfer: %s" % e)
            return False

    """ -------------------------------------------------------------------- """
    """ removeTransfer                                                       """
    """ -------------------------------------------------------------------- """
    def removeTransfer(self, tname):
    
        # log
        self.logger.info("removing transfer %s ..." % tname)
        
        # remove
        try:
            self.downloads[tname].remove()
            return True
        except Exception, e:
            self.logger.info("exception when removing transfer: %s" % e)
            return False

    """ -------------------------------------------------------------------- """
    """ loadTransfers                                                        """
    """ -------------------------------------------------------------------- """
    def loadTransfers(self):
    
        # log
        self.logger.info("loading transfers...")
        
        # load
        self.transfers = []
        try:
            for fileName in os.listdir(self.flu_pathTransfers):
                self.transfers.append(Transfer(self.logger, self.tf_pathTransfers, self.flu_pathTransfers, fileName))
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
            self.logger.info("Processing command-file %s ..." % self.flu_fileCommand)
            try:

                # read file to mem
                try:
                    f = open(self.flu_fileCommand, 'r')
                    data = f.read()
                    f.close()
                except:
                    self.logger.error("Failed to read command-file : %s" % self.flu_fileCommand)
                    raise

                # delete file
                try:
                    os.remove(self.flu_fileCommand)
                except:
                    self.logger.error("Failed to delete command-file : %s" % self.flu_fileCommand)

                # exec commands
                if len(data) > 0:
                    commands = data.split("\n")
                    if len(commands) > 0:
                        for command in commands:
                            if len(command) > 0:
                                try:
                                    # exec, early out when reading a quit-command
                                    if self.execCommand(command):
                                        return True
                                except:
                                    self.logger.error("Failed to exec command: %s" % command)
                    else:
                        self.logger.info("No commands found.")
                else:
                    self.logger.info("No commands found.")

            except:
                self.logger.error("Failed to process command-stack : %s" % self.flu_fileCommand)
        return False

    """ -------------------------------------------------------------------- """
    """ execCommand                                                          """
    """ -------------------------------------------------------------------- """
    def execCommand(self, command):

        # op-code
        opCode = command[0]

        # q
        if opCode == 'q':
            self.logger.info("command: stop-request, setting shutdown-flag...")
            return True

        # r
        elif opCode == 'r':
            self.logger.info("command: reload-request, reloading...")
            self.reload()
            return False

        # u
        elif opCode == 'u':
            if len(command) < 2:
                self.logger.info("invalid rate.")
                return False
            rateNew = command[1:]
            self.logger.info("command: setting upload-rate to %s ..." % rateNew)
            self.setRateU(int(rateNew))
            return False

        # d
        elif opCode == 'd':
            if len(command) < 2:
                self.logger.info("invalid rate.")
                return False
            rateNew = command[1:]
            self.logger.info("command: setting download-rate to %s ..." % rateNew)
            self.setRateD(int(rateNew))
            return False

        # s
        elif opCode == 's':
            try:
                if len(command) < 3:
                    raise
                workLoad = command[1:]
                sets = workLoad.split(":")
                setKey = sets[0]
                setVal = sets[1]
                if len(setKey) < 1 or len(setVal) < 1:
                    raise
                self.logger.info("command: changing setting %s to %s ..." % (setKey, setVal))
                if self.changeSetting(setKey, setVal):
                    self.writeStatFile()
                return False
            except:
                self.logger.info("invalid setting.")
                return False

        # default
        else:
            self.logger.info("op-code unknown: %s" % opCode)
            return False

    """ -------------------------------------------------------------------- """
    """ checkDirs                                                            """
    """ -------------------------------------------------------------------- """
    def checkDirs(self):

        # tf-paths
        if not os.path.isdir(self.tf_path):
            self.logger.error("Invalid path-dir: %s" % self.tf_path)
            return False
        if not os.path.isdir(self.tf_pathTransfers):
            self.logger.error("Invalid tf-transfers-dir: %s" % self.tf_pathTransfers)
            return False

        # flu-paths
        if not os.path.isdir(self.flu_path):
            try:
                self.logger.info("flu-main-path %s does not exist, trying to create ..." % self.flu_path)
                os.mkdir(self.flu_path, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create flu-main-path %s" % self.flu_path)
                return False
        if not os.path.isdir(self.flu_pathTransfers):
            try:
                self.logger.info("flu-transfers-path %s does not exist, trying to create ..." % self.flu_pathTransfers)
                os.mkdir(self.flu_pathTransfers, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create flu-main-path %s" % self.flu_pathTransfers)
                return False
        if not os.path.isdir(self.flu_pathTransfersRun):
            try:
                self.logger.info("flu-transfers-run-path %s does not exist, trying to create ..." % self.flu_pathTransfersRun)
                os.mkdir(self.flu_pathTransfersRun, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create flu-main-path %s" % self.flu_pathTransfersRun)
                return False
        if not os.path.isdir(self.flu_pathTransfersDel):
            try:
                self.logger.info("flu-transfers-del-path %s does not exist, trying to create ..." % self.flu_pathTransfersDel)
                os.mkdir(self.flu_pathTransfersDel, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create flu-main-path %s" % self.flu_pathTransfersDel)
                return False

        # return
        return True

    """ -------------------------------------------------------------------- """
    """ changeSetting                                                        """
    """ -------------------------------------------------------------------- """
    def changeSetting(self, key, val):
        try:

            # get plugin-config
            config_object = self.interface.getPluginconfig()

            # core-keys
            coreKeys = self.getKoreKeys()
            if key not in coreKeys:
                self.logger.info("settings-key unknown: %s" % key)
                return False

            # change setting
            try:
                config_object.setIntParameter(coreKeys[key], int(val))
                return True
            except Exception, e:
                self.logger.info("Failed to change setting %s to %s (%s)" % (key, val, e))
                return False

        except Exception, e:
            self.logger.info("Failed to get Plugin-Config. (%s)" % e)
        return False
        
    """ -------------------------------------------------------------------- """
    """ getKoreKeys                                                        """
    """ -------------------------------------------------------------------- """
    def getKoreKeys(self):
        try:

            # get plugin-config
            config_object = self.interface.getPluginconfig()

            # core-keys
            return { \
                'CORE_PARAM_INT_MAX_ACTIVE': config_object.CORE_PARAM_INT_MAX_ACTIVE, \
                'CORE_PARAM_INT_MAX_ACTIVE_SEEDING': config_object.CORE_PARAM_INT_MAX_ACTIVE_SEEDING, \
                'CORE_PARAM_INT_MAX_CONNECTIONS_GLOBAL': config_object.CORE_PARAM_INT_MAX_CONNECTIONS_GLOBAL, \
                'CORE_PARAM_INT_MAX_CONNECTIONS_PER_TORRENT': config_object.CORE_PARAM_INT_MAX_CONNECTIONS_PER_TORRENT, \
                'CORE_PARAM_INT_MAX_DOWNLOAD_SPEED_KBYTES_PER_SEC': config_object.CORE_PARAM_INT_MAX_DOWNLOAD_SPEED_KBYTES_PER_SEC, \
                'CORE_PARAM_INT_MAX_DOWNLOADS': config_object.CORE_PARAM_INT_MAX_DOWNLOADS, \
                'CORE_PARAM_INT_MAX_UPLOAD_SPEED_KBYTES_PER_SEC': config_object.CORE_PARAM_INT_MAX_UPLOAD_SPEED_KBYTES_PER_SEC, \
                'CORE_PARAM_INT_MAX_UPLOAD_SPEED_SEEDING_KBYTES_PER_SEC': config_object.CORE_PARAM_INT_MAX_UPLOAD_SPEED_SEEDING_KBYTES_PER_SEC, \
                'CORE_PARAM_INT_MAX_UPLOADS': config_object.CORE_PARAM_INT_MAX_UPLOADS, \
                'CORE_PARAM_INT_MAX_UPLOADS_SEEDING': config_object.CORE_PARAM_INT_MAX_UPLOADS_SEEDING \
            }

        except Exception, e:
            self.logger.info("Failed to get Plugin-Config. (%s)" % e)
        return False
        
    """ -------------------------------------------------------------------- """
    """ getStatus                                                            """
    """ -------------------------------------------------------------------- """
    def getStatus(self):
        retVal = {}
        try:
        
            # azu-version
            retVal['azu_version'] = self.azu_version_str
            
            # get plugin-config
            config_object = self.interface.getPluginconfig()
            
            # core-keys
            coreKeys = self.getKoreKeys()
            
            # add core-vars
            for coreName, coreVar in coreKeys.iteritems():
                try:
                    retVal[coreName] = config_object.getIntParameter(coreVar, 0)
                except Exception, e:
                    retVal[coreName] = 0
                    self.logger.error(e)

        except Exception, e:
            self.logger.error("Exception when getting Status. (%s)" % e)
        return retVal

    """ -------------------------------------------------------------------- """
    """ writeStatFile                                                        """
    """ -------------------------------------------------------------------- """
    def writeStatFile(self):
        try:

            # get plugin-config
            config_object = self.interface.getPluginconfig()

            # get vars
            coreVars = [ \
                config_object.CORE_PARAM_INT_MAX_ACTIVE, \
                config_object.CORE_PARAM_INT_MAX_ACTIVE_SEEDING, \
                config_object.CORE_PARAM_INT_MAX_CONNECTIONS_GLOBAL, \
                config_object.CORE_PARAM_INT_MAX_CONNECTIONS_PER_TORRENT, \
                config_object.CORE_PARAM_INT_MAX_DOWNLOAD_SPEED_KBYTES_PER_SEC, \
                config_object.CORE_PARAM_INT_MAX_DOWNLOADS, \
                config_object.CORE_PARAM_INT_MAX_UPLOAD_SPEED_KBYTES_PER_SEC, \
                config_object.CORE_PARAM_INT_MAX_UPLOAD_SPEED_SEEDING_KBYTES_PER_SEC, \
                config_object.CORE_PARAM_INT_MAX_UPLOADS, \
                config_object.CORE_PARAM_INT_MAX_UPLOADS_SEEDING \
            ]
            coreParams = {}
            for coreVar in coreVars:
                try:
                    coreParams[coreVar] = config_object.getIntParameter(coreVar, 0)
                except Exception, e:
                    coreParams[coreVar] = 0
                    self.logger.error(e)

            # write file
            try:
                f = open(self.flu_fileStat, 'w')
                f.write("%s\n" % self.azu_host)
                f.write("%d\n" % self.azu_port)
                f.write("%s\n" % self.azu_version_str)
                for coreVar in coreVars:
                    f.write("%d\n" % coreParams[coreVar])
                f.flush()
                f.close()
                return True
            except Exception, e:
                self.logger.error("Failed to write statfile %s (%s)" % (self.flu_fileStat, e))

        except Exception, e:
            self.logger.error("Failed to get Plugin-Config. (%s)" % e)
        return False

    """ -------------------------------------------------------------------- """
    """ setRateU                                                             """
    """ -------------------------------------------------------------------- """
    def setRateU(self, rate):
        try:
            config_object = self.interface.getPluginconfig()
            config_object.set_upload_speed_limit(rate)
            return True
        except Exception, e:
            self.logger.info("Failed to set upload-rate. (%s)" % e)
            return False

    """ -------------------------------------------------------------------- """
    """ setRateD                                                             """
    """ -------------------------------------------------------------------- """
    def setRateD(self, rate):
        try:
            config_object = self.interface.getPluginconfig()
            config_object.set_download_speed_limit(rate)
            return True
        except Exception, e:
            self.logger.info("Failed to set download-rate. (%s)" % e)
            return False

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
            self.logger.info("connection to Azureus-server lost, reconnecting to %s:%d ..." % (self.azu_host, self.azu_port))

            # try to reconnect
            for i in range(self.maxReconnectTries):

                # sleep
                time.sleep(i << 2)

                # out
                self.logger.info("reconnect-try %d ..." % (i + 1))

                # establish con
                try:
                    self.connection.establish_connection(True)
                    self.logger.info("established connection to Azureus-server")
                except Exception, e:
                    self.logger.error("Error establishing connection to Azureus-server (%s)" % e)
                    continue

                # interface
                try:
                    self.interface = self.connection.get_plugin_interface()
                except LinkError, error:
                    self.logger.error("Error getting interface object (%s)" % e)
                    self.interface = None
                    continue

                # download-manager
                try:
                    self.dm = None
                    self.dm = self.interface.getDownloadManager()
                    if self.dm is None:
                        raise
                    else:
                        return True
                except:
                    self.logger.error("Error getting Download-Manager object")
                    continue

            # seems like azu is down. give up
            self.logger.error("no connection after %d tries, i give up, azu is gone" % self.maxReconnectTries)
            return False
