#!/usr/bin/perl
# $Id$
use strict;
use Qmgr;

my $queue = Qmgr->new(shift, shift, shift);

while ( 1 ) {
	$queue->ProcessQueue();
	$queue->CheckConnections();
}

