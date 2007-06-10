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
from fluxd.functions.generic import getModuleByName, getClassByName
################################################################################

""" ------------------------------------------------------------------------ """
""" Activator                                                                """
""" ------------------------------------------------------------------------ """
class Activator(object):

    # objects
    Objects = {}

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if not '_the_instance' in cls.__dict__:
            cls._the_instance = object.__new__(cls, *p, **k)
        return cls._the_instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):
        pass

    """ -------------------------------------------------------------------- """
    """ getInstance                                                          """
    """ -------------------------------------------------------------------- """
    def getInstance(self, objectId):
        if Activator.Objects.has_key(objectId):
            return Activator.Objects[objectId]()
        else:
            raise Exception, "Invalid Object-ID: %s" % (objectId)

    """ -------------------------------------------------------------------- """
    """ registerInstance                                                     """
    """ -------------------------------------------------------------------- """
    def registerInstance(self, moduleName, className, objectId, *p, **k):

        # get module
        if Activator.Objects.has_key(objectId):
            raise Exception, "Object-ID already registered: %s" % (objectId)
        else:
            mod = getModuleByName(moduleName)
            if (hasattr(mod, "GetInstance")):
                Activator.Objects[objectId] = mod.GetInstance
            else:
                raise Exception, "Invalid Class: %s/%s" % (moduleName, className)

        # get class
        return getClassByName(moduleName, className)(objectId, *p, **k)
