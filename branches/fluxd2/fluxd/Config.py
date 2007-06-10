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
import ConfigParser
from threading import Lock
# fluxd-imports
from fluxd.defaultConfig import defaultConfig
from fluxd.decorators.synchronized import synchronized
################################################################################

""" ------------------------------------------------------------------------ """
""" Config                                                                   """
""" ------------------------------------------------------------------------ """
class Config(object):

    # lock
    InstanceLock = Lock()

    # config-parser
    __ConfigParser = ConfigParser.ConfigParser()
    setattr(__ConfigParser, 'optionxform' , str)

    # shared state
    _state = {}

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        self = object.__new__(cls, *p, **k)
        self.__dict__ = cls._state
        return self

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):
        pass

    """ -------------------------------------------------------------------- """
    """ get                                                                  """
    """ -------------------------------------------------------------------- """
    def get(self, section, option):
        try:
            return Config.__ConfigParser.get(section, option)
        except Exception, e:
            raise Exception, "Failed to get Config: %s.%s (%s)" % (section, option, e)

    """ -------------------------------------------------------------------- """
    """ set                                                                  """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def set(self, section, option, value):
        try:
            if not Config.__ConfigParser.has_section(section):
                Config.__ConfigParser.add_section(section)
            Config.__ConfigParser.set(section, option, value)
        except Exception, e:
            raise Exception, "Failed to set Config: %s.%s (%s)" % (section, option, e)

    """ -------------------------------------------------------------------- """
    """ setConfig                                                            """
    """ -------------------------------------------------------------------- """
    def setConfig(self, configParser):
        for section in configParser.sections():
            sec = section.strip()
            for option in configParser.options(sec):
                opt = option.strip()
                self.set(sec, opt, configParser.get(sec, opt).strip())

    """ -------------------------------------------------------------------- """
    """ loadConfig                                                           """
    """ -------------------------------------------------------------------- """
    def loadConfig(self, file):
        configParser = ConfigParser.ConfigParser()
        setattr(configParser, 'optionxform' , str)
        configParser.read(file)
        return configParser

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self, args = []):

        # process args
        configParser = ConfigParser.ConfigParser()
        setattr(configParser, 'optionxform' , str)
        if len(args) > 2:
            for arg in args[2:]:
                configPair = arg.strip().split('=')
                if len(configPair) == 2:
                    key = configPair[0].strip()
                    val = configPair[1].strip()
                    if len(key) > 2 and key[0] == '-' and key[1] == '-':
                        key = key[2:]
                    keyPair = key.strip().split('.')
                    if len(keyPair) == 2:
                        section = keyPair[0].strip()
                        option = keyPair[1].strip()
                        if not configParser.has_section(section):
                            configParser.add_section(section)
                        configParser.set(section, option, val)
                    else:
                        print "no key-pair: %s" % (keyPair)
                else:
                    print "no config-pair: %s" % (configPair)

        # check op
        if len(args[1]) > 0 and args[1][0] == '_':
            raise Exception, "Error: illegal op: %s" % args[1]

        # default
        self.setConfig(defaultConfig())

        # load config
        if configParser.has_option('file', 'cfg'):
            self.setConfig(self.loadConfig(configParser.get('file', 'cfg')))

        # override with arg-conf
        self.setConfig(configParser)

        # check config
        self.checkConfig()

    """ -------------------------------------------------------------------- """
    """ defaultConfigAsIniString                                             """
    """ -------------------------------------------------------------------- """
    def defaultConfigAsIniString(self):
        return self.configAsIniString(defaultConfig())

    """ -------------------------------------------------------------------- """
    """ currentConfigAsIniString                                             """
    """ -------------------------------------------------------------------- """
    def currentConfigAsIniString(self):
        return self.configAsIniString(Config.__ConfigParser)

    """ -------------------------------------------------------------------- """
    """ defaultConfigAsArgString                                             """
    """ -------------------------------------------------------------------- """
    def defaultConfigAsArgString(self):
        return self.configAsArgString(defaultConfig())

    """ -------------------------------------------------------------------- """
    """ currentConfigAsArgString                                             """
    """ -------------------------------------------------------------------- """
    def currentConfigAsArgString(self):
        return self.configAsArgString(Config.__ConfigParser)

    """ -------------------------------------------------------------------- """
    """ configAsArgString                                                    """
    """ -------------------------------------------------------------------- """
    def configAsArgString(self, configParser):
        retVal = ''
        for section in configParser.sections():
            sec = section.strip()
            for option in configParser.options(sec):
                opt = option.strip()
                val = configParser.get(sec, opt).strip()
                retVal += "%s.%s='%s'\n" % (sec, opt, val)
        return retVal

    """ -------------------------------------------------------------------- """
    """ configAsIniString                                                    """
    """ -------------------------------------------------------------------- """
    def configAsIniString(self, configParser):
        retVal = ''
        for section in configParser.sections():
            sec = section.strip()
            retVal += '\n[%s]\n' % sec
            for option in configParser.options(sec):
                opt = option.strip()
                val = configParser.get(sec, opt).strip()
                retVal += '%s = %s\n' % (opt, val)
        return retVal

    """ -------------------------------------------------------------------- """
    """ checkConfig                                                          """
    """ -------------------------------------------------------------------- """
    def checkConfig(self):

        # required tf-path: docroot
        try:
            docroot = self.get('dir', 'docroot').strip()
            if not os.path.isdir(docroot):
                raise Exception, "tf-dir docroot does not exist: %s" % docroot
        except Exception, e:
            raise Exception, "fatal error when checking tf-dir docroot: %s" % e

        # required tf-path: path
        try:
            pathTf = self.get('dir', 'pathTf').strip()
            if not os.path.isdir(pathTf):
                raise Exception, "tf-dir path does not exist: %s" % pathTf
        except Exception, e:
            raise Exception, "fatal error when checking tf-dir path: %s" % e

        # required fluxd-path: path
        try:
            pathFluxd = self.get('dir', 'pathFluxd').strip()
            if not os.path.isdir(pathFluxd):
                try:
                    os.mkdir(pathFluxd, 0700)
                except Exception, e:
                    raise Exception, "fluxd-dir path does not exist and cannot be created: %s (%s)" % (pathFluxd, e)
        except Exception, e:
            raise Exception, "fatal error when checking fluxd-dir path: %s" % e

        # generic dir-check
        for option in Config.__ConfigParser.options('dir'):
            opt = option.strip()
            val = self.get('dir', opt).strip()
            # ensure we got trailing slashes on dirs
            if val[len(val) - 1] != '/':
                val += '/'
                self.set('dir', opt, val)
            # check if dir exists
            if not os.path.isdir(val):
                raise Exception, "Invalid Dir-Config: %s: %s" % (opt, val)

        # generic file-check (with exceptions ;))
        for option in Config.__ConfigParser.options('file'):
            opt = option.strip()
            if opt != 'cfg' and opt != 'pid' and opt != 'log':
                val = self.get('file', opt).strip()
                # check if file exists
                if not os.path.isfile(val):
                    raise Exception, "Invalid File-Config: %s: %s" % (opt, val)
