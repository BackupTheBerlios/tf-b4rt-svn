#!/usr/bin/perl
################################################################################
# $Id$
# $Revision$
# $Date$
# $Author$
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
use Net::BitTorrent::File;
use strict;
################################################################################

# Internal Vars
my ($VERSION, $DIR, $PROG, $EXTENSION, $USAGE);

#-------------------------------------------------------------------------------
# Main
#-------------------------------------------------------------------------------

# init some vars
$VERSION =
	do { my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
($DIR=$0) =~ s/([^\/\\]*)$//;
($PROG=$1) =~ s/\.([^\.]*)$//;
$EXTENSION=$1;

# main-"switch"
SWITCH: {
	$_ = shift @ARGV;
	/.*(help|-h).*/ && do { # --- help ---
		printUsage();
		exit;
	};
	# decode torrent
	decodeTorrent($_);
	exit;
}

#===============================================================================
# Subs
#===============================================================================

#-------------------------------------------------------------------------------
# Sub: decodeTorrent
# Parameters: string with path to torrent-meta-file
# Return: -
#-------------------------------------------------------------------------------
sub decodeTorrent {
	my $torrentFile = shift;
	if (!(defined $torrentFile)) {
		printUsage();
		exit;
	}
	if (!(-f $torrentFile)) {
		print "Error : ".$torrentFile." is no file\n";
		exit;
	}
	my $torrent = new Net::BitTorrent::File($torrentFile);
	if (!(defined $torrent)) {
		print "Error loading torrent-meta-file ".$torrentFile."\n";
		exit;
	}
	# hash
	my $hash = $torrent->info_hash();
	$hash =~ s/(.)/sprintf("%02x",ord($1))/egs;
	print "hash : ".lc($hash)."\n";
	# name
	print "name : ".$torrent->name()."\n";
	# announce
	print "announce : ".$torrent->announce()."\n";
	# files + size
	my $info = $torrent->info();
	my $torrentSize = 0;
	print "file(s) : \n";
	if (defined($info->{'files'})) {
		foreach my $fileEntry (@{$info->{'files'}}) {
			$torrentSize += $fileEntry->{'length'};
			if (ref($fileEntry->{'path'}) eq 'ARRAY') {
				print " ".$info->{'name'}.'/'.$fileEntry->{'path'}->[0]." (".$fileEntry->{'length'}.")\n";
			} else {
				print " ".$info->{'name'}.'/'.$fileEntry->{'path'}." (".$fileEntry->{'length'}.")\n";
			}
		}
	} else {
		$torrentSize += $info->{'length'},
		print " ".$info->{'name'}." (".$info->{'length'}.")\n";
	}
	print "size : ".$torrentSize."\n";
}

#-------------------------------------------------------------------------------
# Sub: printUsage
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printUsage {
	print <<"USAGE";

$PROG.$EXTENSION (Revision $VERSION)

Usage: $PROG.$EXTENSION path-to-torrent-meta-file

Example:

$PROG.$EXTENSION /foo/bar.torrent


USAGE

}

# EOF
