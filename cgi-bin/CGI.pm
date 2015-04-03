package CGI;

use strict;
use warnings;
use Carp;
use POSIX;
#use IO qw/O_CREAT O_RDWR/;

use lib '.';
use DB;

my $dbfilename = 'db/webauth.db';

my $cookieName = "sessionId";
my $maxCookieAge = 60 * 10;
my $cookiePath = "/";

sub new
{
	my $this = shift;
	my $class = ref($this) || $this;
	my $self = {
		db => undef,
	};

	bless $self, $class;
	$self->constructor( @_ );
	return $self;
}

sub constructor
{
	my ($self, @args) = @_;

	$self->{form} = $self->parseRequest();

	if ( 0 ) {
	map {
		warn "  $_ = $self->{form}{$_}\n";
	} keys %{ $self->{form} };
	}
}


sub _initdb
{
	my ($self, @args) = @_;

	defined( $self->{db} ) and return;

	tie my %db, 'DB', $dbfilename;
	$self->{db} = \%db;

	if ( scalar %{ $self->{db}{users} } <= 0)
	{
		warn "Initializing database..\n";
		$self->{db}{users} = {kenney=>{root=>'override'}};
	}
}


sub referer
{
	my ($self) = @_;
	return $ENV{HTTP_REFERER};
}

sub scriptURL
{
	my $self = shift;

	return $ENV{SCRIPT_NAME};
}

sub DESTROY
{
	my ($self) = shift;
	defined $self->{db} and do {
		untie $self->{db}
		or warn "Cannot close database: $!";
	};
}

sub getCookie
{
no warnings;
	my ($self, $cn) = @_;

	$cn = $cn || $cookieName;

	my $cookieStr = $ENV{HTTP_COOKIE};

	warn "Cookie: $cookieStr\n";

	my $value = undef;

	map { 
		warn "Checking cookie: $_ for '$cookieName'\n";
		if ( /^$cookieName=(.*)$/ )
		{
			return $1;
		}
	} split( /;\s*/, $cookieStr );

	return $value;
}

sub getSession
{
	my ($self) = @_;

	my $cookie = $self->getCookie($cookieName);
	warn $cookie ? "Cookie: $cookie\n" : "No cookie\n";

	$self->_initdb;
	my $foo = $cookie ? $self->{db}{sessions}{$cookie} : undef;

	if ( defined $foo )
	{
	warn "Found session: $foo\n";
	map {
		warn "  $_ = $$foo{$_}\n";
	} keys %{ $foo };
	}

	return $foo;
}

sub deleteSession
{
	my ($self, $sessionId) = @_;

	my $sid = $sessionId || $self->getCookie();

	$self->_initdb;
	warn "Deleting session $sid: $self->{db}{sessions}{$sid}\n";
	delete $self->{db}{sessions}{$sid};
	print "Set-Cookie: $cookieName=$sid; Max-Age: 0; Path=$cookiePath;\n";
}

sub renewSession
{
	my $self=shift;
	my $sid = $self->getCookie($cookieName);
	print "Set-Cookie: $cookieName=$sid; Max-Age: $maxCookieAge; Path=$cookiePath;\n";
}

sub createSession
{
	my ($self, $username) = @_;

	my $sid = "";
	while (length($sid) < 16)
	{
		$sid .= sprintf("%02x", rand(256));
	}

	warn "SessionId: $sid  username: $username\n";

	warn "Set-Cookie: $cookieName=$sid; Max-Age: $maxCookieAge; Path= $cookiePath\n";
	print "Set-Cookie: $cookieName=$sid; Max-Age: $maxCookieAge; Path= $cookiePath\n";

	$self->_initdb;
	$self->{db}{sessions}{$sid} = { username=>$username };
	return $self->{db}{sessions}{$sid};
}

sub cryptPass
{
	my ($self, $username) = @_;
	$self->_initdb;
	if ( defined( $self->{db}{users}{$username} ) )
	{
		return crypt $self->{db}{users}{$username}, 303;
	}
	else
	{
		return undef;
	}
}


sub authenticate
{
	my ($self, $username, $password) = @_;
	if ( defined($username) && defined ($password ) )
	{
warn "Authenticate: user=$username, pass=$password\n";
		$self->_initdb();
		return $self->{db}{users}{$username}{password} eq $password ? 1 : 0;
	}
	else
	{
		return undef;
	}
}

sub parseRequest
{
	my $self=shift;
no warnings;
	my ($buffer, $pair);

	# Read in text
	$ENV{'REQUEST_METHOD'} =~ tr/a-z/A-Z/;

	if ($ENV{'REQUEST_METHOD'} eq "POST")
	{
		read(STDIN, $buffer, $ENV{'CONTENT_LENGTH'});
	}
	else
	{
		$buffer = $ENV{'QUERY_STRING'};
	}

	# Split information into name/value pairs

	my %FORM;

	my @pairs = split(/&/, $buffer);
	foreach $pair (@pairs)
	{
		my ($name, $value) = split(/=/, $pair);
		$FORM{$name} = $self->decodeURI( $value );
	}

	return \%FORM;
}


sub currentURL
{
no warnings;
	return "http://".$ENV{SERVER_NAME}.":".$ENV{SERVER_PORT}.$ENV{REQUEST_URI};
}

sub encodeURI
{
	my ($self, $uri) = @_;

	$uri =~ s/([^A-Za-z0-9])/sprintf("%%%02X", ord($1))/seg;
	return $uri;
}

sub decodeURI
{	
	my ($self, $uri) = @_;

	$uri =~ tr/+/ /;
	$uri =~ s/%(..)/pack("C", hex($1))/eg;

	return $uri;
}


1;
