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
import logging
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IActivator import IActivator
from fluxd.interfaces.ILoggerFactory import ILoggerFactory
################################################################################

""" ------------------------------------------------------------------------ """
""" GetInstance                                                              """
""" ------------------------------------------------------------------------ """
def GetInstance():
    if LoggerFactoryFile.Instance is None:
        raise Exception, 'LoggerFactoryFile not initialized'
    return LoggerFactoryFile.Instance

""" ------------------------------------------------------------------------ """
""" LoggerFactoryFile                                                        """
""" ------------------------------------------------------------------------ """
class LoggerFactoryFile(ILoggerFactory, IActivator):

    # instance
    Instance = None

    # loglevel-map
    LOGLEVEL_MAP = {
        'CRITICAL': logging.CRITICAL,
        'ERROR': logging.ERROR,
        'WARNING': logging.WARNING,
        'INFO': logging.INFO,
        'DEBUG': logging.DEBUG,
        'NOTSET': logging.NOTSET
    }

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if LoggerFactoryFile.Instance is None:
            LoggerFactoryFile.Instance = object.__new__(cls, *p, **k)
        return LoggerFactoryFile.Instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name):

        # set name
        self.__name = name

        # loglevel
        lvl = Config().get('logging', 'Level')
        if LoggerFactoryFile.LOGLEVEL_MAP.has_key(lvl):
            self.__loglevel = lvl
        else:
            self.__loglevel = 'NOTSET'

        # dateformat
        self.__datefmt = Config().get('logging', 'Dateformat')

        # log-file
        self.__logfile = Config().get('file', 'log').strip()

        # file-logger
        logging.basicConfig(
            level=LoggerFactoryFile.LOGLEVEL_MAP[self.__loglevel],
            format='[%(asctime)s][%(name)-20s][%(levelname)-8s] %(message)s',
            datefmt=self.__datefmt,
            filename=self.__logfile,
            filemode='a')

    """ -------------------------------------------------------------------- """
    """ getName                                                              """
    """ -------------------------------------------------------------------- """
    def getName(self):
        return self.__name

    """ -------------------------------------------------------------------- """
    """ getLogger                                                            """
    """ -------------------------------------------------------------------- """
    def getLogger(self, name):
        return logging.getLogger(name)
