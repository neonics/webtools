<?php
//ob_start(); // so we can clear the buffer and send an error on fail

// see api/ and .htaccess
if ( !preg_match( "@api/([\w/]+)@", $_SERVER['REQUEST_URI'], $m ) ) {
	header("HTTP/1.1 400 Illegal call");echo "illegal call";exit;
}
//set_error_handler( function(/*integer*/ $errno,/* string*/ $errstr, string $errfile=null, int $errline=null, array $errcontext=null)
set_error_handler( function( $errno, $errstr, $errfile=null, $errline=null, $errcontext=null)
{
	//ob_end_clean();	// drop 

	header("HTTP/1.1 404 Unknown API: " . $errcontext['m'][1]);
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


#if( file_exists( __DIR__."/api/".$m[1].".php" ) )
	require_once( __DIR__."/api/".$m[1].".php" );
#else
#	require_once( __DIR__."/../webtools/api/".$m[1].".php" ); # XXX fetch from handle.php somehow


//ob_flush();
