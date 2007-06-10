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
################################################################################

""" ------------------------------------------------------------------------ """
""" StatFile                                                                 """
""" ------------------------------------------------------------------------ """
class StatFile(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, file = None):

        # file
        self.file = file

        # stat-fields
        self.running = 1
        self.percent_done = "0.0"
        self.time_left = ""
        self.down_speed = ""
        self.up_speed = ""
        self.sharing = ""
        self.transferowner = ""
        self.seeds = ""
        self.peers = ""
        self.seedlimit = ""
        self.uptotal = ""
        self.downtotal = ""
        self.size = ""

        # init
        if self.file is not None and self.file is not '':
            self.initialize(self.file)

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self, file):

        # file
        self.file = file

        # read in stat-file + set fields
        if os.path.isfile(self.file):
            try:

                # read file to mem
                f = open(self.file, 'r')
                data = f.read()
                f.close()

                # set fields
                content = data.split("\n")
                if len(content) > 12:
                    self.running = content[0].strip()
                    self.percent_done = content[1].strip()
                    self.time_left = content[2].strip()
                    self.down_speed = content[3].strip()
                    self.up_speed = content[4].strip()
                    self.transferowner = content[5].strip()
                    self.seeds = content[6].strip()
                    self.peers = content[7].strip()
                    self.sharing = content[8].strip()
                    self.seedlimit = content[9].strip()
                    self.uptotal = content[10].strip()
                    self.downtotal = content[11].strip()
                    self.size = content[12].strip()
                else:
                    raise Exception, "StatFile::initialize: Failed to parse file %s (%s)" % (self.file, e)

            except:
                raise Exception, "StatFile::initialize: Failed to read file %s (%s)" % (self.file, e)

        else:
            raise Exception, "StatFile::initialize: File does not exist: %s" % (self.file)

    """ -------------------------------------------------------------------- """
    """ write                                                                """
    """ -------------------------------------------------------------------- """
    def write(self):

        # write stat-file
        try:
            # 1
            """
            f = open(self.file, 'w')
            f.write(str(self.running) + '\n')
            f.write(str(self.percent_done) + '\n')
            f.write(str(self.time_left) + '\n')
            f.write(str(self.down_speed) + '\n')
            f.write(str(self.up_speed) + '\n')
            f.write(str(self.transferowner) + '\n')
            f.write(str(self.seeds) + '\n')
            f.write(str(self.peers) + '\n')
            f.write(str(self.sharing) + '\n')
            f.write(str(self.seedlimit) + '\n')
            f.write(str(self.uptotal) + '\n')
            f.write(str(self.downtotal) + '\n')
            f.write(str(self.size))
            f.flush()
            f.close()
            """
            # 2
            """
            content = ''
            content += str(self.running) + '\n'
            content += str(self.percent_done) + '\n'
            content += str(self.time_left) + '\n'
            content += str(self.down_speed) + '\n'
            content += str(self.up_speed) + '\n'
            content += str(self.transferowner) + '\n'
            content += str(self.seeds) + '\n'
            content += str(self.peers) + '\n'
            content += str(self.sharing) + '\n'
            content += str(self.seedlimit) + '\n'
            content += str(self.uptotal) + '\n'
            content += str(self.downtotal) + '\n'
            content += str(self.size)
            f = open(self.file, 'w')
            f.write(content)
            f.flush()
            f.close()
            """
            # 3
            f = open(self.file, 'w')
            f.write( \
                '%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s' % \
                ( \
                    str(self.running), \
                    str(self.percent_done), \
                    str(self.time_left), \
                    str(self.down_speed), \
                    str(self.up_speed), \
                    str(self.transferowner), \
                    str(self.seeds), \
                    str(self.peers), \
                    str(self.sharing), \
                    str(self.seedlimit), \
                    str(self.uptotal), \
                    str(self.downtotal), \
                    str(self.size) \
                ) \
            )
            f.flush()
            f.close()
        except:
            raise Exception, "StatFile::write: Failed to write file %s (%s)" % (self.file, e)
