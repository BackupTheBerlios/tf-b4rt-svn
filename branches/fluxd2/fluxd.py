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
import os
import sys
# fluxd-imports
from fluxd.Config import Config
from fluxd.daemon.Daemon import Daemon
################################################################################

""" ------------------------------------------------------------------------ """
""" printVersion                                                             """
""" ------------------------------------------------------------------------ """
def printVersion():
	"""Print Version for fluxd"""
	from fluxd import __version_str__
	print "fluxd %s" % __version_str__

""" ------------------------------------------------------------------------ """
""" printUsage                                                               """
""" ------------------------------------------------------------------------ """
def printUsage():
	"""Print Usage for fluxd"""
	printVersion()
	print "\nUsage:"
	print "fluxd.py operation [config-settings]"
	print "\nOperations:"
	print "start : start fluxd"
	print "stop  : stop fluxd"
	print "\nDefault-Config-Settings:"
	print Config().defaultConfigAsArgString()
	print "\nExamples:"
	print "fluxd.py start --dir.docroot='/var/www' --dir.pathTf='/usr/local/torrentflux' --dir.pathFluxd='/usr/local/torrentflux/.fluxd' --file.php='/usr/bin/php'"
	print "fluxd.py start --file.cfg='/var/www/bin/fluxd/fluxd.cfg'"
	print "fluxd.py stop --file.cfg='/var/www/bin/fluxd/fluxd.cfg'"
	print

""" ------------------------------------------------------------------------ """
""" __main__                                                                 """
""" ------------------------------------------------------------------------ """
if __name__ == "__main__":

	# version + help
	if sys.argv[1:] == ['--version']:
		printVersion()
		sys.exit(0)
	elif sys.argv[1:] == ['--help']:
		printUsage()
		sys.exit(0)

	# process args, init config and get daemon-method
	daemonMethod = None
	try:
		try:
			# initialize config and process args
			Config().initialize(sys.argv)
			# get daemon-method
			daemonMethod = getattr(Daemon(), sys.argv[1])
			# print daemon-method
			print "fluxd-%s..." % (sys.argv[1])
		except AttributeError:
			raise Exception, "Error: invalid op: %s" % args[1]
		except Exception, e:
			raise e
	except Exception, e:
		print e
		printUsage()
		sys.exit(1)

	# invoke daemon-method
	try:
		sys.exit(daemonMethod())
	except Exception, e:
		print e
		sys.exit(1)
