#!/usr/bin/perl
################################################################################
# $Id$
# $Revision$
# $Date$
# $Author$
#------------------------------------------------------------------------------#
# fluxpoller.pl                                                                #
#------------------------------------------------------------------------------#
# This Stuff is provided 'as-is'. In no way will the author be held            #
# liable for any damages to your soft- or hardware from this.                  #
# Feel free to change or rip the code.                                         #
################################################################################

use strict;

# should we try to find needed binaries ? (using "whereis" + "awk")
# use 1 to activate, else "constants" are used (the faster + safer way) 
my $autoFindBinaries = 0;

# define socket-bins. default : qw( python transmissionc )
my @BINS_SOCKET = qw( python transmissionc );

# Internal Vars
my ( $REVISION, $DIR, $PROG, $EXTENSION, $USAGE, $OSTYPE );

# bin Vars
my ( $BIN_CAT, $BIN_HEAD, $BIN_TAIL, $BIN_NETSTAT, $BIN_FSTAT, $BIN_GREP, $BIN_AWK );

# check env
checkEnv();

# define binaries
$BIN_CAT = "/bin/cat";
$BIN_HEAD = "/usr/bin/head";
$BIN_TAIL = "/usr/bin/tail";
$BIN_AWK = "/usr/bin/awk";
if ($OSTYPE == 1) { # linux
	$BIN_GREP = "/bin/grep";
	$BIN_NETSTAT = "/bin/netstat";
} elsif ($OSTYPE == 2) { # bsd
	$BIN_GREP = "/usr/bin/grep";
	$BIN_FSTAT = "/usr/bin/fstat";
}

#-------------------------------------------------------------------------------
# Main
#-------------------------------------------------------------------------------


# find binaries
if ($autoFindBinaries != 0) { findBinaries() };

# init some vars
$REVISION = do { my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };
($DIR=$0) =~ s/([^\/\\]*)$//;
($PROG=$1) =~ s/\.([^\.]*)$//;
$EXTENSION=$1;

# main-"switch"
SWITCH: {
	$_ = shift @ARGV;
	/^traffic/ && do {
		printTraffic(shift @ARGV, shift @ARGV);
		exit;
	};
	/^connections/ && do {
		printConnections(shift @ARGV);
		exit;
	};
	/.*(help|-h).*/ && do {
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
# Sub: printTraffic
# Parameters: string with path of flux-dir
#             string with wanted output-format (mrtg|cacti)     
# Return:		-
#-------------------------------------------------------------------------------
sub printTraffic {
	my $fluxDir = shift;
  if (!(defined $fluxDir)) {
    printUsage();
    exit;
  }
  $fluxDir .= "/.torrents";
  my $outputFormat = shift;
  if ($outputFormat eq "mrtg") {
    mrtgPrintTraffic($fluxDir);
	} elsif ($outputFormat eq "cacti") {
    cactiPrintTraffic($fluxDir);
	} else {
    # get traffic-vals
    my @traffic = fluxTraffic($fluxDir);
    # print traffic-vals
    print $traffic[0]." ".$traffic[1]."\n";
	}
}

#-------------------------------------------------------------------------------
# Sub: mrtgPrintTraffic
# Parameters:	string with path of flux-".stat-files"-dir
# Return:		-
#-------------------------------------------------------------------------------
sub mrtgPrintTraffic {
	my $fluxDir = shift;
  # get traffic-vals
  my @traffic = fluxTraffic($fluxDir);
  # print down-speed for mrtg 
  print $traffic[0];
  print "\n";
  # print up-speed for mrtg
  print $traffic[1];
  print "\n";
  # print uptime for mrtg
  mrtgPrintUptime();
  # print target-name for mrtg
  mrtgPrintTargetname();
}

#-------------------------------------------------------------------------------
# Sub: cactiPrintTraffic
# Parameters:	string with path of flux-".stat-files"-dir
# Return:		-
#-------------------------------------------------------------------------------
sub cactiPrintTraffic {
	my $fluxDir = shift;
  # get traffic-vals
  my @traffic = fluxTraffic($fluxDir);
  # print traffic for cacti
  my $trafficLine = "";
  $trafficLine .= "bandwidth_in:";
  $trafficLine .= $traffic[0];
  $trafficLine .= " ";
  $trafficLine .= "bandwidth_out:";
  $trafficLine .= $traffic[1];
  print $trafficLine;
}

#-------------------------------------------------------------------------------
# Sub: printConnections
# Parameters:	string with wanted output-format (mrtg|cacti)
# Return:		-
#-------------------------------------------------------------------------------
sub printConnections {
	my $outputFormat = shift;
  if ($outputFormat eq "mrtg") {
    mrtgPrintConnections();
	} elsif ($outputFormat eq "cacti") {
    cactiPrintConnections();
	} else {
    print fluxConnections();
    print "\n";
	}
}

#-------------------------------------------------------------------------------
# Sub: mrtgPrintConnections
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub mrtgPrintConnections {
  # print down-"speed" for mrtg
  print fluxConnections();
  print "\n";
  # print up-"speed" for mrtg
  print "0";
  print "\n";
  # print uptime for mrtg
  mrtgPrintUptime();
  # print target-name for mrtg
  mrtgPrintTargetname();
}

#-------------------------------------------------------------------------------
# Sub: cactiPrintConnections
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub cactiPrintConnections {
  # print connections for cacti
  print fluxConnections();
}

#-------------------------------------------------------------------------------
# Sub: mrtgPrintUptime
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub mrtgPrintUptime {
  # uptime data for mrtg
  my $uptime = `uptime`;
  my @uptimeAry = split(/\s/, $uptime, 6);
	(my $timeLabel = $uptimeAry[4]) =~ s/,.*//;
  print $uptimeAry[3]." ".$timeLabel."\n";
}

#-------------------------------------------------------------------------------
# Sub: mrtgPrintTargetname
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub mrtgPrintTargetname {
  # target-name for mrtg
  my $targetname = `hostname`; 
  print $targetname;
}

#-------------------------------------------------------------------------------
# Sub: fluxTraffic
# Parameters:	string with path of flux-".stat-files"-dir
# Return: array with current down-traffic ([0]) and up-traffic ([1])
#-------------------------------------------------------------------------------
sub fluxTraffic {
	my $fluxDir = shift;
  # init speed-sum-vars
  my $downspeed = 0.0;
  my $upspeed = 0.0;
  # process stat-files
  opendir(DIR, $fluxDir);
  my @files = map { $_->[1] } # extract pathnames
	map { [ $_, "$fluxDir/$_" ] } # full paths
	grep { !/^\./ } # no dot-files
	grep { /.*\.stat$/ } # only .stat-files
  readdir(DIR);
  closedir(DIR);
  foreach my $statFile (@files) {
    if (-f $statFile) {
      my ($down, $up) = split(/\n/, `$BIN_CAT $statFile | $BIN_HEAD -n 5 | $BIN_TAIL -n 2`, 2);
      if ($down != "") {
        $down =~ s/(.*\d)(\s.*)/$1/;
        chomp $down;
        $downspeed += $down;
      }
      if ($up != "") {
        $up =~ s/(.*\d)(\s.*)/$1/;
        chomp $up;
        $upspeed += $up;
      }
    }
  }
  my @retVal;
  $retVal[0] = ($downspeed<<10);
  $retVal[1] = ($upspeed<<10);
	return @retVal;
}

#-------------------------------------------------------------------------------
# Sub: fluxConnections
# Parameters:	-
# Return: int with current flux-tcp-connections (python + transmission)
#-------------------------------------------------------------------------------
sub fluxConnections {
  my $cons = 0;
	my $cons_temp = 0;
	if ($OSTYPE == 1) { # linux
		foreach my $bin_socket (@BINS_SOCKET) {
			$cons_temp = `$BIN_NETSTAT -e -p --tcp -n 2> /dev/null | $BIN_GREP -v root | $BIN_GREP -v 127.0.0.1 | $BIN_GREP -c $bin_socket`;
			chomp $cons_temp;
			$cons += $cons_temp;
		}
	} elsif ($OSTYPE == 2) { # bsd
		foreach my $bin_socket (@BINS_SOCKET) {
			$cons_temp = `$BIN_FSTAT -u www | $BIN_GREP $bin_socket | $BIN_GREP -c tcp`;
			chomp $cons_temp;
			$cons += $cons_temp;
		}
	}
  return $cons;
}

#-------------------------------------------------------------------------------
# Sub: printUsage
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub printUsage {
  print <<"USAGE";

$PROG.$EXTENSION (Revision $REVISION)

Usage: $PROG.$EXTENSION type [extra-args]

types:
<traffic>     : print current flux-traffic.
                extra-args : 1. flux-dir (aka "Path" inside flux-admin)
                             2. (optional) output-format (mrtg|cacti)

<connections> : print current flux-tcp-connections.
                extra-args : 1. (optional) output-format (mrtg|cacti)


Examples:

$PROG.$EXTENSION traffic /usr/local/torrent
$PROG.$EXTENSION traffic /usr/local/torrent mrtg
$PROG.$EXTENSION traffic /usr/local/torrent cacti

$PROG.$EXTENSION connections
$PROG.$EXTENSION connections mrtg
$PROG.$EXTENSION connections cacti

USAGE
  
}

#-------------------------------------------------------------------------------
# Sub: findBinaries
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub findBinaries {
  $BIN_CAT = `whereis cat | awk '{print \$2}'`; chomp $BIN_CAT;
  $BIN_HEAD = `whereis head | awk '{print \$2}'`; chomp $BIN_HEAD;
  $BIN_TAIL = `whereis tail | awk '{print \$2}'`; chomp $BIN_TAIL;
  $BIN_NETSTAT = `whereis netstat | awk '{print \$2}'`; chomp $BIN_NETSTAT;
	$BIN_FSTAT = `whereis fstat | awk '{print \$2}'`; chomp $BIN_FSTAT;
  $BIN_GREP = `whereis grep | awk '{print \$2}'`; chomp $BIN_GREP;
  $BIN_AWK = `whereis awk | awk '{print \$2}'`; chomp $BIN_AWK;
}

#-------------------------------------------------------------------------------
# Sub: checkEnv
# Parameters:	-
# Return:		-
#-------------------------------------------------------------------------------
sub checkEnv {	
  ## win32 not supported ;)
  if ("$^O" =~ /win32/i) {
    print "\r\nWin32 not supported.\r\n";
    exit;
  } elsif ("$^O" =~ /linux/i) {
		$OSTYPE = 1;
		return;
	} elsif ("$^O" =~ /bsd$/i) {
		$OSTYPE = 2;
		return;
	}
}


