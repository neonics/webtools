#!/usr/bin/perl

print "Content-Type: text/html\n\n";

$cgi = "/cgi-bin/browse.pl";


 $q = $ENV{'QUERY_STRING'};
 @q = split("\&", $q);
 
 $dir=undef;
 map {
 	if ( /^dir=(.*)/ )
 	{
 		$dir=$1;
 	}
	if ( /^file=(.*)/ )
	{
		$file = $1;
	}
 } @q;

if ( defined ($file) )
{
	print "<pre>\n";
	print `cat $file`;
	print "</pre>\n";
	exit 1;
}


if ( !defined($dir) )
{
	$dir = `pwd`;
	chomp $dir;
}

@dirs = split("/", $dir);
$dirString = "";
map {
	$dirString .= " / $_";
} @dirs;

print "Directory: $dirString<br>\n";
@l = `ls $dir`;
chomp @l;
print "<ul>\n";
map {
	if ( -d "$dir/$_" )
	{
		print "<li><a href='$cgi?dir=$dir/$_'>$_</a></li>\n";
	}
	else
	{
		print "<li><a href='$cgi?file=$dir/$_'>$_</a></li>\n";
	}
} @l;
print "</ul>\n";


print "<div>\n";

print ` cd ../draft/tmp && make`;

print "\n</div>\n";

1;
