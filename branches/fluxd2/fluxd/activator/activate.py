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
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
################################################################################

""" ------------------------------------------------------------------------ """
""" activate                                                                 """
""" ------------------------------------------------------------------------ """
def activate():

	# LoggerFactory
	loggerPackage = Config().get('logging', 'LoggerFactoryPackage').strip()
	loggerClass = Config().get('logging', 'LoggerFactory').strip()
	try:
		Activator().registerInstance(
			'%s.%s' % (loggerPackage, loggerClass),
			loggerClass,
			'LoggerFactory')
	except Exception, e:
		raise Exception, "Failed to activate LoggerFactory %s.%s (%s)" % (loggerPackage, loggerClass, e)

	# Fluxcli
	try:
		Activator().registerInstance(
			'fluxd.fluxcli.Fluxcli',
			'Fluxcli',
			'Fluxcli')
	except Exception, e:
		raise Exception, "Failed to activate Fluxcli (%s)" % (e)

	# DatabaseManager
	try:
		Activator().registerInstance(
			'fluxd.database.DatabaseManager',
			'DatabaseManager',
			'DatabaseManager')
	except Exception, e:
		raise Exception, "Failed to activate DatabaseManager (%s)" % (e)

	# ServerManager
	try:
		Activator().registerInstance(
			'fluxd.server.ServerManager',
			'ServerManager',
			'ServerManager')
	except Exception, e:
		raise Exception, "Failed to activate ServerManager (%s)" % (e)

	# ModuleManager
	try:
		Activator().registerInstance(
			'fluxd.modules.ModuleManager',
			'ModuleManager',
			'ModuleManager')
	except Exception, e:
		raise Exception, "Failed to activate ModuleManager (%s)" % (e)

	# Dispatcher
	try:
		Activator().registerInstance(
			'fluxd.dispatcher.Dispatcher',
			'Dispatcher',
			'Dispatcher')
	except Exception, e:
		raise Exception, "Failed to activate Dispatcher (%s)" % (e)
