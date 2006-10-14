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
#                                                                              #
# tfrss.pl is a simple script to download a torrent rss feed and download      #
# torrents that match regular expressions.                                     #
#                                                                              #
# tfrss.pl is based on TFRSS v0.1 by Chris Craig (chris@chriscraig.net)        #
#                                                                              #
################################################################################
#                                                                              #
#  Requirements :                                                              #
#   * LWP::Simple  ( perl -MCPAN -e "install LWP::Simple" )                    #
#   * XML::Simple  ( perl -MCPAN -e "install XML::Simple" )                    #
#                                                                              #
################################################################################
use strict;
use warnings;
#
use XML::Simple;
use LWP::Simple;
################################################################################

# arg-vars
my ($PATH_TFLUX, $PATH_SAVE, $RSS_URL);

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

# check args
my $argCount = scalar(@ARGV);
if (($argCount != 1) && ($argCount != 3)) {
	printUsage();
	exit;
}

# ops
if ($argCount == 1) {
	SWITCH: {
		$_ = shift @ARGV;

		/check/ && do { # --- check ---
			check();
			exit;
		};
		/.*(version|-v).*/ && do { # --- version ---
			printVersion();
			exit;
		};
		/.*(help|-h).*/ && do { # --- help ---
			printUsage();
			exit;
		};
		printUsage();
		exit;
	}
}

# init vars
$PATH_TFLUX = shift @ARGV;
$PATH_SAVE = shift @ARGV;
$RSS_URL = shift @ARGV;


exit;


###############################################################################
# config
###############################################################################

# The list of regular expressions (1 regex per line)
my $filters = "/path/to/tfrss/regex.dat";

# The URL of the rss feed
my $rssFeed = "http://myfeed.com/feeds/thefeed/";

# The location to save torrents (should be the same as your watch folder in
# TorrentFlux
my $saveLocation = "/path/to/watch/directory/";

# History File Location
my $historyFile = "/path/to/tfrss/history.log";

# DO NOT EDIT BELOW THIS POINT UNLESS YOU KNOW WHAT YOU'RE DOING!
################################################################################


# Create the xml object
my $rss = new XML::Simple;

# Parse the xml object
my $data = $rss->XMLin(get($rssFeed));

# Open the file with the list of regular expressions
open(FILTERS,$filters);

# Loop through all of the regular expressions
while(<FILTERS>){
	chomp;
	my $filter = $_;
	print "*****$filter*****\n";

	# compare the filter to each torrent in the xml doc
	foreach my $torrent (@{$data->{channel}->{item}}){

		# if we have a match, save torrent file

		# if($torrent->{title} =~ /($filter)/i && $torrent->{title} !~ /HR/){
		if($torrent->{title} =~ /($filter)/i){

			# Check the history file for the torrent we're looking at
			open(HISTORY,$historyFile);

			# Set the match flag to false
			my $match = 0;

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


#===============================================================================
# Subs
#===============================================================================

#------------------------------------------------------------------------------#
# Sub: check                                                                   #
# Arguments: Null                                                              #
# Returns: info on system requirements                                         #
#------------------------------------------------------------------------------#
sub check {
	print "checking requirements...\n";
	# 1. perl-modules
	print "1. perl-modules\n";
	my @mods = ('LWP::Simple', 'XML::Simple');
	foreach my $mod (@mods) {
		if (eval "require $mod")  {
			print " - ".$mod."\n";
			next;
		} else {
			print "Error : cant load module ".$mod."\n";
			# Turn on Autoflush;
			$| = 1;
			print "Should we try to install the module with CPAN ? (y|n) ";
			my $answer = "";
			chomp($answer=<STDIN>);
			$answer = lc($answer);
			if ($answer eq "y") {
				exec('perl -MCPAN -e "install '.$mod.'"');
			}
			exit;
		}
	}
	# done
	print "done.\n";
}

#------------------------------------------------------------------------------#
# Sub: printVersion                                                            #
# Arguments: Null                                                              #
# Returns: Version Information                                                 #
#------------------------------------------------------------------------------#
sub printVersion {
	print $PROG.".".$EXTENSION." Version ".$VERSION."\n";
}

#-------------------------------------------------------------------------------
# Sub: printUsage
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printUsage {
	print <<"USAGE";
$PROG.$EXTENSION (Revision $VERSION)

Usage: $PROG.$EXTENSION tflux-path save-location rss-feed-url

Example:
$PROG.$EXTENSION /usr/local/torrent /save/location http://www.example.com/feed.xml

USAGE

}

# EOF
