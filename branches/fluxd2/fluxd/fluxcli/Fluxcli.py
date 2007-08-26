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
import popen2
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IActivator import IActivator
from fluxd.activator.Activator import Activator
from fluxd.decorators.synchronized import synchronized
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if Fluxcli.Instance is None:
        raise Exception, 'Fluxcli not initialized'
    return Fluxcli.Instance

""" ------------------------------------------------------------------------ """
""" Fluxcli                                                                  """
""" ------------------------------------------------------------------------ """
class Fluxcli(IActivator):

    # instance
    Instance = None

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if Fluxcli.Instance is None:
            Fluxcli.Instance = object.__new__(cls, *p, **k)
        return Fluxcli.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('Fluxcli')

        # invocation-Count
        self.invocationCount = 0

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ invoke                                                               """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def invoke(self, args = [], readResult = True):

        # increment counter
        self.invocationCount += 1

        # log
        self.logger.info('invoking fluxcli...')

        try:

            # unshift fluxcli-arg
            args.insert(0, '%s%s' % (Config().get('dir', 'docroot').strip(), "bin/fluxcli.php"))

            # unshift php-arg (command is invoked thru an args
            # list, not by building a string command-line given
            # to a shell -- this avoids any quoting troubles)
            php = Config().get('file', 'php').strip()
            args.insert(0, php)

            # log pseudo-cmdline (see above, php is not actually invoked that way)
            self.logger.debug(' '.join([("'%s'" % arg) for arg in args]))

            # open
            if readResult:
                # invoke (use popen2.Popen3 directly to be able to reap
                # child correctly -- using os.popen2 leaves zombies)
                p = popen2.Popen3(args)
                p.tochild.close()
                result = p.fromchild.read()
                p.fromchild.close()
                p.wait()
                # return result
                return result

            # spawn
            else:
                # invoke and return bool
                return (os.spawnv(os.P_WAIT, php, args) == 0)

        except Exception, e:
            self.logger.error("Exception in invoke: %s" % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ beginInvoke                                                          """
    """ -------------------------------------------------------------------- """
    def beginInvoke(self, args = [], readResult = True, callback = None):
        raise Exception, 'Fluxcli.beginInvoke not implemented'
