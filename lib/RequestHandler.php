<?php

require_once( "Util.php" );
require_once( "ModuleManager.php" );
require_once( "XML.php" );
require_once( "Resource.php" );

class Request
{
	public $requestURI;				# /WEBROOT/bar/baz.html?foo
	public $requestBaseURI;		# /WEBROOT/
	public $requestRelURI;		# bar/baz.html
	public $requestPathURI;		# /WEBROOT/bar/
	public $requestRelPathURI;# bar/
	public $requestFileURI;		# baz.html
	public $requestQuery;			# ?foo   # '?' included for easy xsl

	public $requestLang;

	public $basedir;

	public $requestDir;
	public $requestFile;
	public $in;

	public $style = "layout.xsl"; # processing instr: <?psp style href=".."

	public function __construct( $requestURIRoots )
	{
		global $debug;

		if ( array_key_exists( "REDIRECT_URL", $_SERVER ) )
		{
			$this->requestURI = $_SERVER["REDIRECT_URL"];
			if ( array_key_exists( "REQUEST_QUERY_STRING", $_SERVER) )
				$this->requestQuery = "?".$_SERVER["REQUEST_QUERY_STRING"];
		}
		else
		{
		if ( !preg_match( "@^(.*?)(\?.*)?$@", $_SERVER["REQUEST_URI"], $matches ) )
			die ("Regexp error");

			$this->requestURI = $matches[1];
			$this->requestQuery = array_key_exists( 2, $matches ) ? $matches[2] :
				# instead of null
				"?".$_SERVER["QUERY_STRING"];

			#override
		}

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
			$requestBaseURI = '/';

		$this->requestRelURI =
			substr( $this->requestURI, strlen( $this->requestBaseURI ) );

		$this->requestPathURI = stripDoubleSlash(
			endsWith( $this->requestURI, "/" )
				? $this->requestURI
				: pathinfo( $this->requestURI, PATHINFO_DIRNAME )."/"
		);

		$this->requestRelPathURI =
			substr( $this->requestPathURI, strlen( $this->requestBaseURI ) );

		$this->requestFileURI =
			substr( $this->requestURI, strlen( $this->requestPathURI ) );

		$this->requestLang = $_REQUEST["l"] or null;

		if ( $debug > 0 )
		{
			debug( 'request', "requestURI:        $this->requestURI" );
			debug( 'request', "requestBaseURI:    $this->requestBaseURI" );
			debug( 'request', "requestPathURI:    $this->requestPathURI");
			debug( 'request', "requestRelURI:     $this->requestRelURI" );
			debug( 'request', "requestRelPathURI: $this->requestRelPathURI" );
			debug( 'request', "requestFileURI:    $this->requestFileURI" );
			debug( 'request', "requestQuery:      $this->requestQuery" );

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
				exit;
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
		$matches;
		if ( preg_match( $this->regexp, $request->requestRelPathURI, $matches ) )
		{
			debug( 'request', "[redir] match $this->regexp: ".$matches[0] );
			$request->requestRelPathURI = "";
			$request->requestFileURI = $this->redir[ $matches[1] ];
			$request->requestRelURI = $request->requestFileURI;
			return false;
		}

		debug( 'request', "[redir] No match for $this->regexp" );
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
		debug( 'request', "[static] no match for $this->regexp" );
		return false;
	}
}

class ContentRequestHandler extends RequestHandler
{
	public function __construct( $dirs )
	{
		$this->regexp = '@^(' . implode( '|^', $dirs ) . ')@';
	}

	public function _handle( $request )
	{
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
						ob_end_clean();
						RequestHandler::sendFile( $in );
						exit;
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
			array_reverse( DirectoryResource::findFiles(
				$request->style, 'style' ) ) );
		return true;

	}
}


?>
