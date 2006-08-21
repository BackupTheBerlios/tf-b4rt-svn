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
package Watch;
use strict;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

# state
# -1 error
#  0 not initialized (null)
#  1 initialized
my $state = 0;

# message, error etc. keep it in one string for simplicity atm.
my $message = "";

# jobs-hash
my %jobs = undef;

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: Null                                                              #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {
	my $self = {};
	bless $self;
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub destroy {
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# jobs
	my $jobs = shift;
	if (!(defined $jobs)) {
		# message
		$message = "jobs not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	print "initializing Watch (jobs: ".$jobs.")\n"; # DEBUG

	# parse jobs
	my (@jobsAry) = split(/;/,$jobs);
	foreach my $jobEntry (@jobsAry) {
		chomp $jobEntry;
		my (@jobAry) = split(/:/,$jobEntry);
		my $user = shift @jobAry;
		chomp $user;
		my $dir = shift @jobAry;
		chomp $dir;
		if ((!($user eq "")) && (-d $dir)) {
			$jobs{$user} = $dir;
		}
	}

	# set state
	$state = 1;

	# return
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: getVersion                                                              #
# Arguments: null                                                              #
# Returns: VERSION                                                             #
#------------------------------------------------------------------------------#
sub getVersion {
	return $VERSION;
}

#------------------------------------------------------------------------------#
# Sub: getState                                                                #
# Arguments: null                                                              #
# Returns: state                                                               #
#------------------------------------------------------------------------------#
sub getState {
	return $state;
}

#------------------------------------------------------------------------------#
# Sub: getMessage                                                              #
# Arguments: null                                                              #
# Returns: message                                                             #
#------------------------------------------------------------------------------#
sub getMessage {
	return $message;
}

#------------------------------------------------------------------------------#
# Sub: set                                                                     #
# Arguments: Variable [value]                                                  #
# Returns: Info string                                                         #
#------------------------------------------------------------------------------#
sub set {
}

#------------------------------------------------------------------------------#
# Sub: main                                                                    #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub main {

	# watch in dirs for dropped meta-files
	foreach my $user (sort keys %jobs) {
		my $dir = $jobs{$user};
		if ((!($user eq "")) && (-d $dir)) {
			print "Watch::main : username \"".$user."\" ; dir \"".$dir."\"\n"; # DEBUG
		}
	}

}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Status information                                                  #
#------------------------------------------------------------------------------#
sub status {
	my $return = "";
	$return .= "\n-= Watch.pm Revision ".$VERSION."=-\n\n";
	$return .= "Watch.pm is currently running\n";
	return $return;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
