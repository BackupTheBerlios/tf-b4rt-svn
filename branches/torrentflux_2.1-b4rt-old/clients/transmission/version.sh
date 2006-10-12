#! /bin/sh
#
# $Id$

# get transmission-revision from transmission.revision
REV_TR=`cat transmission.revision`

# get cli-revision from id in transmissioncli.c
REV_CLI=`sed -e '/\$Id:/!d' -e 's/.*\$Id: [^ ]* \([0-9]*\) .*/\1/' cli/transmissioncli.c`

# Generate files to be included: only overwrite them if changed so make
# won't rebuild everything unless necessary
replace_if_differs ()
{
    if cmp $1 $2 > /dev/null 2>&1; then
      rm -f $1
    else
      mv -f $1 $2
    fi
}

# Generate version.mk
cp -f mk/version.mk.in mk/version.mk.new
echo "VERSION_REVISION = $REV_TR" >> mk/version.mk.new
echo "VERSION_REVISION_CLI = $REV_CLI" >> mk/version.mk.new 
replace_if_differs mk/version.mk.new mk/version.mk

# Generate version.h from version.mk
grep "^VER" mk/version.mk | sed -e 's/^/#define /g' -e 's/= //g' \
    -e 's/\(VERSION_STRING[ ]*\)\(.*\)/\1"\2"/' > \
    libtransmission/version.h.new
replace_if_differs libtransmission/version.h.new libtransmission/version.h

exit 0
