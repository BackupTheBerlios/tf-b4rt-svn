#!/usr/bin/perl -w
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


use List::Util qw(min max);


open(IN, '<manual.txt') or die 'cannot open input file';


use constant STATE_IN_START  => 0;
use constant STATE_IN_PRETOC => 1;
use constant STATE_IN_TOC    => 2;
use constant STATE_IN_TEXT   => 3;

my $state = STATE_IN_START;

my $toc = [ [ '__root_marker__', [] ] ];
                              # [
                              #   [ '__root_marker__',
                              #     [
                              #       [ 'Introduction', [] ],
                              #       [ 'User operations',
                              #         [
                              #           [ 'Working with metadata files...',
                              #             ...
                              #             [ 'Uploading individual metadata files from your filesystem', [] ]
                              #           ]
                              #         ]
                              #       ]
                              #     ]
                              #   ]
                              # ]

my %indents = ( 0 => $toc );  # (
                              #   0 => $toc,              # (global contents)
                              #   4 => $$toc[0][1],       # (contents of root)
                              #   8 => $$toc[0][1][1][1], # (contents of 'User operations')
                              #   ...
                              # )

my %contents;


sub gettocentry(@) {
  $_ = $$toc[0];
  $_ = $$_[1][shift] while @_;
  return $_;
}

sub getnexttocpos(@) { # Simple DFS.
  my $entry = gettocentry @_;
  if (@{$$entry[1]}) { # Children, visit first one.
    push @_, 0;
  }
  else {
    while ($#_ >= 0) {
      my @uppos = @_[0..$#_-1];
      my $upentry = gettocentry @uppos;
      if ($_[$#_] < $#{$$upentry[1]}) { # Brethren, go bug next one.
        $_[$#_]++;
        last;
      }
      else { # Go up one level.
        @_ = @uppos;
      }
    }
  }
  return @_;
}

sub isendpos(@) {
  return $#_ == -1;
}

my @nexttocpos = ( 0 );
my $curtocentry;
my $nexttocentry;


sub getline($) {
  $_ = shift;
  chomp;
  $_ =~ /^(\s*)(.*)\s*$/;
  return (length $1, $2);
}

while (my $line = <IN>) {

  if ($state == STATE_IN_START) {
    # Just wait for TOC to start.
    $state = STATE_IN_PRETOC if $line =~ /Contents/;
    next;
  }

  if ($state == STATE_IN_PRETOC) {
    # Just wait for TOC to start.
    $state = $line =~ /========/ ? STATE_IN_TOC : STATE_IN_START;
    next;
  }

  my ($depth, $text) = getline $line;

  if ($state == STATE_IN_TOC) {
    next if length $text == 0;

    if ($text =~ /\*\*\*\*\*\*\*\*\*\*\*\*\*\*\*\*/) {
      $state = STATE_IN_TEXT;
      undef %indents;
      $nexttocentry = gettocentry @nexttocpos;
      next;
    }

    my @depths = keys %indents;

    # Found an item at an already-existing depth (don't even get me started about those syntaxes).
    if (exists($indents{$depth})) {
      # Add it.
      my $pa = $indents{$depth};
      push @{$pa}, [ $text, [] ];
      # And remove any deeper indents.
      delete @indents{ grep { $_ > $depth } @depths };
    }

    # Found an item deeper than anything found yet (new depth).
    elsif ($depth > max @depths) {
      my $maxdepth = max @depths;
      # Insert it.
      my $grandpa = $indents{$maxdepth};
      my $pa = $$grandpa[$#$grandpa][1];
      push @{$pa}, [ $text, [] ];
      # And remember indent.
      $indents{$depth} = $pa;
    }

    else {
      die "bad toc indentation (depth: $depth)";
    }
  }

  if ($state == STATE_IN_TEXT) {
    if ($text =~ /^\s*\Q$$nexttocentry[0]\E\s*$/) {
      $curtocentry = $nexttocentry;
      @nexttocpos = getnexttocpos @nexttocpos;
      $nexttocentry = gettocentry @nexttocpos;
      next;
    }

    if (exists $contents{$curtocentry}) {
      push @{$contents{$curtocentry}}, ($depth, $text);
    }
    elsif (length $text > 0) {
      $contents{$curtocentry} = [ ($depth, $text) ];
    }
  }
}

die "unterminated doc (pos: @nexttocpos, waiting for: \"$$nexttocentry[0]\")" unless isendpos @nexttocpos;

close(IN);


sub qbkize(@) {
  my $ret = '';
  my @listdepths = ( -1 );
  my @listtypes  = ();
  my @listopen   = ();
  my $pendingnl = 0;
  while (@_) {
    my ($depth, $text) = (shift, shift);
    my $eos = length $text == 0 && !@_;
    $text =~ s/torrentflux-b4rt/__proj__/g;
    $text =~ s/Torrentflux-b4rt/__Proj__/g;
    die "partial project name (line: $text)" if $text =~ /torrentflux-/i || $text =~ /-b4rt/i;
    if ($eos || length $text > 0) {
      while ($depth < $listdepths[$#listdepths]) {
        pop @listdepths;
        my $type = pop @listtypes;
        my $open = pop @listopen;
        if ($type == 1) {
          $ret .= ']]' if $open;
          $ret .= "\n".('  ' x $#listdepths).']';
          $pendingnl = 1;
        }
      }
    }
    my $vlist = 0 && ($text =~ /^[-o*]\s+(\S+?\s+){1,3}\s*\-\s*/ && $text =~ s/^[-o*]\s+//);
    my $ulist = !$vlist &&            $text =~ s/^[-o*]\s+//;
    my $olist = !$vlist && !$ulist && $text =~ s/^\d+\.\s+//;
    if ($vlist || $ulist || $olist) { # list item
      if ($depth > $listdepths[$#listdepths]) {  # new list
        push @listdepths, $depth;
        push @listtypes, ($vlist ? 1 : 0);
        push @listopen, 0;
        $ret .= "\n".('  ' x ($#listdepths-1)).'[variablelist' if $vlist;
      }
      if ($vlist) {
        $text =~ s/^(.+)\s*\-\s*//;
        my $head = $1;
        $head =~ s/\s+$//;
        $ret .= ']]' if $listopen[$#listopen];
        $listopen[$#listopen] = 1;
        $ret .= "\n".('  ' x ($#listdepths-1)).'[['.$head.'] ['.$text;
        $pendingnl = 0;
      }
      else {
        $ret .= "\n" if $pendingnl;
        $ret .= "\n".('  ' x ($#listdepths-1)).($olist ? '# ' : '* ').$text;
        $pendingnl = 0;
      }
    }
    else {
      if ($eos || length $text > 0) {
        if ($depth == $listdepths[$#listdepths]) {
          pop @listdepths;
          my $type = pop @listtypes;
          my $open = pop @listopen;
          if ($type == 1) {
            $ret .= ']]' if $open;
            $ret .= "\n".('  ' x $#listdepths).']';
            $pendingnl = 1;
          }
        }
        $ret .= "\n" if $pendingnl;
        $ret .= "\n".('  ' x $#listdepths).$text;
        $pendingnl = 0;
      }
      else {
        $pendingnl = 1;
      }
    }
  }
  return $ret;
}


sub getqbk($);
sub getqbk($) {
  my $entry = shift;
  my $ret = '';
  $ret .= qbkize @{$contents{$entry}} if exists $contents{$entry};
  foreach (@{$$entry[1]}) {
    $ret .= "\n[section $$_[0]]\n";
    $ret .= getqbk($_);
    $ret .= "\n[endsect]\n";
  }
  return $ret;
}

$qbk = getqbk $$toc[0];



$eor = $/; # std::swap anyone? :o)
undef $/;

open(TMPLINDEX, '<tmpl.index.qbk') or die 'cannot open master template file';
$tmplindex = <TMPLINDEX>;
close(TMPLINDEX);

$/ = $eor;
undef $/;

$tmplindex =~ s/\%CONTENTS\%/$qbk/;



open(OUTINDEX, '>auto.qbk') or die 'cannot open master output file';
print OUTINDEX $tmplindex;
close(OUTINDEX);
