#! /bin/sh
#
# $Id$

MAJOR=0
MINOR=6
STRING=0.7-svn

# get transmission-revision from transmission.revision
if [ -f transmission.revision ]; then
	REV_TR=`cat transmission.revision`
else
	REV_TR=0
fi

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

# print out found revisions
echo "Transmission : $REV_TR"
echo "CLI : $REV_CLI"

# Generate version.mk
cat > mk/version.mk.new << EOF
VERSION_MAJOR    = $MAJOR
VERSION_MINOR    = $MINOR
VERSION_STRING   = $STRING
VERSION_REVISION = $REV_TR
VERSION_REVISION_CLI = $REV_CLI
EOF
replace_if_differs mk/version.mk.new mk/version.mk

# Generate version.h
cat > libtransmission/version.h.new << EOF
#define VERSION_MAJOR    $MAJOR
#define VERSION_MINOR    $MINOR
#define VERSION_STRING   "$STRING"
#define VERSION_REVISION $REV_TR
#define VERSION_REVISION_CLI $REV_CLI
EOF
replace_if_differs libtransmission/version.h.new libtransmission/version.h


exit 0
