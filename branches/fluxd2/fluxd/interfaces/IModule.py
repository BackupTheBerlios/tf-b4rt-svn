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

""" ------------------------------------------------------------------------ """
""" IModule                                                                  """
""" ------------------------------------------------------------------------ """
class IModule(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):
        raise Exception, 'IModule.__init__ not implemented'

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self, requestHandler, onStopDelegate = None):
        raise Exception, 'IModule.start not implemented'

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):
        raise Exception, 'IModule.stop not implemented'

    """ -------------------------------------------------------------------- """
    """ isRunning                                                            """
    """ -------------------------------------------------------------------- """
    def isRunning(self):
        raise Exception, 'IModule.isRunning not implemented'

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        raise Exception, 'IModule.status not implemented'

    """ -------------------------------------------------------------------- """
    """ command                                                              """
    """ -------------------------------------------------------------------- """
    def command(self, cmd):
        raise Exception, 'IModule.command not implemented'

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        raise Exception, 'IModule.getName not implemented'

    """ -------------------------------------------------------------------- """
    """ getVersion                                                           """
    """ -------------------------------------------------------------------- """
    def getVersion(self):
        raise Exception, 'IModule.getVersion not implemented'

    """ -------------------------------------------------------------------- """
    """ setRequestHandler                                                    """
    """ -------------------------------------------------------------------- """
    def setRequestHandler(self, requestHandler):
        raise Exception, 'IModule.setRequestHandler not implemented'

    """ -------------------------------------------------------------------- """
    """ addOnStopDelegate                                                    """
    """ -------------------------------------------------------------------- """
    def addOnStopDelegate(self, onStopDelegate):
        raise Exception, 'IModule.addOnStopDelegate not implemented'

    """ -------------------------------------------------------------------- """
    """ removeOnStopDelegate                                                 """
    """ -------------------------------------------------------------------- """
    def removeOnStopDelegate(self, onStopDelegate):
        raise Exception, 'IModule.removeOnStopDelegate not implemented'
