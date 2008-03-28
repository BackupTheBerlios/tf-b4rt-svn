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
# fluxd-imports
from fluxd.interfaces.IDataAdapter import IDataAdapter
from fluxd.activator.Activator import Activator
################################################################################

""" ------------------------------------------------------------------------ """
""" DataAdapterFluxcli                                                       """
""" ------------------------------------------------------------------------ """
class DataAdapterFluxcli(IDataAdapter):

    DELIM_SETTINGS = '*'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('DataAdapterFluxcli')

    """ -------------------------------------------------------------------- """
    """ loadSettings                                                         """
    """ -------------------------------------------------------------------- """
    def loadSettings(self):
    
        # info
        self.logger.info('loading settings...')

        # load settings via fluxcli
        try:
            # get Fluxcli instance
            fluxcli = Activator().getInstance('Fluxcli')
            # invoke
            result = fluxcli.invoke(['dump', 'settings'], True).strip()
            # process
            settings = result.split('\n')
            # check
            if len(settings) < 1:
                raise Exception, 'settings-validation failed'
            retVal = {}
            for settingPair in settings:
                setting = settingPair.strip().split(DataAdapterFluxcli.DELIM_SETTINGS)
                if len(setting) == 2:
                    retVal[setting[0]] = setting[1]
            # check
            if len(retVal) < 1:
                raise Exception, 'settings-validation failed'
            # return
            return retVal
        except Exception, e:
            self.logger.error('failed to load Settings (%s)' % (e))
            raise e

    """ -------------------------------------------------------------------- """
    """ saveSettings                                                         """
    """ -------------------------------------------------------------------- """
    def saveSettings(self, settings = {}):
        raise Exception, 'Read-Only DataAdapter'
