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
package Watch;
use strict;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: Null                                                              #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {
	my $self = {};
	bless $self;
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub destroy {
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: getVersion                                                              #
# Arguments: null                                                              #
# Returns: VERSION                                                             #
#------------------------------------------------------------------------------#
sub getVersion {
	return $VERSION;
}

#------------------------------------------------------------------------------#
# Sub: set                                                                     #
# Arguments: Variable [value]                                                  #
# Returns: Info string                                                         #
#------------------------------------------------------------------------------#
sub set {
}

#------------------------------------------------------------------------------#
# Sub: main                                                                    #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub main {
}


################################################################################
# make perl happy                                                              #
################################################################################
1;
