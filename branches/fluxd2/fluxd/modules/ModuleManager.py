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
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IActivator import IActivator
from fluxd.activator.Activator import Activator
from fluxd.functions.generic import getClassByName
from fluxd.decorators.synchronized import synchronized
from fluxd.interfaces.IModule import IModule
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if ModuleManager.Instance is None:
        raise Exception, 'ModuleManager not initialized'
    return ModuleManager.Instance

""" ------------------------------------------------------------------------ """
""" ModuleManager                                                            """
""" ------------------------------------------------------------------------ """
class ModuleManager(IActivator):

    # instance
    Instance = None

    # lock
    InstanceLock = Lock()

    # modules
    Modules = {}

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if ModuleManager.Instance is None:
            ModuleManager.Instance = object.__new__(cls, *p, **k)
        return ModuleManager.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('ModuleManager')

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ startModule                                                          """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def startModule(self, requestHandler, name = None):
        try:

            # start all modules
            if name is None:
                self.logger.info('Starting all Modules...')
                names = Config().get('modules', 'Modules').strip().split(',')
                for name in names:
                    name = name.strip()
                    if Config().get(name, 'enabled').strip() == 'True':
                        try:
                            # check if exists
                            if ModuleManager.Modules.has_key(name):
                                raise Exception, "Module does already exist: %s" % (name)
                            # start
                            ModuleManager.Modules[name] = getClassByName(Config().get(name, 'module').strip(), Config().get(name, 'class').strip())(name)
                            ModuleManager.Modules[name].start(requestHandler, self.onModuleStop)
                        except Exception, e:
                            self.logger.error("failed to start Module %s (%s)" % (name, e))

            # start single module
            else:
                self.logger.info('Starting Module %s...' % name)
                if Config().get(name, 'enabled').strip() == 'True':
                    try:
                        # check if exists
                        if ModuleManager.Modules.has_key(name):
                            raise Exception, "Module does already exist: %s" % (name)
                        # start
                        ModuleManager.Modules[name] = getClassByName(Config().get(name, 'module').strip(), Config().get(name, 'class').strip())(name)
                        ModuleManager.Modules[name].start(requestHandler, self.onModuleStop)
                    except Exception, e:
                        self.logger.error("failed to start Module %s (%s)" % (name, e))

        except Exception, e:
            self.logger.error("Exception in startModule (%s)" % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ stopModule                                                           """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def stopModule(self, name = None):
        try:

            # stop all modules
            if name is None:
                self.logger.info('Stopping all Modules...')
                moduleCopy = ModuleManager.Modules.copy()
                for name, module in moduleCopy.iteritems():
                    try:
                        self.logger.info('Stopping Module %s...' % name)
                        module.stop()
                    except Exception, e:
                        self.logger.error("failed to stop Module %s (%s)" % (name, e))

            # stop single module
            else:
                self.logger.info('Stopping Module %s...' % name)
                try:
                    if not ModuleManager.Modules.has_key(name):
                        raise Exception, "Module does not exist: %s" % (name)
                    self.logger.info('Stopping Module %s...' % (name))
                    ModuleManager.Modules[name].stop()
                except Exception, e:
                    self.logger.error("failed to stop Module %s (%s)" % (name, e))

        except Exception, e:
            self.logger.error("Exception in stopModule (%s)" % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ setRequestHandler                                                    """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def setRequestHandler(self, requestHandler, name = None):

        # all modules
        if name is None:
            for name, module in ModuleManager.Modules.iteritems():
                try:
                    module.setRequestHandler(requestHandler)
                except Exception, e:
                    self.logger.error("failed to set Request-Handler at Module %s (%s)" % (name, e))

        # single module
        else:
            try:
                if not ModuleManager.Modules.has_key(name):
                    raise Exception, "Module does not exist: %s" % (name)
                ModuleManager.Modules[name].setRequestHandler(requestHandler)
            except Exception, e:
                self.logger.error("failed to set Request-Handler at Module %s (%s)" % (name, e))

    """ -------------------------------------------------------------------- """
    """ isModuleRunning                                                      """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def isModuleRunning(self, name = None):

        # all modules
        if name is None:
            for name, module in ModuleManager.Modules.iteritems():
                if module.isRunning():
                    return True
            return False

        # single module
        else:
            if ModuleManager.Modules.has_key(name):
                return ModuleManager.Modules[name].isRunning()
            else:
                return False

    """ -------------------------------------------------------------------- """
    """ runningModules                                                       """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def runningModules(self):
        retVal = []
        try:
            for name in ModuleManager.Modules.iterkeys():
                retVal.append(name)
        except Exception, e:
            self.logger.error("Exception in runningModules (%s)" % (e))
        finally:
            return retVal

    """ -------------------------------------------------------------------- """
    """ checkModules                                                         """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def checkModules(self):
        try:
            for module in ModuleManager.Modules.itervalues():
                if not module.isRunning():
                    return False
            return True
        except Exception, e:
            self.logger.error("Exception in checkModules (%s)" % (e))
            return False

    """ -------------------------------------------------------------------- """
    """ moduleCommand                                                        """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def moduleCommand(self, name, command):
        try:
            if not ModuleManager.Modules.has_key(name):
                raise Exception, "Module does not exist: %s" % (name)
            return ModuleManager.Modules[name].command(command)
        except Exception, e:
            self.logger.error("Exception in moduleCommand (%s)" % (e))
            return None

    """ -------------------------------------------------------------------- """
    """ moduleStatus                                                         """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def moduleStatus(self, name):
        try:
            if not ModuleManager.Modules.has_key(name):
                raise Exception, "Module does not exist: %s" % (name)
            return ModuleManager.Modules[name].status()
        except Exception, e:
            self.logger.error("Exception in moduleStatus (%s)" % (e))
            return None

    """ -------------------------------------------------------------------- """
    """ onModuleStop                                                         """
    """ -------------------------------------------------------------------- """
    def onModuleStop(self, name):
        # log
        self.logger.info("Module stopped: %s" % (name))
        # remove
        try:
            ModuleManager.Modules.__delitem__(name)
        except Exception, e:
            self.logger.error("failed to remove Module %s (%s)" % (name, e))
