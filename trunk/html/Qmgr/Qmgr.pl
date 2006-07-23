#!/usr/bin/perl
use strict;
use IO::Socket;
use Qmgr;

my $Command = "";
my $Torrent = "";
my $User    = "";
my $Host    = "localhost";
my $Port    = 3150;

$Command = shift;
if (!(defined $Command)) {
	Qmgr::PrintUsage();
	exit;
}

if ($Command =~ /.*(version|-v).*/) {
	Qmgr::printVersion();
	exit;
}

if ( $Command !~/^stop$|^status$|^jobs$|^queue$|^list$|^add$|^remove$|^set$|^worker$|^move-(?:up$|down$|top$|bottom$)/ ) {
	Qmgr::PrintUsage();
	exit;
}

if ($Command eq "add") {
	$Torrent = shift;
	if (!(defined $Torrent)) {
		Qmgr::PrintUsage();
		exit;
	}

	$User = shift;
	if (!(defined $User)) {
		Qmgr::PrintUsage();
		exit;
	}
} else {
	if ( ($Command eq "remove") or ($Command =~ /move/) ) {
		$Torrent = shift;
		if (!(defined $Torrent)) {
			Qmgr::PrintUsage();
			exit;
		}
		$User = undef;
	}
	if ($Command eq "set") {
		$Torrent = shift;
		if (!(defined $Torrent)) {
			Qmgr::PrintUsage();
			exit;
		}

		$User = shift;
	}
}

$Host = shift || 'localhost';
$Port = shift || 3150;

$Command .= " ".$Torrent." ".$User." \n";

my $Socket = IO::Socket::INET->new(
	PeerAddr => $Host,
	PeerPort => $Port,
	Proto    => 'tcp') or die "Can't create socket : $!";

send($Socket, $Command, 0);

sleep 1;

while (defined (my $buf = <$Socket>)) {
	if ($buf ne $Command) {
		print $buf;
	}
}

close $Socket;
