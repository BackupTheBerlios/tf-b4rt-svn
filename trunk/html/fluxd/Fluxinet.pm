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

my $port = 3150;
my ( $SERVER, $Select );

#------------------------------------------------------------------------------#
# Sub: New                                                                     #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub New {
	# Create the read set
	$Select = new IO::Select();

	# Create the server socket
	$SERVER = IO::Socket::INET->new(
		LocalPort       => $port,
		Proto           => 'tcp',
		Listen          => 16,
		Reuse           => 1);
	die "Could not create server socket: $!\n" unless $SERVER;
	$Select->add($SERVER);

	# Create the object
	my $self = {};
	bless $self;
	return $self;
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
# Sub: Main                                                                    #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub Main {
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
                        $return = Fluxd::ProcessRequest($buf);
                        $socket->send($return);
                        $Select->remove($socket);
                        close($socket);
                }
        }
}

#------------------------------------------------------------------------------#
# Sub: Destroy                                                                 #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub Destroy {
	foreach my $handle ($Select->handles) {
		$Select->remove($handle);
		$handle->close;
	}
}
1;
