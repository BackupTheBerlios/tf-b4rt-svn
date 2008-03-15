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
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.modules.Modules.BasicModule import BasicModule
from fluxd.decorators.synchronized import synchronized
from fluxd.classes.StatFile import StatFile
# fluazu-imports
from fluxd.modules.Modules.fluazu.FluAzuD import FluAzuD
################################################################################

__version__ = (0, 0, 1)
__version_str__ = '%s.%s' % (__version__[0], ''.join([str(part) for part in __version__[1:]]))

""" ------------------------------------------------------------------------ """
""" Fluazu                                                                    """
""" ------------------------------------------------------------------------ """
class Fluazu(BasicModule):

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)

        # config
        self.path_tf = Config().get('dir', 'pathTf').strip()
        self.path_fluxd = Config().get('dir', 'pathFluxd').strip()
        self.host = Config().getExt(name, 'host').strip()
        self.port = int(Config().getExt(name, 'port').strip())
        self.secure = Config().getExt(name, 'secure').strip()
        self.username = Config().getExt(name, 'username').strip()
        self.password = Config().getExt(name, 'password').strip()
        self.maxReconnectTries = int(Config().get(name, 'maxReconnectTries').strip())
        
        # fluazu-daemon
        self.fluazud = FluAzuD(self.logger)

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        data = {}
        data['version'] = __version_str__
        fluazudStatus = self.fluazud.getStatus()
        for ke, va in fluazudStatus.iteritems():
            data[ke] = va
        for transfer in self.fluazud.transfers:
            data["Transfer %s" % transfer.name] = transfer.state
        for dName, dObj in self.fluazud.downloads.iteritems():
            data["Download %s" % dName] = dObj.__str__()
        return data

    """ -------------------------------------------------------------------- """
    """ command                                                              """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def command(self, cmd):

        # log
        self.logger.debug('command: %s' % cmd)

        # stop
        if cmd == 'stop':
            if self.running:
                self.running = False
                self.fluazud.stop()
                return 'initialize Module-shutdown...'
            else:
                return 'Module not running'

        # return
        return cmd

    """ -------------------------------------------------------------------- """
    """ getVersion                                                           """
    """ -------------------------------------------------------------------- """
    def getVersion(self):
        return __version_str__

    """ -------------------------------------------------------------------- """
    """ onStart                                                              """
    """ -------------------------------------------------------------------- """
    def onStart(self):

        # DEBUG
        self.logger.debug('onStart')
        
        # run fluazud
        self.fluazud.run(self.path_tf, self.path_fluxd, self.host, self.port, self.secure, self.username, self.password, self.maxReconnectTries)

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        while self.running and self.fluazud.isRunning():

            try:

                # call fluazud-main
                self.fluazud.main()

            except Exception, e:
                if self.running:
                    self.logger.error("Exception in Module-Thread (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ onStop                                                               """
    """ -------------------------------------------------------------------- """
    def onStop(self):

        # DEBUG
        self.logger.debug('onStop')
        
        # shutdown fluazud
        self.fluazud.shutdown()
