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
from fluxd.interfaces.IDataAdapter import IDataAdapter
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if DatabaseManager.Instance is None:
        raise Exception, 'DatabaseManager not initialized'
    return DatabaseManager.Instance

""" ------------------------------------------------------------------------ """
""" DatabaseManager                                                          """
""" ------------------------------------------------------------------------ """
class DatabaseManager(IActivator):

    # instance
    Instance = None

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if DatabaseManager.Instance is None:
            DatabaseManager.Instance = object.__new__(cls, *p, **k)
        return DatabaseManager.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('DatabaseManager')

        # settings
        self.settings = {}

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ getSetting                                                           """
    """ -------------------------------------------------------------------- """
    def getSetting(self, key):
        try:
            return self.settings[key]
        except Exception, e:
            self.logger.error("Exception in getSetting: %s" % (e))
            raise Exception, "Failed to get Setting: %s" % (key)

    """ -------------------------------------------------------------------- """
    """ getSettings                                                          """
    """ -------------------------------------------------------------------- """
    def getSettings(self):
        try:
            return self.settings.copy()
        except Exception, e:
            self.logger.error("Exception in getSettings: %s" % (e))
            raise Exception, "Failed to get Settings"

    """ -------------------------------------------------------------------- """
    """ getSettingsCount                                                     """
    """ -------------------------------------------------------------------- """
    def getSettingsCount(self):
        try:
            return len(self.settings)
        except Exception, e:
            self.logger.error("Exception in getSettingsCount: %s" % (e))
            raise Exception, "Failed to get SettingsCount"

    """ -------------------------------------------------------------------- """
    """ setSetting                                                           """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def setSetting(self, key, value):
        try:
            self.settings[key] = value
        except Exception, e:
            self.logger.error("Exception in setSetting: %s" % (e))
            raise Exception, "Failed to set Setting: %s" % (key)

    """ -------------------------------------------------------------------- """
    """ load                                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def load(self):
        # adapter
        adapter = Config().get('database', 'DataAdapter').strip()
        # log
        self.logger.info('loading data with Adapter %s ...' % adapter)
        try:
            # get data-adapter
            dataAdapter = self.getDataAdapter(adapter)
            # load settings
            self.settings = dataAdapter.loadSettings()
            # log
            self.logger.info('settings loaded (%d)' % (len(self.settings)))
        except Exception, e:
            self.logger.error("Failed to load data with Adapter %s: %s" % (adapter, e))
            raise Exception, "DatabaseManager failed to load data"

    """ -------------------------------------------------------------------- """
    """ save                                                                 """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def save(self):
        # adapter
        adapter = Config().get('database', 'DataAdapter').strip()
        # log
        self.logger.info('saving data with Adapter %s ...' % adapter)
        try:
            # get data-adapter
            dataAdapter = self.getDataAdapter(adapter)
            # save settings
            dataAdapter.saveSettings(self.settings)
        except Exception, e:
            self.logger.error("Failed to save data (%s): %s" % (adapter, e))
            raise Exception, "DatabaseManager failed to save data"

    """ -------------------------------------------------------------------- """
    """ getDataAdapter                                                       """
    """ -------------------------------------------------------------------- """
    def getDataAdapter(self, adapterClass):
        return getClassByName(
            '%s.%s' % (Config().get('database', 'DataAdapterPackage').strip(), adapterClass),
            adapterClass)()
        #return getClassByName(Config().get('database', 'DataAdapterPackage').strip() + '.' + adapterClass, adapterClass)()
