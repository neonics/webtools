<?php
/**
 * Directory Resource: overridable path hierarchy.
 *
 * Used to allow several kinds of resources to have sensible defaults
 * and be overridable by a client installation.
 *
 * Built-in, yet overridable, search paths are first initialized:
 *
 * - core content, logic, style ('content/', 'psp/' and 'style/' respectively)
 * - client content logic, style
 * - db/ client, db/ core
 *
 * @author Kenney Westerhof
 */
require_once( 'RequestHandler.php' );

	class DirectoryResource
	{
		// either a fallback-chain and one global resource,
		// or global static access to all resources, iteratable.
		public static $resources = Array();

		public $label;

		public $baseDir;

		public $relPaths = Array();

		public function __construct( $baseDir, $label )
		{
			global $debug;

			$this->label = $label;

			$this->baseDir = getDirectory( $baseDir );
			$debug > 2 and debug( 'resource', "[$label] resource $this->baseDir" );

			array_unshift( self::$resources, $this );

			$this->addResourceRelPath( "default", "" );
		}

		public static function debugFile( $f )
		{
			foreach ( self::$resources as $r )
			{
				if ( startsWith( $f, $r->baseDir ) )
					return "[$r->label] ". substr( $f, strlen( $r->baseDir ) );
			}
			return $f;
		}


		/**
		 * Note: only one of each $type per instance!
		 */
		public function addResourceRelPath( $type, $relPath )
		{
			global $debug;

			$this->relPaths[ $type ] = getDirectory( $relPath );

			$debug > 2 and
			debug( 'resource', "[$this->label] register " . $this->relPaths[ $type ] . " type $type" );
		}

		private static function maybeinit() {
			if ( ! self::$resources )
			{
				global $request, $requestURIRoots, $staticContent, $redir;
				if ( ! isset( $request ) )
					$request = RequestHandler::init( $requestURIRoots, $staticContent, $redir );

				setupPaths( __DIR__ . "/../" ); // XXX update this when moving the file
			}
		}

		public static function findFile( $relFile, $type = "default", $label = null )
		{
			global $debug;

			self::maybeinit();

			$debug > 3 and debug( 'resource', "find $type file '$relFile'" );

			foreach ( self::$resources as $r )
			{
				if ( isset( $label ) && $label != $r->label )
					continue;

				$debug > 3 and
				debug( 'resource', "[$r->label] ? checking Resource: " . $r->baseDir );

				$fn = $r->getFile( $relFile, $type );

				if ( $fn != null )
					return $fn;
			}
			if ( $debug > 0 ) debug( 'resource', " x Resource not found: $type $relFile" );
			return null;
		}

		public static function findFiles( $relFile, $type = 'default' )
		{
			global $debug;

			self::maybeinit();

			$ret = Array();

			$debug > 2 and
			debug( 'resource', "find $type file '$relFile'" );

			foreach ( self::$resources as $r )
			{
				$debug > 2 and
				debug( 'resource', "[$r->label] ? check resource " . $r->baseDir );

				$fn = $r->getFile( $relFile, $type );

				if ( $fn != null )
					$ret[] = $fn;
			}

			if ( count($ret) == 0 )
				if ( $debug > 0 ) debug( 'resource', " ! resource not found: $type '$relFile'" );
			return $ret;
		}

		private function getFile( $relFile, $type )
		{
			global $debug;

			if ( !isset( $this->relPaths[ $type ] ) )
			{
				if ( $debug > 3 )
					debug( 'resource', "Resource '$this->label' does not provide '$type'");
				return;
			}

			$rp =  gd( $this->relPaths[ $type ], "" );

			$fn = $this->baseDir . $rp . $relFile;

			if ( $debug > 3 )
			{
				debug( 'resource', "    - Label:    $this->label");
				debug( 'resource', "    - Base:    $this->baseDir");
				debug( 'resource', "    - Type:    $type");
				debug( 'resource', "    - RelPath: $rp");
				debug( 'resource', "    - RelFile: $relFile");
				debug( 'resource', "    * Check filename " . $fn );
			}

			if ( file_exists( $fn ) )
			{
				$debug > 2 and
				debug( 'resource', "[$this->label] v found $type $relFile");
				return $fn;
			}
			else
				$debug > 2 and
				debug( 'resource', "[$this->label] x not found: $fn [relfile:$relFile]" );
			return null;
		}
	}


	function setupPaths( $baseDir )
	{
		global $request, $requestBaseDir, $requestBaseUri, $pspBaseDir,
			$pspContentDir, $pspLogicDir, $pspStyleDir,
			$contentDir, $logicDir, $styleDir;

		$requestBaseDir = str_replace( '\\', '/', $requestBaseDir );
		$baseDir = str_replace( '\\', '/', $baseDir );

		$pspContentDir = gd( $pspContentDir, 'content' );
		$pspLogicDir = gd( $pspLogicDir, 'psp' );
		$pspStyleDir = gd( $pspStyleDir, 'style' );

		$pspBaseDir = $baseDir;

		$br = new DirectoryResource( $pspBaseDir, 'core' );
		$br->addResourceRelPath( 'content', $pspContentDir );
		$br->addResourceRelPath( 'logic', $pspLogicDir );
		$br->addResourceRelPath( 'style', $pspStyleDir );
		$br->addResourceRelPath( 'pages', "pages" );

		if ( isset( $requestBaseDir ) )
		{
			if ( $requestBaseDir != $pspBaseDir )
			{
				$rr = new DirectoryResource( $requestBaseDir, $request->requestBaseURI );
				$rr->addResourceRelPath( 'content', gd( $contentDir, $pspContentDir ) );
				$rr->addResourceRelPath( 'logic', gd( $logicDir, $pspLogicDir ) );
				$rr->addResourceRelPath( 'style', gd( $styleDir, $pspStyleDir ) );
				# XXX FIXME - not sure what this is for. See RequestHandler.php in same commit)
				debug('!!!', "add psp dir $requestBaseDir - $logicDir - $pspLogicDir");
				# . "/psp";#DirectoryResource::findFile( "psp" );
				$dr = new DirectoryResource( $requestBaseDir, 'psp' );
				$dr->addResourceRelPath( 'psp', 'psp' );#gd( $logicDir, $pspLogicDir ) );
			}
		}
		else
			$requestBaseDir = $pspBaseDir;

		$request->basedir = $requestBaseDir;

		// also add db/content provider
		$dbdir = DirectoryResource::findFile( "db" );
		if ( isset( $dbdir ) )
		{
			debug('!!!', "add db dir $dbdir - $contentDir - $pspContentDir");
			$dr = new DirectoryResource( $dbdir, 'db' );
			$dr->addResourceRelPath( 'content', gd( $contentDir, $pspContentDir ) );
		}

		$tmpdir = DirectoryResource::findFile( 'tmp' );
		if ( isset( $tmpdir ) )
		{
			$tr = new DirectoryResource( $tmpdir, 'tmp' );
		}
	}


	function relativePath( $from, $to )
	{
		$pba = explode( "/", rtrim( str_replace( "\\", "/", $to  ) ) );
		$rba = explode( "/", rtrim( str_replace( "\\", "/", $from) ) );

		while ( count($pba)>0 && count($rba) >0 && $pba[0] == $rba[0] )
		{
				array_shift( $pba );
				array_shift( $rba );
		}

		$a= str_pad( "", count( $rba ) * 3, '../' ).implode( "/", $pba );
	}

	function file_to_uri( $path )
	{
		return "file:///" . str_replace( "\\", "/", $path );
	}

?>
