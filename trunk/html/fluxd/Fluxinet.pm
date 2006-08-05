################################################################################
# $Id$
# $Date$
# $Revision$
################################################################################
package Fluxinet;
use strict;
use IO::Socket;
use IO::Select;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

my $port = 3150; # TODO : use value from db-bean
my ( $SERVER, $Select );

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
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	# Create the read set
	$Select = new IO::Select();

	# Create the server socket
	$SERVER = IO::Socket::INET->new(
		LocalPort       => $port,
		Proto           => 'tcp',
		Listen          => 16,
		Reuse           => 1);
	return 0 unless $SERVER;
	$Select->add($SERVER);

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


################################################################################
# make perl happy                                                              #
################################################################################
1;
