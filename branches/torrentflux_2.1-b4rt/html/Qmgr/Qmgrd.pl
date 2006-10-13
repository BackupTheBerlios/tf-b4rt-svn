#!/usr/bin/perl
use strict;
use Qmgr;

my $queue = Qmgr->new(shift, shift, shift);

while ( 1 ) {
	$queue->ProcessQueue();
	$queue->CheckConnections();
}

