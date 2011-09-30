#!/usr/bin/perl
 
#use strict;
use warnings;
use diagnostics;
use lib '.';
use CGI;
use DB;

print "Content-Type: text/html\n\n";

$debug = "";

my $cgi = CGI->new();

my $cgiURL = $cgi->scriptURL;
$cgiURL = $cgiURL ? $cgiURL : "";

$session = $cgi->getSession();

$protectedURL = $cgi->{form}{url} || ($session?$session->{protectedURL}:undef) || $cgi->referer() || "http://www.google.com";

if (defined $session)
{
	warn "Session: ".$session.": ".join(',', keys(%{$session}))."\n";
}
else {warn "No session\n";}

$login = $cgi->authenticate( $cgi->{form}{username}, $cgi->{form}{password} );
$logout = $cgi->{form}{logout};

warn $login ? "Login: $login\n" : "No login\n";

if ( $login )
{
	$cgi->deleteSession() if defined($session);
	$session = $cgi->createSession($cgi->{form}{username});
	$session{protectedURL} = $protectedURL;
}
elsif ( $logout )
{
	$cgi->deleteSession() if defined ($session);
	$session = undef;
}
else
{
	$cgi->renewSession() if defined $session;
	
}

$debug .= $session ? "USER $session->{username}\n":"";


if ( defined $logout )
{
print <<"EOF";
	<html>
		<head>
			<meta http-equiv="set-cookie" content="key=; expires=Fri 3 Aug 2001 00:00:00 UTC; path=/"/>
		</head>
		<body>
		    <pre style="color:grey">$debug</pre>
			Logged out.<br/>
			<a href="$cgiURL">Login</a>,<br/>
			or <a href="$protectedURL">go back</a>.
		</body>
	</html>
EOF
}
elsif ( $login )
{
	print <<"EOF";
	<html>
		<head>
			<meta http-equiv="REFRESH" content="1;url=$protectedURL"/>
		</head>
		<body>
			<pre style="color:grey">$debug</pre>
			Logged in. Redirecting <a href="$protectedURL">back</a>.
		</body>
	</html>
EOF

}
elsif ( defined $session )
{
	print <<"EOF";
<html>
  <body>
    <pre style="color:grey">$debug</pre>
    Welcome, $session->{username}. <br/>
    All you can do now is <a href="?logout=1">logout</a>,<br/>
    or <a href="$protectedURL">go back</a>.
  </body>
</html>
EOF
}
else
{
	# $login = undef if no credentials, true or false if credentials.
	# at this point, it is either undef or false. 
	$msg = defined($login)?"Illegal credentials":"";
	print <<"EOF";
<html>
	<head>
	</head>
	<body>
		<pre style="color:grey">$debug</pre>
		<div style='color: red'>$msg</div>
		Log in
		<form method="post" action="$cgiURL">
			<input type="hidden" name="url" value="$protectedURL"/>
			<label for="username">Username</label>
			<input type="text" name="username"/><br/>
			<label for="password">Password</label>
			<input type="password" name="password"/><br/>
			<input type="submit"/>
		</form>
	</body>
</html>
EOF
}


1;
