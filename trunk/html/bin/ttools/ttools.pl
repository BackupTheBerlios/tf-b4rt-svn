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
use Convert::Bencode;
use Net::BitTorrent::File;
use LWP::UserAgent;
use strict;
################################################################################

# timeout for url-get
my $TIMEOUT = 5;

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
	/info|.*-i/ && do { # --- info ---
		torrentInfo(shift @ARGV);
		exit;
	};
	/scrape|.*-s/ && do { # --- scrape ---
		torrentScrape(shift @ARGV);
		exit;
	};
	/.*(help|-h).*/ && do { # --- help ---
		printUsage();
		exit;
	};
	printUsage();
	exit;
}

#===============================================================================
# Subs
#===============================================================================

#-------------------------------------------------------------------------------
# Sub: torrentInfo
# Parameters: string with path to torrent-meta-file
# Return: -
#-------------------------------------------------------------------------------
sub torrentInfo {
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
# Sub: torrentScrape
# Parameters: string with path to torrent-meta-file
# Return: -
#-------------------------------------------------------------------------------
sub torrentScrape {
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
	# get hash
	my $hash = $torrent->info_hash();
	$hash =~ s/(.)/sprintf("%02x",ord($1))/egs;
	$hash = lc($hash);
	# get scrape url
	my $scrapeUrl;
	if ((index($torrent->announce(), "/announce")) > 0) {
		$scrapeUrl = $torrent->announce();
		$scrapeUrl =~ s#/announce#/scrape#ig;
	} else {
		print "Error : could not get scrape-url\n";
		exit;
	}
	# get scrape info
	my $res = getUrl($scrapeUrl);
	if (!($res->is_success)) {
		print "Error : could not fetch scrape-infos : ".$res->status_line()."\n";
		exit;
	}
	# decode data
	my $info;
	eval {
		$info = Convert::Bencode::bdecode($res->content);
	};
	if ($@) {
		print "Error : cant decode info-data : ".$@."\n";
		exit;
	}
	# check result
	if (!(defined $info)) {
		print "Error in tracker-response.\n";
		exit;
	}
	if (!(ref($info) eq 'HASH')) {
		print "Error in tracker-response.\n";
		exit;
	}
	if (!(exists($info->{'files'}))) {
		print "Error : tracker-response did not contain files.\n";
		exit;
	}
	# process response
	foreach my $fileEntry (%{$info->{'files'}}) {
		my $t = $info->{'files'}{$fileEntry};
		my $fileHash = $fileEntry;
		$fileHash =~ s/(.)/sprintf("%02x",ord($1))/egs;
		$fileHash = lc($fileHash);
		if ($fileHash eq $hash) {
			print $t->{'complete'}." seeder(s), ";
			print $t->{'incomplete'}." leecher(s).\n";
			exit;
		}
	}
	# error
	print "Error : tracker-response did not contain requested info.\n";
	exit;
}

#-------------------------------------------------------------------------------
# Sub: getUrl
# Parameters: string with url
# Return: res
#-------------------------------------------------------------------------------
sub getUrl() {
	my $url = shift;
	my $ua = LWP::UserAgent->new(
		'agent'		 => $PROG.$EXTENSION."/".$VERSION,
		'timeout'	 => $TIMEOUT
	);
	return $ua->get($url);
}

#-------------------------------------------------------------------------------
# Sub: printUsage
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printUsage {
	print <<"USAGE";

$PROG.$EXTENSION (Revision $VERSION)

Usage: $PROG.$EXTENSION operation path-to-torrent-meta-file

Operations :
 -i  : decode and print out torrent-info.
 -s  : get scrape-info and print out seeders + leechers.

Example:

$PROG.$EXTENSION -i /foo/bar.torrent
$PROG.$EXTENSION -s /foo/bar.torrent

USAGE

}

# EOF
