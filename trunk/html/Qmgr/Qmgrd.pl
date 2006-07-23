#!/usr/bin/perl
use strict;
use Qmgr;

my $Command = shift;

if ($Command =~ /.*(version|-v).*/) {
	Qmgr::printVersion();
	exit;
}

my $queue = Qmgr->new($Command, shift, shift);

while ( 1 ) {
	$queue->ProcessQueue();
	$queue->CheckConnections();
}

