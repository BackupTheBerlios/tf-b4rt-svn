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
SVN_URL="http://svn.berlios.de/svnroot/repos/tf-b4rt/branches/torrentflux_2.1-b4rt"

# main-dir
MAINDIR="torrentflux_2.1-b4rt"

# version-string
VERSION="svn-2.1-b4rt"

# tarball-name
TARNAME="torrentflux_2.1-b4rt-svn"

# use svn-revision
USE_SVNREV="1"

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


################################################################################

# DEBUG
#echo SVN_URL : $SVN_URL
#echo MAINDIR : $MAINDIR
#echo VERSION : $VERSION

# export from svn
$BIN_SVN export --quiet --non-interactive $SVN_URL

# Get current SVN revision of tfb from Ids in all files
REV_TFB=`( find $MAINDIR '(' -name 'CHANGES' \
			-o -name 'INSTALL' \
			-o -name 'transmissioncli.c' \
			-o -name 'flux-mrtg-update.sh' \
			-o -name '*.php' -o -name '*.dist' \
			-o -name '*.pl' -o -name '*.pm' \
			-o -name '*.tmpl' -o -name '*.html' \
			-o -name '*.css' -o -name '*.js' \
			-o -name '*.sql' -o -name '*.cfg' \
			-o -name '*.xml' -o -name '*.xsd' \
			-o -name '*.py' \
            ')' -exec cat '{}' ';' ) | \
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
REV_CLI=`sed -e '/\$Id:/!d' -e \
	's/.*\$Id: [^ ]* \([0-9]*\) .*/\1/' \
	$MAINDIR/clients/transmission/cli/transmissioncli.c`

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
echo -n $VERSION_STRING > $MAINDIR/html/version

# line-endings
for ENDING in $ENDINGS
do
	find $MAINDIR -name "*.$ENDING" | $BIN_XARGS $BIN_FROMDOS -a -d -o -p
done

# some execs
find $MAINDIR -name "*.sh" | $BIN_XARGS chmod +x
find $MAINDIR -name "*.pl" | $BIN_XARGS chmod +x
chmod +x $MAINDIR/html/fluxcli.php
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

