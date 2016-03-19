<?php
//ob_start(); // so we can clear the buffer and send an error on fail

// see api/ and .htaccess
if ( !preg_match( "@api/([\w/\-_]+)@", $_SERVER['REQUEST_URI'], $m ) ) {
	header("HTTP/1.1 400 Illegal call");echo "illegal call";exit;
}
//set_error_handler( function(/*integer*/ $errno,/* string*/ $errstr, string $errfile=null, int $errline=null, array $errcontext=null)
set_error_handler( function( $errno, $errstr, $errfile=null, $errline=null, $errcontext=null)
{
	//ob_end_clean();	// drop 

	if ( isset( $errcontext['m'] ) )
	{
		header("HTTP/1.1 404 xUnknown API: " . $errcontext['m'][1]);
		while ( ob_get_level() )
			ob_end_flush();
		echo "xUnknown API: " . $errcontext['m'][1] . "\n";
	}
	else
	{
		switch ( $errno )
		{
			case 2:
			case 8:
			default:
				header( "HTTP/1.1 500 API Error: [$errno] $errstr" );
				while ( ob_get_level() )
					ob_end_flush();
				echo "API Error [$errno] $errstr\n";
				break;
		}
	}

	echo "<pre>ERROR: ".print_r( func_get_args(), 1 )."</pre>";

	exit;
	return true;
}
);


// from handle.php:
set_include_path(get_include_path()
  . PATH_SEPARATOR . __DIR__ . '/lib'
  . PATH_SEPARATOR . __DIR__ . '/inc'
  . PATH_SEPARATOR . __DIR__ . '/vendor/neonics/woocommerce-api'
  . PATH_SEPARATOR . __DIR__ . '/vendor/google/apiclient/src'
);


# authentication
{
	// needed to load psp/auth.php in AuthRequestHandler
	$pspLogicDir = "psp";	# otherwise will use logic/
	$pspContentDir = "content";
	$pspStyleDir = "style";

	require_once("RequestHandler.php");
	$request = new Request( array('/') );
	require_once('Resource.php');
  setupPaths( $pspBaseDir );# dirname( __FILE__ ) );

	ModuleManager::loadModule( "auth" );
  if ( ! auth_user() ) {
		if ( 0 )
		{
			require_once 'db/pdo.php';
			$auth = new SQLAuthentication( new PDODB( $dsn, $user, $pass ) );
			if ( ! $auth->process_headers() )
			{
				$auth->send_challenge( 'api-v1' );
				fatal('auth', "authentication required");
			}
			// else authenticated.
		}
		else
			fatal('auth', "authentication required");
  }
  Session::close();
}

# Sample

#if( file_exists( __DIR__."/api/".$m[1].".php" ) )
	require_once( __DIR__."/api/".$m[1].".php" );
#else
#	require_once( __DIR__."/../webtools/api/".$m[1].".php" ); # XXX fetch from handle.php somehow


//ob_flush();
