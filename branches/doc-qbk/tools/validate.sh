#!/bin/sh
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
# Torrentflux-b4rt doc helper script: validate QuickBook sources.
################################################################################


if [ "$1" = "" ] ; then
  sourcedir='src'
else
  sourcedir="$1"
fi


# Check all QuickBook [section...] elements have an explicit id.

grep -F '[section ' "$sourcedir"/* || exit 0

echo 'ERROR: all [section...] elements must have an explicit id'
exit 1
