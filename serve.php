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
$PSP_TIMING_BEGIN = microtime(true);

if ( !isset( $psp_timing_show ) )
$psp_timing_show = false; // note that this is applied to all files, not just html!

try
{
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
		$debug = 4;

	// XXX maybe prepend instead
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/lib' );

	ini_set('date.timezone', date_default_timezone_get());

	require_once( dirname(__FILE__)."/lib/Util.php" );
	require_once( dirname(__FILE__)."/lib/Debug.php" );
	require_once( dirname(__FILE__)."/lib/ClassLoader.php" );	# extensible; initializes __autoload
	require_once( dirname(__FILE__)."/lib/RequestHandler.php" );
	require_once( dirname(__FILE__)."/lib/Resource.php" );

	$requestURIRoots;
	$theme;

	$staticContent = array_merge(
		gd( $staticContent, array() ),
		Array( 'css/', 'img/', 'js/', 'ckeditor/')
	);

	$psp_custom_handlers = //array_merge(
		gd( $psp_custom_handlers, array() )
		+
		array( 'template' => 'TemplateRequestHandler' )
	//)
	;

	$redir = array( 'wiki/' => 'wiki.html' );

	# global var!
	$request = RequestHandler::init( $requestURIRoots, $staticContent, $redir );

	$pspLogicDir = "psp";
	$pspContentDir = "content";
	$pspStyleDir = "style";

	setupPaths( dirname( __FILE__ ) );

	RequestHandler::handle( $request );

} catch ( Exception $e ) {
	fatal( 'serve', "Fatal error: ". $e->getMessage() );
}

$PSP_TIMING_END = microtime(true);
$timing = sprintf( "done in %f ms", 1000* ( $PSP_TIMING_END - $PSP_TIMING_BEGIN ) );
if ( $debug ) debug('serve', $timing );
else if ( $psp_timing_show) echo "<!-- $timing -->";
?>
