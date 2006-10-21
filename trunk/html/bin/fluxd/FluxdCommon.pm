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
package FluxdCommon;
use Exporter;
@ISA = ('Exporter');
@EXPORT_OK = qw(
				getVersion
				niceTimeString
				);
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

################################################################################
# subs                                                                         #
################################################################################

#------------------------------------------------------------------------------#
# Sub: niceTimeString                                                          #
# Arguments: start-time                                                        #
# Return: nice Time String                                                     #
#------------------------------------------------------------------------------#
sub niceTimeString {
	my $startTime = shift;
	my ($dura, $duration, $days, $hours, $mins, $secs, $rest);
	$dura = ((time) - $startTime);
	$rest = $dura;
	$days = $hours = $mins = $secs = 0;
	$duration = "";
	if ($dura >= (24 * 60 * 60)) { # days
		$days = int((($rest / 60) / 60) / 24);
		$duration .= $days."d ";
		$rest = ($dura - ($days * 60 * 60 * 24));
	}
	if ($dura >= (60 * 60)) { # hours
		$hours = int(($rest / 60) / 60);
		$duration .= $hours."h ";
		$rest = ($dura - ($hours * 60 * 60) - ($days * 60 * 60 * 24));
	}
	if ($rest >= 60) { # mins
		$mins = int($rest / 60);
		$duration .= $mins."m ";
		$rest = ($dura - ($mins * 60) - ($hours * 60 * 60) - ($days * 60 * 60 * 24));
	}
	if ($rest > 0) { # secs
		$duration .= $rest."s";
	}
	return $duration;
}

#------------------------------------------------------------------------------#
# Sub: getVersion                                                              #
# Arguments: null                                                              #
# Returns: VERSION                                                             #
#------------------------------------------------------------------------------#
sub getVersion {
	return $VERSION;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
