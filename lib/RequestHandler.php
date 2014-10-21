<?php
/**
 * Extensible Request Handling.
 *
 *
 * RequestHandler::$requesthandlers is initialized with these sequenced handlers:
 *
 * - $psp_custom_handlers* - see below
 * - 'template' (via custom handlers); see TemplateRequestHandler.php.
 * - 'log' 			updates access log; use this name for early-always access
 * - 'redirect' internal redirect: rewrite Request and continue
 * - 'static' 	(css/, img/, js/)
 * - 'content' 	('db/content/',  '.', 'content/')
 * - 'dynamic' 	the PSP Core handler, executing a content XML using the psp module.
 *
 * See serve.php for default configuration and override mechanism.
 *
 * Custom handlers*)
 *   $psp_custom_handlers is an array( name => classname ). All handlers are
 *   at current executed in sequence of class RequestHandler instantiation.
 *   It is foreseen that the sequence might be modeled by a RequestLifecycle
 *   using different processing phases (and possibly complex decision trees),
 *   so, treat any of the above listed 'phase' names as reserved.
 *
 * NOTE: any ob_end_flush() below is debug related, prior to the actual content output.
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */

require_once( "Util.php" );
require_once( "ModuleManager.php" );
require_once( "XML.php" );
require_once( "Resource.php" );

/**
 * Data object that constructs path information from server environment variables.
 */
class Request
{
	public $requestBaseURL;		# http://..../WEBROOT/
	public $requestURI;				# /WEBROOT/bar/baz.html?foo
	public $requestBaseURI;		# /WEBROOT/
	public $requestRelURI;		# bar/baz.html
	public $requestPathURI;		# /WEBROOT/bar/
	public $requestRelPathURI;# bar/
	public $requestFileURI;		# baz.html
	public $requestQuery;			# ?foo   # '?' included for easy xsl

	# slash url example:   /admin/foo/bar
	public $slashmode;				# redirect parameter: 1 or null
	public $slashpath;				# redirect parameter: foo/bar
	public $slashpage;				# redirect parameter: admin

	public $requestLang;

	public $basedir;

	public $requestDir;
	public $requestFile;
	public $in;

	public $style = "layout.xsl"; # processing instr: <?psp style href=".."

	public function __construct( $requestURIRoots )
	{
		global $debug;


		if ( !preg_match( "@^(.*?)(\?.*)?$@", $_SERVER["REQUEST_URI"], $matches ) )
			die ("Regexp error");

		$this->requestURI = $matches[1];
	#	$this->requestQuery = array_key_exists( 2, $matches ) ? $matches[2] :
	#		# instead of null
	#		"?".$_SERVER["QUERY_STRING"];
		$this->requestQuery = $_SERVER['QUERY_STRING'];

		# check if there was an internal redirect - if so, the target refers to the file
		# to load.
		# REDIRECT_URL : the .htaccess target
		# REDIRECT_QUERY_STRING: the .htaccess target query string + orig q string
		#
		# EXAMPLE .htaccess:
		#
		#  RewriteRule ^admin/(.*?)$ admin.html?psp:slashmode=1&%{QUERY_STRING} [L]
		#
		# need the %{QUERY_STRING} so the original query ends up in REDIRECT_QUERY_STRING.
		# This also affects the $_SERVER['QUERY_STRING'] which contains the whole query.
		# $_SERVER['REQUEST_URI'] contains the original URI + query string.
		$requestOrigURI;

		if ( array_key_exists( "REDIRECT_URL", $_SERVER ) )
		{
			$requestOrigURI = $_SERVER["REDIRECT_URL"];
			#if ( array_key_exists( "REDIRECT_QUERY_STRING", $_SERVER) )
			#	$this->requestQuery = "?".$_SERVER["REDIRECT_QUERY_STRING"];
		}
		else
		{
			$requestOrigURI = $this->requestURI;
		}


		debug('', '');
		debug('request', "REQUEST $this->requestURI\n");

		$debug > 2 and
		debug('request', "\n\nREQUEST CONSTRUCTED - ".$this->requestURI."\n\n");

		# oddness ...
		if ( !isset ( $this->requestQuery ) )
			$this->requestQuery = "?".$_SERVER["QUERY_STRING"];

		if ( !isset( $requestURIRoots ) )
			$requestURIRoots = Array( '/' );

		//	find the request root URI
		foreach ( $requestURIRoots as $u )
		{
			$u = stripDoubleSlash( "$u/" );

			if ( startsWith( $this->requestURI, $u ) )
			{
				$this->requestBaseURI = $u;
				break;
			}
		}
		if (! isset( $this->requestBaseURI ) )
			$this->requestBaseURI = '/';

		# reconstruct the Request URL:
		{
			$p = (int)$_SERVER['SERVER_PORT'];
			$port = '';
			if ( $p == 80 )				$prot = 'http';
			elseif ( $p == 443 )	$prot = 'https';
			else { $prot = 'UNKNOWN'; $port = ':'.$p; }

			$url = $prot."://".$_SERVER['SERVER_NAME'].$port;

			$this->requestBaseURL = $url . $this->requestBaseURI;
			$this->requestURL = $url . $this->requestURI;
		}

		$this->requestRelURI =
			#substr( $this->requestURI, strlen( $this->requestBaseURI ) );
			substr( $requestOrigURI, strlen( $this->requestBaseURI ) );

		$this->requestPathURI = stripDoubleSlash(
			endsWith( $this->requestURI, "/" )
				? $this->requestURI
				: pathinfo( $this->requestURI, PATHINFO_DIRNAME )."/"
		);

		$this->requestRelPathURI =
			substr( $this->requestPathURI, strlen( $this->requestBaseURI ) );

		$this->requestFileURI =
			substr( $this->requestURI, strlen( $this->requestPathURI ) );

		$this->requestLang = isset( $_REQUEST["l"] ) ? $_REQUEST["l"] : 'en';
		$this->slashmode = isset( $_REQUEST["psp:slashmode"] ) ? $_REQUEST["psp:slashmode"] : null;
		$this->slashpath = isset( $_REQUEST["psp:slashpath"] ) ? $_REQUEST["psp:slashpath"] : null;
		$this->slashpage = isset( $_REQUEST["psp:slashpage"] ) ? $_REQUEST["psp:slashpage"] : null;

		if ( $debug > 0 )
		{
			debug( 'request', "slashmode:         $this->slashmode" );
			debug( 'request', "slashpage:         $this->slashpage" );
			debug( 'request', "slashpath:         $this->slashpath" );
			debug( 'request', "requestURI:        $this->requestURI" );
			debug( 'request', "requestBaseURI:    $this->requestBaseURI" );
			debug( 'request', "requestPathURI:    $this->requestPathURI");
			debug( 'request', "requestRelURI:     $this->requestRelURI" );
			debug( 'request', "requestRelPathURI: $this->requestRelPathURI" );
			debug( 'request', "requestFileURI:    $this->requestFileURI" );
			debug( 'request', "requestQuery:      $this->requestQuery" );
			debug( 'request', "referrer:          ".gad( $_SERVER, "HTTP_REFERER", '-') );

			// no closures...
			$actions = implode( ' ',
				array_map( "Request::a",
					array_filter( array_keys( $_REQUEST ), "Request::b"
					)
				)
			);
			if ( !empty( $actions ) )
				debug( 'request', "actions: $actions" );
		}
	}

	// no closures...
	private static function a($a) { return substr($a, 7); }
	private static function b($a) { return startsWith( $a, 'action:' ); }
}


abstract class RequestHandler
{
	private static $requestHandlers = Array();

	public static function init( $requestURIRoots, $staticContent, $redir )
	{
		ob_start();

		global $psp_custom_handlers;
		foreach ( gd( $psp_custom_handlers, array() ) as $name => $class )
		{
			# be sure to set up an __autoload function!
			self::add( $name, new $class() );
		}
		self::add( 'log', new LogRequestHandler() );
		self::add( 'redirect', new RedirectRequestHandler( $redir ) );
		self::add( 'static', new StaticRequestHandler( $staticContent ) );
		self::add( 'content', new ContentRequestHandler( Array('content/') ) );
		self::add( 'dynamic', new DynamicRequestHandler() );

		$request = new Request( $requestURIRoots );
		return $request;
	}

	public static function add( $label, $function )
	{
		self::$requestHandlers[ $label ] = $function;
	}

	public static function handle( $request )
	{
		global $debug;

		$debug > 2 and
		debug( 'request', "Request $request->requestURI" );

		foreach ( self::$requestHandlers as $k => $h )
		{
			$debug > 1 and
			debug( 'request', "delegate handler $k" );
			if ( $h->_handle( $request ) )
			{
				#ob_flush();flush();return;
				#exit;
				return;
			}
		}
	}

	protected abstract function _handle( $request );

	public static function notFound( $request )
	{
		debug( 'request', "404 not found: " . $request->requestBaseURI . "404.html" );
		header( "HTTP/1.1 404 Not found" );

		global $requestURIRoots;
		# re-use the same Request object, just update the URI.
#		$r = new Request( $requestURIRoots );
#		$r->requestBaseURI = $request->requestBaseURI;
		$r = $request;
		$r->requestRelURI = "404.html";
		RequestHandler::handle( $r );

		exit;

		//header( "Status: 404 Not found" );

		#header( "Location: $request->requestBaseURI"."404.html" );
	}

	public static function sendFile( $fn )
	{
		$lmreq = gad( $_SERVER, "HTTP_IF_MODIFIED_SINCE", null );
		$lmtime = filemtime( $fn );

		if ( isset( $lmreq ) )
		{
			$lmreq = strtotime( $lmreq );

			if ( $lmtime == $lmreq )
			{
				header( "HTTP/1.1 304 Not modified" );
				exit;
			}
		}

		#debug( "200 okay $fn" );
		header( "HTTP/1.1 200 OK", false );
		// i used to send both, but this no worky in < 5.3
		//header( "Status 200 OK" );

		// #clearstatcache()


		header( "Last-Modified: " .gmdate( "D, d M Y H:i:s", $lmtime ) . " GMT" );

		sendmime( $fn );
		readfile( $fn );
	}
}

class RedirectRequestHandler extends RequestHandler
{
	public function __construct( $redir )
	{
		$this->regexp = '@^(' . implode( '|^', array_keys( $redir ) ) . ')@';
		$this->redir = $redir;
	}

	public function _handle( $request )
	{
		global $debug;

		$matches;
		if ( preg_match( $this->regexp, $request->requestRelPathURI, $matches ) )
		{
			if ( $debug )
			debug( 'request', "[redir] match $this->regexp: ".$matches[0] );
			$request->requestRelPathURI = "";
			$request->requestRelURI =
			$request->requestFileURI = $this->redir[ $matches[1] ];
			return false;
		}

		if ( $debug )
		debug( 'request', "[redir] no match for $this->regexp" );
		return false;
	}
}


class LogRequestHandler extends RequestHandler
{
	public function __construct()
	{
	}

	public function _handle( $request )
	{
		$dir = DirectoryResource::findFile( "db" );
		if ( isset( $dir ) )
		{
			$accessLog = "$dir/access.log";
			$accessLogXML = "$dir/access.xml";

			// .............. RA Host Time Reqst 200 size useragent
			$line = sprintf( "%s %s %s [%s] \"%s\" %d %d \"%s\"\n",
				$_SERVER["REMOTE_ADDR"],
				gad( $_SERVER, "HTTP_HOST", '-'),
				gad( $_SERVER, "HTTP_REFERER", '-'),
				date( "Y-m-d H:i:s O", $_SERVER["REQUEST_TIME"]),
				// apache style date:
				//date( "M/d/Y:H:i:s O", $_SERVER["REQUEST_TIME"]),

				$_SERVER["REQUEST_METHOD"] . " " .
					$_SERVER["REQUEST_URI"] . " " .
					$_SERVER["SERVER_PROTOCOL"],

				0, 0,

				$_SERVER["HTTP_USER_AGENT"] // or $_SERVER[]
			);

			$xmlline = sprintf( "<%s remote='%s' referer='%s' time='%s' host='%s' uri='%s' protocol='%s' agent='%s' code='%d'/>\n",

				$_SERVER["REQUEST_METHOD"],

				$_SERVER["REMOTE_ADDR"],
				gad( $_SERVER, "HTTP_REFERER", '-' ),

				date( "Y-m-d H:i:s O", $_SERVER["REQUEST_TIME"]),
				// apache style date:
				//date( "M/d/Y:H:i:s O", $_SERVER["REQUEST_TIME"]),

				gad( $_SERVER, "HTTP_HOST", '-'),

				$_SERVER["REQUEST_URI"],
				$_SERVER["SERVER_PROTOCOL"],

				$_SERVER["HTTP_USER_AGENT"],

				0, 0
			);

			$xmlline = str_replace( "&", "&amp;", $xmlline );

			file_put_contents( $accessLog, $line, LOCK_EX | FILE_APPEND );
			file_put_contents( $accessLogXML, $xmlline, LOCK_EX | FILE_APPEND );
		}
		return false;
	}
}



class StaticRequestHandler extends RequestHandler
{
	public function __construct( $staticContent )
	{
		$this->regexp = '@^' . implode( '|^', $staticContent ) . '@';
	}

	public function _handle( $request )
	{
		global $debug;

		if ( preg_match( $this->regexp, $request->requestRelURI ) )
		{
			$fn = DirectoryResource::findFile( $request->requestRelURI );

			if ( is_file( $fn ) )
			{
				ob_end_clean();
				#ob_end_flush();
				RequestHandler::sendFile( $fn );
			}
			else
			{
				ob_end_clean(); # ob_end_flush();
				RequestHandler::notFound( $request );
				exit;
			}

			return true;
		}
		if ( $debug )
		debug( 'request', "[static] no match for $this->regexp" );
		return false;
	}
}

class ContentRequestHandler extends RequestHandler
{
	public function __construct( $dirs )
	{
		$this->regexp = '@^(' . implode( '|', $dirs ) . ')@';
	}

	public function _handle( $request )
	{
		global $debug;
		$matches;

		if ( preg_match( $this->regexp, $request->requestRelURI, $matches ) )
		{
			debug( 'request', "[content] match for $this->regexp" );

			$fn = DirectoryResource::findFile(
				substr ( $request->requestRelURI, strlen( $matches[1] ) ),
				endsWith( $matches[1], '/' )
					? substr( $matches[1], 0, strlen( $matches[1] )-1 )
					: $matches[1]
			);

			if ( is_file( $fn ) )
			{
				ob_end_clean();
				#ob_end_flush();
				RequestHandler::sendFile( $fn );
			}
			else
			{
				ob_end_flush();
				RequestHandler::notFound( $request );
				exit;
			}

			return true;
		}
		if ( $debug )
		debug( 'request', "[content] no match for $this->regexp" );
		return false;
	}
}

class DynamicRequestHandler extends RequestHandler
{
	public function _handle( $request )
	{
		global $debug;

		$in;

		if ( php_sapi_name() == 'cli' )
		{
			$scriptFile;

			foreach ( $argv as $arg )
			{
				if ( !isset( $scriptFile ) )
					$scriptFile = $arg;
				else if ( isset( $in ) )
					ModuleManager::addXSL( $arg );
				else
					$in = $arg;
			}
		}
		else
		{
			$requestDir = pathinfo( $request->requestRelURI, PATHINFO_DIRNAME );
			if ( $requestDir == '.' ) $requestDir = ""; else $requestDir.='/';

			$requestFile = pathinfo( $request->requestRelURI, PATHINFO_FILENAME )
				. '.xml';

			debug( 'request', "File [$requestDir]/$requestFile");

			$in = DirectoryResource::findFile( $requestDir.$requestFile, "content" );

			debug( 'request', "in: ".DirectoryResource::debugFile( $in )
				. ($debug > 2 ? " ($in)":"")
			);

			if ( ! is_file( $in ) )
			{
				// Fallback for plain .html files
				if ( preg_match( "/\.html$/", $request->requestRelURI ) )
				{
					$in = DirectoryResource::findFile( $request->requestRelURI, "content" );
					if ( is_file( $in ) )
					{
						debug( 'request', "Fallback to $in" );
						ob_end_clean(); // explicit clean
						RequestHandler::sendFile( $in );
						exit;
					}
				}
				else
				{
					# HACK FIXME special handler for psp/*.xsl
					# see Resource.php setupPaths - adds a 'psp' resource handler.
					# (only allows from $requestBaseDir)

					# take the requestDir as the type
					$idx = strpos( $requestDir, '/' );
					if ( $idx !== false )
					{
						$t = substr( $requestDir, 0, $idx );
						$requestDir = substr( $requestDir, $idx + 1);

						if ( $t == 'psp' )
						{
							debug( $this, "psp xsl handler triggered" ); # have yet to see output

							$requestFile = pathinfo( $request->requestRelURI, PATHINFO_FILENAME ).'.xsl';

							$in = DirectoryResource::findFile( $requestDir.$requestFile, $t );
							debug ('request', "\n\nFALLBACK TYPE: find($requestDir $requestFile, $t) FILE: $in\n\n");
							if ( is_file( $in ) )
							{
								RequestHandler::sendFile( $in );
								exit;
							}
						}

					}
				}

				RequestHandler::notFound( $request );
				exit;
			}

			$request->requestDir = $requestDir;
			$request->requestFile = $requestFile;
			$request->in = $in;
		}

		ModuleManager::loadModule( "psp" );

		$doc = loadXML( $in );

		$doc = ModuleManager::transform( $doc );
		ModuleManager::deinit();

		ob_end_flush();

		$debug and
		debug('handler', "serialize style=" . $request->style );

		echo serializeDoc( $doc,
			array_reverse(
				DirectoryResource::findFiles(
				$request->style, 'style' )
			)
		);
		return true;

	}
}


?>
