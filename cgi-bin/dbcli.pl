#!/usr/bin/perl

use lib '.';
use DB;

tie %db, 'DB', $ARGV[0] || 'db/webauth.db';

$db{test}{foo}={bar=>{baz=>'lala'}};

print "Tables:\n";
map {
	print "  $_ : $db{$_}\n";

	my $k = $_;
	map {
		my $k2=$_;
		print "    $k2 = { ".join('; ', map { "$_=".$db{$k}{$k2}{$_} } keys %{ $db{$k}{$_}} )." }\n";
	} keys %{ $db{$k} };
} keys %db;

print "Done\n";


sub dump
{
	my ($data) = @_;
}
