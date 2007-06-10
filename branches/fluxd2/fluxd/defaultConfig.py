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
# standard-imports
import ConfigParser
################################################################################

""" ------------------------------------------------------------------------ """
""" defaultConfig                                                            """
""" ------------------------------------------------------------------------ """
def defaultConfig():

	# configParser
	configParser = ConfigParser.ConfigParser()
	setattr(configParser, 'optionxform' , str)

	# dir
	configParser.add_section('dir')
	configParser.set('dir', 'docroot', '/var/www')
	configParser.set('dir', 'pathTf', '/usr/local/torrentflux')
	configParser.set('dir', 'pathFluxd', '/usr/local/torrentflux/.fluxd')

	# file
	configParser.add_section('file')
	configParser.set('file', 'php', '/usr/bin/php')
	configParser.set('file', 'log', '/usr/local/torrentflux/.fluxd/fluxd.log')
	configParser.set('file', 'pid', '/usr/local/torrentflux/.fluxd/fluxd.pid')
	configParser.set('file', 'cfg', '/var/www/bin/fluxd/fluxd.cfg')

	# logging
	configParser.add_section('logging')
	configParser.set('logging', 'LoggerFactoryPackage', 'fluxd.logging.LoggerFactory')
	configParser.set('logging', 'LoggerFactory', 'LoggerFactoryFile')
	configParser.set('logging', 'Level', 'DEBUG')
	configParser.set('logging', 'Dateformat', '%Y/%m/%d - %H:%M:%S')

	# database
	configParser.add_section('database')
	configParser.set('database', 'DataAdapterPackage', 'fluxd.database.DataAdapter')
	configParser.set('database', 'DataAdapter', 'DataAdapterFluxcli')

	# server
	configParser.add_section('server')
	configParser.set('server', 'Servers', 'ServerUnixSocket, ServerInetSocket')

	# ServerUnixSocket
	configParser.add_section('ServerUnixSocket')
	configParser.set('ServerUnixSocket', 'enabled', 'True')
	configParser.set('ServerUnixSocket', 'module', 'fluxd.server.Server.ServerUnixSocket')
	configParser.set('ServerUnixSocket', 'class', 'ServerUnixSocket')
	configParser.set('ServerUnixSocket', 'path', '/usr/local/torrentflux/.fluxd/fluxd.sock')

	# ServerInetSocket
	configParser.add_section('ServerInetSocket')
	configParser.set('ServerInetSocket', 'enabled', 'True')
	configParser.set('ServerInetSocket', 'module', 'fluxd.server.Server.ServerInetSocket')
	configParser.set('ServerInetSocket', 'class', 'ServerInetSocket')
	configParser.set('ServerInetSocket', 'host', 'auto')
	configParser.set('ServerInetSocket', 'port', '45454')

	# modules
	configParser.add_section('modules')
	configParser.set('modules', 'Modules', 'Dummy, Maintenance')

	# Dummy
	configParser.add_section('Dummy')
	configParser.set('Dummy', 'enabled', 'True')
	configParser.set('Dummy', 'module', 'fluxd.modules.Modules.Dummy')
	configParser.set('Dummy', 'class', 'Dummy')

	# Maintenance
	configParser.add_section('Maintenance')
	configParser.set('Maintenance', 'enabled', 'True')
	configParser.set('Maintenance', 'module', 'fluxd.modules.Modules.Maintenance')
	configParser.set('Maintenance', 'class', 'Maintenance')
	configParser.set('Maintenance', 'interval', '60')
	configParser.set('Maintenance', 'restart', 'False')

	# return
	return configParser
