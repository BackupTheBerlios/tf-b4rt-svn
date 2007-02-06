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

class StatFile(object):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, file):
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
        if self.file is not '':
            self.initialize(self.file)

    """ -------------------------------------------------------------------- """
    """ initialize                                                           """
    """ -------------------------------------------------------------------- """
    def initialize(self, file):

        # file
        self.file = file

        # read in stat-file + set fields
        if isfile(self.file):
            try:
                # read file to mem
                f = open(self.file, 'r')
                content = f.readlines()
                f.close
                # set fields
                if len(content) > 12:
                    self.running = content[0].replace("\n", "")
                    self.percent_done = content[1].replace("\n", "")
                    self.time_left = content[2].replace("\n", "")
                    self.down_speed = content[3].replace("\n", "")
                    self.up_speed = content[4].replace("\n", "")
                    self.transferowner = content[5].replace("\n", "")
                    self.seeds = content[6].replace("\n", "")
                    self.peers = content[7].replace("\n", "")
                    self.sharing = content[8].replace("\n", "")
                    self.seedlimit = content[9].replace("\n", "")
                    self.uptotal = content[10].replace("\n", "")
                    self.downtotal = content[11].replace("\n", "")
                    self.size = content[12].replace("\n", "")
                    # return
                    return 1
            except:
                print "Failed to read StatFile %s" % self.file
                pass

        # return
        return 0

    """ -------------------------------------------------------------------- """
    """ write                                                                """
    """ -------------------------------------------------------------------- """
    def write(self):

        # write stat-file
        try:
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
        except:
            print "Failed to write StatFile %s" % self.file
            pass

