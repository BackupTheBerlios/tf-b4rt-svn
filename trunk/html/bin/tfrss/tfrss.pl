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
################################################################################

# timeout for lwp-operations
my $TIMEOUT = 10;

# arg-vars
my ($PATH_FILTERS, $PATH_HISTORY, $PATH_SAVE, $RSS_URL);

# filters
my @filters;

# history
my @history;
my @historyNew;

# data
my $data;

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
if (($argCount != 1) && ($argCount != 4)) {
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

# init arg vars
initArgVars();

# load modules
loadModules();

# load filters
loadFilters();

# load history
loadHistory();

# load data
loadData();

# process data
processData();

# update history
updateHistory();

# exit
exit;

#===============================================================================
# Subs
#===============================================================================

#------------------------------------------------------------------------------#
# Sub: processData                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub processData {
	# Loop through all of the regular expressions
	FILTERS: foreach my $filter (@filters) {
		print "***** ".$filter." *****\n";
		# compare the filter to each torrent in the xml doc
		TORRENTS: foreach my $torrent (@{$data->{channel}->{item}}) {
			# if we have a match, save torrent file
			# if ($torrent->{title} =~ /($filter)/i && $torrent->{title} !~ /HR/){
			if ($torrent->{title} =~ /($filter)/i) {
				# Set the match flag to false
				my $match = 0;
				# Check the history file for the torrent we're looking at
				if ((scalar(@history)) > 0) {
					HISTORY: foreach my $hist (@history) {
						# If we find the torrent, set the match flag to true
						if ($hist eq $torrent->{title}){
							$match = 1;
							last HISTORY;
						}
					}
				}
				# if we haven't already downloaded the torrent, process it
				if (!$match) {
					# Add the torrent to the history
					push @historyNew, $torrent->{title};
					# print it
					print "$torrent->{title}\n";
					# Download the torrent
					downloadTorrent($torrent->{link}, $PATH_SAVE.$torrent->{title}.".torrent");
				}
			}
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: loadData                                                                #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadData {
	# load rss-feed
	my $feed = getUrl($RSS_URL);
	if (defined($feed)) {
		# Create the xml object
		my $rss = new XML::Simple;
		# Parse the xml object
		eval {
			$data = $rss->XMLin($feed);
		};
		if ($@) {
			print STDERR "Error : cant parse feed-data from ".$RSS_URL.": ".$@."\n";
			exit;
		}
	} else {
		exit;
	}
}

#------------------------------------------------------------------------------#
# Sub: loadFilters                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadFilters {
	open(FILTERS, $PATH_FILTERS);
	while(<FILTERS>) {
		chomp;
		push @filters, $_;
	}
	close FILTERS;
}

#------------------------------------------------------------------------------#
# Sub: loadHistory                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadHistory {
	if (-f $PATH_HISTORY) {
		open(HISTORY, $PATH_HISTORY);
		while(<HISTORY>) {
			chomp;
			push @history, $_;
		}
		close HISTORY;
	}
}

#------------------------------------------------------------------------------#
# Sub: updateHistory                                                           #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub updateHistory {
	if ((scalar(@historyNew)) > 0) {
		open(HISTORY, ">>$PATH_HISTORY");
		foreach my $hist (@historyNew) {
			print HISTORY $hist."\n";
		}
		close HISTORY;
	}
}

#------------------------------------------------------------------------------#
# Sub: downloadTorrent                                                         #
# Arguments: url, destination                                                  #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub downloadTorrent {
	my $turl = shift;
	my $tdest = shift;
	eval {
		local $SIG{ALRM} = sub {die "alarm\n"};
		alarm $TIMEOUT;
		getstore($turl, $tdest);
		alarm 0;
	};
	if ($@) {
		if ($@ eq "alarm\n") {
			print STDERR "Error : download torrent from ".$turl." timed out.\n";
		} else {
			print STDERR "Error : cant download torrent from ".$turl.": ".$@."\n";
		}
		return 0;
	}
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: getUrl                                                                  #
# Parameters: string with url                                                  #
# Return: data or undef                                                        #
#------------------------------------------------------------------------------#
sub getUrl() {
	my $url = shift;
	my $urldata;
	eval {
		local $SIG{ALRM} = sub {die "alarm\n"};
		alarm $TIMEOUT;
		$urldata = get($url);
		alarm 0;
	};
	if ($@) {
		if ($@ eq "alarm\n") {
			print STDERR "Error : download URL ".$url." timed out.\n";
		} else {
			print STDERR "Error : cant download URL ".$url." : ".$@."\n";
		}
		return undef;
	}
	return $urldata;
}

#------------------------------------------------------------------------------#
# Sub: loadModules                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub loadModules {
	# load LWP::Simple
	if (eval "require LWP::Simple")  {
		LWP::Simple->import();
	} else {
		print STDERR "Error : cant load perl-module LWP::Simple : ".$@."\n";
		exit;
	}
	# load XML::Simple
	if (eval "require XML::Simple")  {
		XML::Simple->import();
	} else {
		print STDERR "Error : cant load perl-module XML::Simple : ".$@."\n";
		exit;
	}
}

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
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub printVersion {
	print $PROG.".".$EXTENSION." Version ".$VERSION."\n";
}

#------------------------------------------------------------------------------#
# Sub: initArgVars                                                             #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub initArgVars {
	# init arg-vars
	$PATH_SAVE = shift @ARGV;
	$PATH_FILTERS = shift @ARGV;
	$PATH_HISTORY = shift @ARGV;
	$RSS_URL = shift @ARGV;
	# check args
	if (!(-f $PATH_FILTERS)) {
		print STDERR "Error : filter-file is no file : ".$PATH_FILTERS."\n";
		exit;
	}
	if (!(-d $PATH_SAVE)) {
		print STDERR "Error : save-location is no dir : ".$PATH_SAVE."\n";
		exit;
	}
	if (!((substr $PATH_SAVE, -1) eq "/")) {
		$PATH_SAVE .= "/";
	}
}

#------------------------------------------------------------------------------#
# Sub: printUsage                                                              #
# Parameters: null                                                             #
# Return: null                                                                 #
#------------------------------------------------------------------------------#
sub printUsage {
	print <<"USAGE";
$PROG.$EXTENSION (Revision $VERSION)

Usage: $PROG.$EXTENSION save-location filter-file history-file rss-feed-url
       $PROG.$EXTENSION check
       $PROG.$EXTENSION version
       $PROG.$EXTENSION help

Example:
$PROG.$EXTENSION /path/to/rss-torrents/ /path/to/filter.dat /path/to/filter.hist http://www.example.com/feed.xml

USAGE

}

# EOF
