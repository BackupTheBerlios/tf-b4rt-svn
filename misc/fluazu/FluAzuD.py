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
# standard
import time
from time import time, strftime, sleep
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
        # flux-settings
        self.flu_path = ''
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

        # dump vars
        print "path: %s" % path
        print "host: %s" % host
        print "port: %s" % port
        print "secure: %s" % secure
        print "user: %s" % username
        print "pass: %s" % password

        # set vars
        self.flu_path = path
        self.azu_host = host
        self.azu_port = int(port)
        if secure == '1':
            self.azu_secure = True
        else:
            self.azu_secure = False
        self.azu_user = username
        self.azu_pass = password

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
            print "there were problems, not starting up daemon-mainloop."
            return 0

        # main
        self.main()

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running > 0:

            # check if connection still valid
            if not self.checkConnection():
                return 0

            # process downloads
            downloads = self.dm.getDownloads()
            for download in downloads:
                print "* %s" % str(download)
                print "  %s" % str(download.getTorrentFileName())
                torrent = download.getTorrent()
                print "  %s" % str(torrent.getName())
                print "  getState: %s" % str(download.getState())
                print "  getSize: %s" % str(torrent.getSize())
                stats = download.getStats()
                print "  getUploaded: %s" % str(stats.getUploaded())
                print "  getDownloaded: %s" % str(stats.getDownloaded())
                print "  getUploadAverage: %s" % str(stats.getUploadAverage())
                print "  getDownloadAverage: %s" % str(stats.getDownloadAverage())

            # inner loop
            for i in range(5):

				# debug
                print "%s (%s)" % (self.flu_path, str(i))

                # sleep
                sleep(1)

        # return
        return 1

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self):

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
            print "Connected to %s" % self.azu_host
        else:
            print "Error getting plugin interface object - could not connect to Azureus, error:\n %s" % connection_error.to_error_string()

        # print azureus version
        print "Azureus-Version: " + str(self.connection.get_azureus_version())

        # download-manager
        self.dm = self.interface.getDownloadManager()
        if self.dm is None:
            print "Error getting plugin Download-Manager object"
            return 0

    """ -------------------------------------------------------------------- """
    """ checkConnection                                                      """
    """ -------------------------------------------------------------------- """
    def checkConnection(self):

        # con valid
        if self.connection.is_connection_valid():
            return True

        # con not valid
        else:

            # establish con
            try:
                self.connection.establish_connection(True)
                print "established connection to " + self.azu_host
            except:
                print "Error establishing connection to " + self.azu_host
                return False

            # interface
            try:
                self.interface = self.connection.get_plugin_interface()
            except LinkError, error:
                self.interface = None
            if self.interface is None:
                print "Error getting plugin interface object"
                return False

            # download-manager
            self.dm = None
            self.dm = self.interface.getDownloadManager()
            if self.dm is None:
                print "Error getting plugin Download-Manager object"
                return False
            else:
                return True

