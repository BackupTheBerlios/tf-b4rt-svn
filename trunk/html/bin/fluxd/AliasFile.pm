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
package AliasFile;
use strict;
use warnings;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

# state
# -1 error
#  0 null
#  1 initialized (alias-file loaded)
my $state = 0;

# message, error etc. keep it in one string for simplicity atm.
my $message = "";

# alias-file
my $aliasFile = "";

# alias-file-data-hash, keys 1 : 1 AliasFile-class of TF
my %data;
# running
# percent_done
# time_left
# down_speed
# up_speed
# transferowner
# seeds
# peers
# sharing
# seedlimit
# uptotal
# downtotal
# size

# alias-file-error-array
my @errors;

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: null or path to alias-file                                        #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {
	my $class = shift;
	my $self = bless ({}, ref ($class) || $class);
	# initialize file now if name given in ctor
	$aliasFile = shift;
	if (defined($aliasFile)) {
		$self->initialize($aliasFile);
	}
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub destroy {

	# set state
	$state = 0;

	# strings
	$message = "";
	$aliasFile = "";

	# undef
	undef %data;
	undef @errors;
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: path to alias-file                                                #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# path-to-aliasfile
	$aliasFile = shift;
	if (!(defined $aliasFile)) {
		# message
		$message = "path-to-aliasfile not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# read in alias-file + set fields
	if (-f $aliasFile) {
		# sep + open file
		my $lineSep = $/;
		$/ = "\n";
		$. = 0;
		open(ALIASFILE,"<$aliasFile");
		# read data
		$data{"running"} = <ALIASFILE>;
		$data{"percent_done"} = <ALIASFILE>;
		$data{"time_left"} = <ALIASFILE>;
		$data{"down_speed"} = <ALIASFILE>;
		$data{"up_speed"} = <ALIASFILE>;
		$data{"transferowner"} = <ALIASFILE>;
		$data{"seeds"} = <ALIASFILE>;
		$data{"peers"} = <ALIASFILE>;
		$data{"sharing"} = <ALIASFILE>;
		$data{"seedlimit"} = <ALIASFILE>;
		$data{"uptotal"} = <ALIASFILE>;
		$data{"downtotal"} = <ALIASFILE>;
		$data{"size"} = <ALIASFILE>;
		chomp %data;
		# errors
		@errors = qw();
		while (<ALIASFILE>) {
			chomp;
			push(@errors, $_);
		}
		# close file + sep
		close ALIASFILE;
		$/ = $lineSep;
		# set state
		$state = 1;
		# return
		return 1;
	} else {
		# message
		$message = "aliasfile no file";
		# set state
		$state = -1;
		# return
		return 0;
	}
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
# Sub: get                                                                     #
# Arguments: key                                                               #
# Returns: value                                                               #
#------------------------------------------------------------------------------#
sub get {
	shift; # class
	my $key = shift;
	return $data{$key};
}

#------------------------------------------------------------------------------#
# Sub: set                                                                     #
# Arguments: key,value                                                         #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub set {
	shift; # class
	my $key = shift;
	$data{$key} = shift;
}

#------------------------------------------------------------------------------#
# Sub: getData                                                                 #
# Arguments: null                                                              #
# Returns: hash                                                                #
#------------------------------------------------------------------------------#
sub getData {
	return %data;
}

#------------------------------------------------------------------------------#
# Sub: getErrors                                                               #
# Arguments: null                                                              #
# Returns: array                                                               #
#------------------------------------------------------------------------------#
sub getErrors {
	return @errors;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
