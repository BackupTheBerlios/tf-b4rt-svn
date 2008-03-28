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
""" getDatabaseConfig                                                        """
""" ------------------------------------------------------------------------ """
def getDatabaseConfig():

    # imports
    from fluxd.Config import Config
    
    # return
    return getDatabaseConfigFromFile('%sinc/config/config.db.php' % Config().get('dir', 'docroot').strip())

""" ------------------------------------------------------------------------ """
""" getDatabaseConfigFromFile                                                """
""" ------------------------------------------------------------------------ """
def getDatabaseConfigFromFile(file):

    # imports
    import os
    import re

    # check file
    if not os.path.isfile(file):
        raise Exception, 'database-config-file does not exist: %s' % file
        
    # read file to mem
    f = open(file, 'r')
    data = f.read()
    f.close()

    # retval
    ret = {}

    # keys
    keys = ['db_type', 'db_host', 'db_name', 'db_user', 'db_pass']

    # parse and fill
    for key in keys:

        # regexp
        rex = '%s.*[^\[]\"(.*)\"[^\]]' % key

        # search
        m = re.search(rex, data)
        
        # add to ret
        if m is not None and hasattr(m, 'group'):
            ret[key] = m.group(1)
        else:
            ret[key] = ''

    # pcon
    ret['db_pcon'] = False
    
    # return
    return ret
