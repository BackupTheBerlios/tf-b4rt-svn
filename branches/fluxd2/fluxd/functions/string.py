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
""" isTrue                                                                   """
""" ------------------------------------------------------------------------ """
def isTrue(arg):
    return arg.lower() in ('true', 'yes', '1')

""" ------------------------------------------------------------------------ """
""" isFalse                                                                  """
""" ------------------------------------------------------------------------ """
def isFalse(arg):
    return arg.lower() in ('false', 'no', '0', '')

""" ------------------------------------------------------------------------ """
""" parseInt                                                                 """
""" ------------------------------------------------------------------------ """
def parseInt(val, defval = 0):
    if val: return int(val)
    else:   return defval

""" ------------------------------------------------------------------------ """
""" parseLong                                                                """
""" ------------------------------------------------------------------------ """
def parseLong(val, defval = 0L):
    if val: return long(val)
    else:   return defval

""" ------------------------------------------------------------------------ """
""" parseFloat                                                               """
""" ------------------------------------------------------------------------ """
def parseFloat(val, defval = 0.):
    if val: return float(val)
    else:   return defval
