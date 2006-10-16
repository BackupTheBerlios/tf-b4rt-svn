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
package Fluxinet;
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
#  0 not initialized (null)
#  1 initialized
my $state = 0;

# message, error etc. keep it in one string for simplicity atm.
my $message = "";

# loglevel
my $LOGLEVEL = 2;

# port
my $port = 3150;

# server-socket
my ($SERVER, $Select);

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub new {
	# Create the object
	my $self = {};
	bless $self;
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub destroy {
	# set state
	$state = 0;
	# remove
	foreach my $handle ($Select->handles) {
		$Select->remove($handle);
		$handle->close;
	}
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: port                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# loglevel
	$LOGLEVEL = shift;
	if (!(defined $LOGLEVEL)) {
		# message
		$message = "loglevel not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# $port
	$port = shift;
	if (!(defined $port)) {
		# message
		$message = "port not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}

	print "Fluxinet : initializing (loglevel: ".$LOGLEVEL." ; port: ".$port.")\n";

	# load modules
	if (loadModules() != 1) {
		return 0;
	}

	# Create the read set
	$Select = new IO::Select();

	# Create the server socket
	$SERVER = IO::Socket::INET->new(
		LocalPort       => $port,
		Proto           => 'tcp',
		Listen          => 16,
		Reuse           => 1);
	if (!(defined $SERVER)) {
		# message
		$message = "could not create server socket";
		# set state
		$state = -1;
		# return
		return 0;
	}
	$Select->add($SERVER);

	# set state
	$state = 1;

	# return
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: loadModules                                                             #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub loadModules {

	# load IO::Socket
	if (eval "require IO::Socket")  {
		IO::Socket->import();
	} else {
		# message
		$message = "cant load perl-module IO::Socket : ".$@;
		# set state
		$state = -1;
		# return
		return 0;
	}

	# load IO::Select
	if (eval "require IO::Select")  {
		IO::Select->import();
	} else {
		# message
		$message = "cant load perl-module IO::Select : ".$@;
		# set state
		$state = -1;
		# return
		return 0;
	}
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
# Returns:                                                                     #
#------------------------------------------------------------------------------#
sub set {
}

#------------------------------------------------------------------------------#
# Sub: main                                                                    #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub main {
	# Get the readable handles. timeout is 0, only process stuff that can be
	# read NOW.
	my $return = "";
	my @ready = $Select->can_read(0);
	foreach my $socket (@ready) {
		if ($socket == $SERVER) {
			my $new = $socket->accept();
			$Select->add($new);
		} else {
			my $buf = "";
			my $char = getc($socket);
			while ($char ne "\n") {
					$buf .= $char;
					$char = getc($socket);
			}
			$return = Fluxd::processRequest($buf);
			$socket->send($return);
			$Select->remove($socket);
			close($socket);
		}
	}
}

#------------------------------------------------------------------------------#
# Sub: command                                                                 #
# Arguments: command-string                                                    #
# Returns: result-string                                                       #
#------------------------------------------------------------------------------#
sub command {
	shift; # class
	my $command = shift;
	# TODO
	return "";
}

#------------------------------------------------------------------------------#
# Sub: status                                                                  #
# Arguments: Null                                                              #
# Returns: Status information                                                  #
#------------------------------------------------------------------------------#
sub status {
	my $return = "";
	$return .= "\n-= Fluxinet.pm Revision ".$VERSION." =-\n";
	$return .= "port : $port \n";
	return $return;
}

################################################################################
# make perl happy                                                              #
################################################################################
1;
