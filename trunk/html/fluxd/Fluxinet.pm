package Fluxinet;
use strict;
use IO::Socket;
use IO::Select;

my $port = 3150;
my ( $SERVER, $READSET );

#------------------------------------------------------------------------------#
# Sub: New                                                                     #
# Arguments: Null                                                              #
# Returns: Info String                                                         #
#------------------------------------------------------------------------------#
sub New {
	# Create the read set
	$READSET = new IO::Select();

	# Create the server socket
	$SERVER = IO::Socket::INET->new(
		LocalPort       => $port,
		Proto           => 'tcp',
		Listen          => 16,
		Reuse           => 1);
	die "Could not create server socket: $!\n" unless $SERVER;
	$READSET->add($SERVER);
}

#------------------------------------------------------------------------------#
# Sub: CheckConns                                                              #
# Arguments: Null                                                              #
# Returns: Null                                                                #
#------------------------------------------------------------------------------#
sub CheckConns {
        SOCKET: while ( my @ready = $READSET->can_read(Fluxd::SleepDelta())) {
                foreach my $socket (@ready) {
                        if ($socket == $SERVER) {
                                # Create a new socket
                                my $new = $SERVER->accept;
                                $READSET->add($new);
                        } else {
                                # Process the socket
                                my $buf = <$socket>;
                                if($buf) {
                                        my $return = Fluxd::ProcessRequest($buf);
                                        send($socket, $return, 0);
                                        $READSET->remove($socket);
                                        $socket->close;
                                } else {
                                        # Client has closed connection
                                        $READSET->remove($socket);
                                        $socket->close;
                                }
                                last SOCKET;
                        }       
                }
        }
}
