<?php
//ob_start(); // so we can clear the buffer and send an error on fail

// see api/ and .htaccess
if ( !preg_match( "@api/([\w/\-_]+)@", $_SERVER['REQUEST_URI'], $m ) ) {
	header("HTTP/1.1 400 Illegal call");echo "illegal call";exit;
}

/**
 * @return true if handled, false to let default error handler handle the error.
 *         Note that you must `ini_set('display_errors', 'on' );` when returning false.
 */
set_error_handler( function( $errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
{
	$e2str = [
		E_ERROR							=> 'E_ERROR',
		E_PARSE							=> 'E_PARSE',
		E_CORE_ERROR				=> 'E_CORE_ERROR',
		E_CORE_WARNING			=> 'E_CORE_WARNING',
		E_COMPILE_ERROR			=> 'E_COMPILE_ERROR',
		E_COMPILE_WARNING		=> 'E_COMPILE_WARNING',
		E_USER_ERROR				=> 'E_USER_ERROR',
		E_STRICT						=> 'E_STRICT',
		E_RECOVERABLE_ERROR	=> 'E_RECOVERABLE_ERROR',
		E_DEPRECATED				=> 'E_DEPRECATED',
		E_USER_DEPRECATED		=> 'E_USER_DEPRECATED',
		E_WARNING						=> 'E_WARNING',
		E_NOTICE						=> 'E_NOTICE',
		E_USER_WARNING			=> 'E_USER_WARNING',
		E_USER_NOTICE				=> 'E_USER_NOTICE',
	];


	switch ( $errno )
	{
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_CORE_WARNING:
		case E_COMPILE_ERROR:
		case E_COMPILE_WARNING:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
		default:
			if ( ! headers_sent() )
				header("HTTP/1.1 500 API Error: [$errno] " . $errstr );
			while ( ob_get_level() )
				ob_end_flush();
			echo "fatal error:\n<pre>";
			debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			echo "</pre>\n";
			break;

		case E_WARNING:
		case E_NOTICE:
		case E_DEPRECATED:
		case E_STRICT:
		case E_USER_WARNING:
		case E_USER_NOTICE:
		case E_USER_DEPRECATED:
			//return false;
			echo "<code><b>[".$e2str[$errno]."]</b> $errstr</code><br/>\n";
			return true;
	}

	return true;
}, -1 );
#error_reporting( -1 );
#ini_set('display_errors', 'on' );
#ini_set('display_startup_errors', 'on' );


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
