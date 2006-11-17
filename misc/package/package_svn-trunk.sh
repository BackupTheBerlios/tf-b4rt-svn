#!/bin/sh
################################################################################
# $Id$
# $Revision$
# $Date$
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

# svn-url
SVN_URL="http://svn.berlios.de/svnroot/repos/tf-b4rt/trunk"

# main-dir
MAINDIR="trunk"

# version-string
VERSION="svn-trunk"
#VERSION="1.0-alpha3"

# tarball-name
TARNAME="torrentflux-b4rt_svn-trunk"
#TARNAME="torrentflux-b4rt_1.0-alpha3"

# use svn-revision
USE_SVNREV="1"
#USE_SVNREV="0"

#------------------------------------------------------------------------------#

# some bins
BIN_SVN="/usr/local/bin/svn"
BIN_FROMDOS="/usr/bin/fromdos"
BIN_XARGS="/usr/bin/xargs"
BIN_TAR="/bin/tar"
BIN_MDSUM="/usr/bin/md5sum"

#------------------------------------------------------------------------------#

# file-endings
ENDINGS="php
		dist
        pl
		pm
		sh
		cfg
		tmpl
		html
		js
		css
		xml
		dtd
		xsd
		sql
		py"


###############################################################################

# DEBUG
#echo SVN_URL : $SVN_URL
#echo MAINDIR : $MAINDIR
#echo VERSION : $VERSION

# export from svn
$BIN_SVN export --quiet --non-interactive $SVN_URL

# Get current SVN revision of tfb from Ids in all files
REV_TFB=`( find $MAINDIR '(' -name '*.[chm]' -o -name '*.php' -o -name '*.dist' \
            -o -name '*.pl' -o -name '*.pm' -o -name '*.txt' \
			-o -name '*.cfg' -o -name '*.tmpl' -o -name '*.html' \
			-o -name '*.js' -o -name '*.css' -o -name '*.xml' \
			-o -name '*.dtd' -o -name '*.xsd' -o -name '*.sql' \
			-o -name '*.py' -o -name 'INSTALL' -o -name 'CHANGES' \
            -o -name 'flux-mrtg-update.sh' ')' -exec cat '{}' ';' ) | \
          sed -e '/\$Id:/!d' -e \
            's/.*\$Id: [^ ]* \([0-9]*\) .*/\1/' |
          awk 'BEGIN { REV_TFB=0 }
               //    { if ( $1 > REV_TFB ) REV_TFB=$1 }
               END   { print REV_TFB }'`

# set tarname
if [ "$USE_SVNREV" == "1" ] ; then
	TARNAME=$TARNAME"-"$REV_TFB
fi

# get transmission-revision from transmission.revision
if [ -f $MAINDIR/clients/transmission/transmission.revision ]; then
	REV_TR=`cat $MAINDIR/clients/transmission/transmission.revision`
else
	REV_TR=0
fi

# get cli-revision from id in transmissioncli.c
REV_CLI=`sed -e '/\$Id:/!d' -e 's/.*\$Id$MAINDIR/clients/transmission/cli/transmissioncli.c`

# DEBUG
#echo REV_TFB : $REV_TFB
#echo REV_TR  : $REV_TR
#echo REV_CLI : $REV_CLI
#echo $TARNAME

# write new version-file
VERSION_STRING=$VERSION
if [ "$USE_SVNREV" == "1" ] ; then
	VERSION_STRING=$VERSION-$REV_TFB
fi
echo -n '<?php define("_VERSION", "'$VERSION_STRING'"); ?>' > $MAINDIR/html/version.php

# line-endings
for ENDING in $ENDINGS
do
	find $MAINDIR -name "*.$ENDING" | $BIN_XARGS $BIN_FROMDOS -a -d -o -p
done

# some execs
chmod +x $MAINDIR/clients/transmission/version.sh
chmod +x $MAINDIR/clients/transmission/configure

# rename dir
mv $MAINDIR $TARNAME

# create tar
$BIN_TAR jcf $TARNAME.tar.bz2 $TARNAME
# md5-sum
$BIN_MDSUM -b $TARNAME.tar.bz2 > $TARNAME.tar.bz2.md5

# delete dir
if [ -d "$TARNAME" ] ; then
	rm -R -f ./$TARNAME
fi

