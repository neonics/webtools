<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 *
 * Content-Logic-Style: Logic contains code within stylesheets
 * Content-Modules-Sheets: Modules are scripts (immediately-executable source code).
 */

	// debug:
	// Levels:
	// 0           - off
	// 1           - XML comments
	// 2 or higher - pre-HTML text

	// Verbosity:
	// 0 - none
	// 1 - basic files (content, modules, sheets)
	// 2 - pre-HTML text
	// 3 - verbose (resources)
	// 4 - 

if (get_magic_quotes_gpc()) {
    function stripslashes_gpc(&$value)
    {
        $value = stripslashes($value);
    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}


#phpinfo();
	if ( !isset( $debug ) )
		$debug = 2;

	ini_set('date.timezone', date_default_timezone_get());

	require_once( "lib/Debug.php" );
	require_once( "lib/RequestHandler.php" );
	require_once( "lib/Resource.php" );
	require_once( "lib/Util.php" );

	$requestURIRoots;

	$staticContent = array_merge( isset( $staticContent )
		? $staticContent
		: Array(), Array( 'css/', 'img/', 'js/', 'ckeditor/')
	);

	$redir = array( 'wiki/' => 'wiki.html' );

	# global var!
	$request = RequestHandler::init( $requestURIRoots, $staticContent, $redir );

	$pspLogicDir = "psp";
	$pspContentDir = "content";
	$pspStyleDir = "style";

	setupPaths( dirname( __FILE__ ) );

	$slashmode;

	RequestHandler::handle( $request );
?>
