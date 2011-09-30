#!?usr/bin/perl
#
# @author: Kenney Westerhof - kenney@neonics.com
#
# Binary Encoder/decoder
#
# use:
#
#
# DataCoder::encode( "key", $value );
# DataCoder::encode( "key", \$value );
# DataCoder::encode( "key", \@value );
# DataCoder::encode( "key", \%value );
#
# Data structures are recursively encoded/decoded. Note that for hashes,
# this is not done for keys - only scalars are allowed as keys.

package DataCoder;

use diagnostics;
use warnings;
use strict;

my %types = ('SCALAR'=>1, 'ARRAY'=>2, 'HASH'=>3);

sub encode
{
	my ($v) = @_;

	my $val = ref($v) ? $v : \$v;

	if ( ref($val) eq 'SCALAR' )
	{
		return pack "C Z*", $types{ref($val)}, $$val;
	}
	elsif ( ref($val) eq 'ARRAY' )
	{
		return pack "C S/(S/a*)*", $types{ref($val)},
		#	scalar @{$val},
			map {
				encode($_);
			} @{$val};
	}
	elsif ( ref($val) eq 'HASH' )
	{
		return pack "C S(Z* S/a*)*", $types{ref($val)}, 
			scalar keys %{$val},
			map {
				($_, encode($$val{$_}) )
			} keys %{$val};
	}
	else
	{
		warn "Unknown ref: ".ref($val)."\n";
	}
}

sub decode
{
	my ($data) = @_;
	my $type;

	($type, $data) = unpack("C a*", $data);


	if ( $type == $types{SCALAR} )
	{
		my $val = unpack("Z*", $data);
		return $val;
	}
	elsif ( $type == $types{ARRAY} )
	{
		my $size;
		($size, $data) = unpack "S a*", $data;

		my @val;
		for ( my $i = 0; $i < $size; $i++)
		{
			my $d;
			($d, $data) = unpack "S/a* a*", $data;

			push @val, decode($d);
		}
		return \@val;
	}
	elsif ( $type == $types{HASH} )
	{
		#return pack "C S/(Z* S/a*)*", $types{ref($val)}, 

		my $size;

		($size, $data) = unpack "S a*", $data;

		my %val;
		for ( my $i = 0; $i < $size; $i++)
		{
			my ($key, $d);
			($key, $d, $data) = unpack "Z* S/a* a*", $data;
			$val{$key} = decode( $d );
		}
		return \%val;
	}

	return "";
}

1;

__END__

my $scalar=303;
my $string="foo";
my @array = (1,2,3);
my %hash = (a=>'b', c=>'d');
my %hh = (a=>{b=>'c'});


print decode(encode( $scalar))."\n\n";
print decode(encode( $string))."\n\n";
print decode(encode( \@array))."\n\n";
print decode(encode( \%hash))."\n\n";
print decode(encode( \%hh))."\n\n";

my $hr=decode(encode( \%hh));

map
{
	print "  hr: $_ = $$hr{$_}\n";
	my %h0 = %{ $$hr{$_} };

	map {
		print "    $_ = $h0{$_}\n";
	} keys %h0;
}
keys %{ $hr };


