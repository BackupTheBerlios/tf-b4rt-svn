#!/usr/bin/perl

# $Id$

use strict;
use Qmgr;

my $Command = shift;

if ($Command =~ /.*(version|-v).*/) {
	Qmgr::printVersion();
	exit;
}

my $queue = Qmgr->new($Command, shift, shift, shift, shift);

while ( 1 ) {
	$queue->ProcessQueue();
	$queue->CheckConnections();
}

