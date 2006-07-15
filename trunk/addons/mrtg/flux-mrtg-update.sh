#!/bin/sh                                                           
################################################################################
# $Id$                                            
# $Revision$                                                             
# $Date$                                                 
# $Author$                                                            
#------------------------------------------------------------------------------#
# flux-mrtg-update.sh                                                          #
#------------------------------------------------------------------------------#
# This Stuff is provided 'as-is'. In no way will the author be held            #
# liable for any damages to your soft- or hardware from this.                  #
# Feel free to change or rip the code.                                         #
################################################################################

# defaults
FLUXPATH="/usr/local/torrent"
CONFFILE="/etc/mrtg/mrtg.flux.cfg"
BIN_MRTG="/usr/bin/mrtg"
DEFAULT_CONFFILE="/etc/mrtg/flux-mrtg.conf"

# load conf-file
if [ "$1X" != "X" ] ; then
  if [ -e "$1" ] ; then
    . $1
  fi
else
  if [ -e "$DEFAULT_CONFFILE" ] ; then
    . $DEFAULT_CONFFILE
  fi
fi

# check for mrtg-bin
if [ ! -x "$BIN_MRTG" ] ; then
  BIN_MRTG=`whereis mrtg | awk '{print $2}'`
  if [ ! -x "$BIN_MRTG" ] ; then
    echo "error: cant find mrtg"
    exit
  fi
fi

# check for mrtg-directory, create if missing.
if [ ! -d "$FLUXPATH/.mrtg" ] ; then
  mkdir -p $FLUXPATH/.mrtg
fi

# invoke mrtg for flux
$BIN_MRTG $CONFFILE | tee -a $FLUXPATH/.mrtg/mrtg.log

