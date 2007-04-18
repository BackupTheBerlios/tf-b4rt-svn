#! /bin/sh
#
# $Id$

MAJOR=0
MINOR=7
MAINT=0
STRING=0.80-svn

# get transmission-revision from transmission.revision
if [ -f transmission.revision ]; then
	REV_TR=`cat transmission.revision`
else
	REV_TR=0
fi

# get cli-revision from svn-ids in files in cli-dir
REV_CLI=`( find cli '(' -name '*.[chm1]' -o -name '*.cpp' -o -name '*.po' \
            -o -name '*.mk' -o -name '*.in' -o -name 'Makefile' \
            -o -name 'configure' ')' -exec cat '{}' ';' ) | \
          sed -e '/\$Id:/!d' -e \
            's/.*\$Id: [^ ]* \([0-9]*\) .*/\1/' |
          awk 'BEGIN { REV_CLI=0 }
               //    { if ( $1 > REV_CLI ) REV_CLI=$1 }
               END   { print REV_CLI }'`

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
VERSION_MAJOR       = $MAJOR
VERSION_MINOR       = $MINOR
VERSION_MAINTENANCE = $MAINT
VERSION_STRING      = $STRING
VERSION_REVISION    = $REV_TR
VERSION_REVISION_CLI = $REV_CLI
EOF
replace_if_differs mk/version.mk.new mk/version.mk

# Generate version.h
cat > libtransmission/version.h.new << EOF
#define VERSION_MAJOR       $MAJOR
#define VERSION_MINOR       $MINOR
#define VERSION_MAINTENANCE $MAINT
#define VERSION_STRING      "$STRING"
#define VERSION_REVISION    $REV_TR
#define VERSION_REVISION_CLI $REV_CLI
EOF
replace_if_differs libtransmission/version.h.new libtransmission/version.h

exit 0
