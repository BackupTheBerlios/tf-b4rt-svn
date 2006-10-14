#!/usr/bin/perl
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
#
# tfrss.pl is a simple script to download a torrent rss feed and download
# torrents that match regular expressions.
#
################################################################################
#
# tfrss.pl is based on TFRSS v0.1 written by Chris Craig (chris@chriscraig.net)
#
################################################################################


###############################################################################
#
# config
#
###############################################################################

# The list of regular expressions (1 regex per line)
$filters = "/path/to/tfrss/regex.dat";

# The URL of the rss feed
$rssFeed = "http://myfeed.com/feeds/thefeed/";

# The location to save torrents (should be the same as your watch folder in
# TorrentFlux
$saveLocation = "/path/to/watch/directory/";

# History File Location
$historyFile = "/path/to/tfrss/history.log";

# DO NOT EDIT BELOW THIS POINT UNLESS YOU KNOW WHAT YOU'RE DOING!
################################################################################

#includes
use XML::Simple;
use LWP::Simple;

# Open the file with the list of regular expressions
open(FILTERS,$filters);

# Create the xml object
$rss = new XML::Simple;

# Parse the xml object
$data = $rss->XMLin(get($rssFeed));

# Loop through all of the regular expressions
while(<FILTERS>){
	chomp;
	$filter = $_;
	print "*****$filter*****\n";

	# compare the filter to each torrent in the xml doc
	foreach $torrent (@{$data->{channel}->{item}}){

		# if we have a match, save torrent file

		# if($torrent->{title} =~ /($filter)/i && $torrent->{title} !~ /HR/){
		if($torrent->{title} =~ /($filter)/i){

			# Check the history file for the torrent we're looking at
			open(HISTORY,$historyFile);

			# Set the match flag to false
			$match = 0;

			#Read through the history file to see if we've already
			#downloaded this torrent before.
			while(<HISTORY>){
				chomp;

				# If we find the torrent, set the match flag to true
				if($_ eq $torrent->{title}){
					$match = 1;
				}
			}

			close HISTORY;

			# if we haven't already downloaded the torrent, process it
			if (!$match){

				# Add the torrent to the history log
				open(HISTORY,">>$historyFile");
				print HISTORY "$torrent->{title}\n";
				close HISTORY;
				print "$torrent->{title}\n";

				# Download the torrent
				getstore($torrent->{link},"$saveLocation$torrent->{title}.torrent");

			}
		}

	}
}

close FILTERS;
