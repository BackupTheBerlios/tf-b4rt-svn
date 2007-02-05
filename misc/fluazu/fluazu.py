#!/usr/bin/env python
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
#                                                                              #
#  Requirements :                                                              #
#   * DOPAL                                                                    #
#     http://dopal.sourceforge.net                                             #
#   * Azureus with XML/HTTP Plugin                                             #
#     http://azureus.sourceforge.net                                           #
#     http://azureus.sourceforge.net/plugin_details.php?plugin=xml_http_if)    #
#                                                                              #
################################################################################
import commands, os, re, sys
from os import popen, getpid, remove
from sys import argv, stdout
from time import time, strftime
import thread, threading, time
################################################################################

################################################################################
# fields                                                                       #
################################################################################
# version
version_fluazu = '0.1'
# azu
azu_host = '127.0.0.1'
azu_port = 6884
azu_secure = False
azu_user = ''
azu_pass = ''

################################################################################
# methods                                                                      #
################################################################################

def run(host, port, secure, username, password):

    # debug
    print "host : " + host
    print "port : " + port
    print "secure : " + secure
    print "user : " + username
    print "pass : " + password

    # set vars
    azu_host = host
    azu_port = int(port)
    if secure == 1:
        azu_secure = True
    else:
        azu_secure = False
    azu_user = username
    azu_pass = password

    # set connection details
    connection_details = {}
    connection_details['host'] = azu_host
    connection_details['port'] = azu_port
    connection_details['secure'] = azu_secure
    if len(azu_user) > 0:
        connection_details['user'] = azu_user
        connection_details['password'] = azu_pass

    # connect
    from dopal.main import make_connection
    connection = make_connection(**connection_details)
    connection.is_persistent_connection = True
    from dopal.errors import LinkError
    try:
        interface = connection.get_plugin_interface()
    except LinkError, error:
        interface = None
        connection_error = error
    else:
        connection_error = None
    from dopal import __version_str__
    print  "DOPAL %s" % __version_str__
    if connection_error is None:
        print "Connected"
    else:
        print "Error getting plugin interface object - could not connect to Azureus, error:\n %s" % connection_error.to_error_string()

    # version
    print "Azureus-Version : " + str(connection.get_azureus_version())

    # download-manager
    dm = interface.getDownloadManager()

    # print downloads
    downloads = dm.getDownloads()
    for download in downloads:
        print "* " + str(download)
        print "  " + str(download.getTorrentFileName())
        torrent = download.getTorrent()
        print "  " + str(torrent.getName())
        print "  getState: " + str(download.getState())
        print "  getSize: " + str(torrent.getSize())
        stats = download.getStats()
        print "  getUploadAverage: " + str(stats.getUploadAverage())
        print "  getDownloadAverage: " + str(stats.getDownloadAverage())
        print "  getUploaded: " + str(stats.getUploaded())
        print "  getDownloaded: " + str(stats.getDownloaded())

    # exit
    sys.exit(0)

################################################################################
# main                                                                         #
################################################################################

if __name__ == '__main__':

    # version
    if argv[1:] == ['--version']:
        print version_fluazu
        sys.exit(0)

    # check argv-length
    if len(argv) < 6:
        print "Error : missing arguments, exiting. \n"
        sys.exit(0)

    # run
    run(argv[1], argv[2], argv[3], argv[4], argv[5])





