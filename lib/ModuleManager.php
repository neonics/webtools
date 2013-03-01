<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

interface IModule
{
	public function ns();

	public function init();

	public function deinit();


	/**
	 * $xslt->setParameter( ns, name, value );
	 */
	public function setParameters( $xslt );
}

abstract class AbstractModule implements IModule
{
	public $name;
	protected $ns;

	protected function __construct( $name, $ns )
	{
		#$mprefix = strtolower( preg_replace( "/Module$/", "", get_class( $this ) ));
		$this->name = $name;
		$this->ns = $ns;
	}
	public function ns() { return $this->ns; }

	public function init() {}

	public function deinit() {}

	public function setParameters( $xslt ) {}

	protected function isAction( $action )
	{
		return array_key_exists( 'action:' . $this->name . ":$action", $_REQUEST );
	}

	private static $messages;

	public static function messages( $module = null )
	{
		// XXX TODO filter $module
		return isset( self::$messages )
			? self::$messages : new DOMNode();
	}

	protected function message( $msg, $type = null )
	{
		return self::smessage( $msg, $type, $this );
	}

	private static $funcToMod = Array(
		"mail" => "email",
	);
	public static function smessage( $msg, $type = null, $mod = null )
	{
		$lns = "http://www.neonics.com/xslt/layout/1.0";

		if ( !isset( self::$messages ) )
		{
			$l = self::$messages = new DOMDocument();
			$l->appendChild( $l->createElementNS( $lns, 'messages' ) );
			isset( $mod ) and
			$l->documentElement->setAttribute( 'module', $mod->name );
		}
		else
		{
			$l = self::$messages;
		}

		$m = $l->documentElement->appendChild(
			$l->createElementNS( $lns, 'message' ) );

		if ( ! is_array( $msg ) )
			$msg = Array($msg);

		$modname;
		if (! isset( $mod ) )
		{
			$matches;
			if ( preg_match( "/^(.*?\(.*?<a href=['\"]function\.(.*?)['\"]>.*?<\/a>\s*\]:\s*)/",
				$msg[0], $matches ) )
			{
				$modname = $matches[2];
				$modname = gad( self::$funcToMod, $modname, $modname );
				$msg[0] = substr( $msg[0], strlen( $matches[1] ) );
				$msg[0] = str_replace( "&quot;", "'", $msg[0] );
				$msg = Array( $msg[0] );
			}
		}
		else
			$modname = $mod->name;

		isset( $modname ) and $m->setAttribute( 'module', $modname );
		isset( $type ) and $m->setAttribute( 'type', $type );

		foreach ( $msg as $message )
		{
			$m->appendChild( $l->createTextNode( $message ) );
		}

		debug( 'core', 'add message: ' . str_replace( "<", "&lt;", $l->saveXML( $m ) ) );
		return $m;
	}

	protected function errorMessage( $msg )
	{
		return $this->message( $msg, 'error' );
		#return DOMDocument::loadXML( "<message type='error'".
		#	" xmlns='" . $this->ns . "'>$msg</message>" );
	}
}


class ModuleManager
{
	public static $modules = Array();
	private static $stylesheets = Array();
	private static $xsltFunctions = Array();

	static function loadModule( $m )
	{
		global $debug;

		if ( array_key_exists( $m, self::$modules ) )
		{
			debug( 'module', "duplicate module $m" );

			// bump stylesheet if any to end of list
			if ( array_key_exists( "sheet", self::$modules[$m] ) )
			{
				foreach ( self::$stylesheets as $i => $z )
				{
					if ( $z[0] == self::$modules[$m]['sheet'] )
					{
						array_splice( self::$stylesheets, $i, 1 );
						self::$stylesheets[] = $z;
						debug( 'module', "sheet " . self::$modules[$m]['sheet']
							. " bumped from #$i to end" );
						break;
					}
				}
			}


			// XXX if no return here, then recursion due to 'addXSL' below.
			// To have duplicate sheets enabled, remove the return and the addXSL call
			// however this leads to way too many transformation calls
			return;
		}
		else
		{
			debug( 'module', "[module $m]" );

			self::$modules[ $m ] = Array();

			$pspLogic = DirectoryResource::findFile( "$m.php", 'logic' );

			if ( isset( $pspLogic ) )
			{
				self::$modules[$m]["logic"] = $pspLogic;

				ob_start();
				include_once( $pspLogic );
				ob_end_clean();

				$GLOBALS += get_defined_vars();
			}

			// the code below is not included within the conditional above
			// as the core may provide pre-loaded modules.

			// if it is a class, instantiate it..
			$modClass = $m."_class";

			if ( isset( $$modClass ) )
			{
				if ( $debug > 2 )
					debug( 'module', "Instantiating module $m" );

				self::$modules[$m]["instance"] = new $$modClass();

				self::createProxies( $m );
			}

			$f = $m."_init";
			if ( function_exists( $f ) )
			{
				if ( $debug > 2 )
				debug( 'module', "Initializing module $m" );
				$f();
			}
		}

		self::addXSL( "$m.xsl", $m  );

		if ( $debug > 1 )
		{
			debug( 'module', "sheets:" );
			foreach ( self::$stylesheets as $s )
			{
				debug( 'module', "  sheet " . $s[0] );

				for ($i=1; $i<count($s); $i++)
					debug( 'module', "        ".$s[$i]);
			}
		}
	}

	private static function createProxies( $m )
	{
		global $debug;

		$mi = self::$modules[ $m ][ "instance" ];

		$rc = new ReflectionClass( $mi );

		$methods = $rc->getMethods( ReflectionMethod::IS_PUBLIC );

		foreach ( $methods as $method )
		{
			if ( $method->class == get_class( $mi )
				&& substr( $method->name, 0, 1) != '_' )
			{
				self::exportMethod( $mi, $method );
			}
		}
	}

	// Due to php<5.3 not supporting closures:

	private static function makeFuncStr($arg)
	{
		global $debug;

		$dflt = $arg->isOptional() ? gd( $arg->getDefaultValue(), 'null') : null;

		$a =
			($arg->isPassedByReference() ? "&" : "").
			'$'.$arg->name.
			(isset($dflt)?" = $dflt":"")
			;

		if ( $debug > 2 )
			debug( 'module', "  Arg: $arg: '$a'" );
		return $a;
	}

	private static function makeArgStr( $arg )
	{
			return '$'.$arg->name;
	}

	protected static function exportMethod( $mi, $method )
	{
		global $debug;

		$fname = $mi->name."_".$method->name;

		if ( $debug > 2 )
		debug( 'module', "Exporting ".$method->class ."::".$method->name ." as $fname" );

		// ReflectionMethod::getClosure( $instance )

		// php < 5.3 has no closures....
		$fargs = join( ", ", array_map( "ModuleManager::makeFuncStr", $method->getParameters() ) );

		$margs = join(", ", array_map( "ModuleManager::makeArgStr",
			$method->getParameters() ) );

		$code = <<<EOF
		function $fname( $fargs ) {
			return ModuleManager::\$modules[ '$mi->name' ]["instance"]
				->$method->name( $margs );
		}
EOF;
		if ( $debug > 3 )
		debug( 'module', "Code:<pre style='color:blue'>$code</pre>" );

		eval( $code );

		ModuleManager::registerFunction( $fname );
	}


	public static function setParameters( $xslt, $sheet )
	{
		global $debug;

# commented out because of warning that $sheet is an array
#		$debug > 2 and
#		debug( 'module', "  setParameters $sheet");

		foreach ( array_keys( self::$modules ) as $m )
		{
			$f = $m . "_setParameters";
			if ( function_exists( $f ) )
			{
				if ( $debug > 2 )
				debug( 'module', "    module $m" );
				$f( $xslt );
			}
			else
			if ( $debug > 2 )
			debug ("    module $m: no function");
		}
	}

	public static function registerFunction( $fname )
	{
		self::$xsltFunctions[] = $fname;
	}

	public static function registerFunctions( $xslt )
	{
		global $debug;

		$debug > 2 and
		debug( 'module', "Registering functions: " . join(', ', self::$xsltFunctions ) );
			$xslt->registerPHPFunctions(
				self::$xsltFunctions
			);
	}


	private static $sheetToModule = array();

	public static function addXSL( $sheet, $m = null )
	{
		global $debug;
		$z = DirectoryResource::findFiles( $sheet, $m==null?'style':'logic' );
		$z = array_reverse( $z );

		if ( empty( $z ) )
			return;

		// TODO: blend/merge the stylesheets.
		// Earlier tests showed that loading the sheets ASAP does not work.
		// TODO: check XSLTProcessor->importStylesheet
		// FIXME: check stylesheets handled as Array

		if ( count( self::$stylesheets ) == 0 ||
			count( array_diff_assoc( $z, self::$stylesheets[0] ) ) > 0
			#$z[0] == self::$stylesheets[0][0]
		)
		{
			debug( 'module',
				($m==null?"":"module $m ").
				($m==null?'style ':'logic ') .
				"bridge-sheet " .
				($debug > 2 ? $z[0] : $sheet)
			);

			//array_unshift( self::$stylesheets, $z );
			// XXX FIXME MAJOR CHANGE TODO CHECK
			array_push( self::$stylesheets, $z );

			if ( isset ( $m ) )
			{
				self::$sheetToModule[$sheet] = $m;
				self::$modules[$m]["sheet"] = $z[0];
			}
ob_flush();
			loadXSL( $z );
		}
		else
		{
			debug( 'module', " x Duplicate sheet ".$z[0]);
			if ( $debug > 3 ) {
			debug( 'module', "    Sheets: ");
			foreach ( self::$stylesheets as $q )
				debug ("      ". $q[0]);
			}
		}

	}


	/**
	 * 'xsp:deinit'
	 */
	public static function deinit()
	{
		foreach ( self::$modules as $m => $mod )
		{
			$f = $m."_deinit()";

			if ( function_exists( $f ) )
			{
				$f();
			}
		}
	}

	public static function transform( $doc )
	{
		global $debug;

		while ( count( self::$stylesheets ) > 0 )
		{
			$sheets = array_shift( self::$stylesheets );
			$doc = transform( $doc, $sheets );
			dumpXMLFile( $doc, $sheets[0] );
		}

		debug( 'module', "TRANSFORM DONE");

		return $doc;
	}

	public static function processDoc( $doc )
	{
		global $debug;

		$pspXSL = ModuleManager::$modules[ "psp" ][ "sheet" ];
		if ( isset( $pspXSL ) && $pspXSL != $doc->documentURI )
		{
			$debug > 2 and
			debug( 'xml', "transforming ".$doc->documentURI . " with $pspXSL" );

			ModuleManager::$modules[ "psp" ][ "instance" ]->curDoc = $doc;

			$doc = transform( $doc, $pspXSL );

			ModuleManager::$modules[ "psp" ][ "instance" ]->curDoc = null;

			$debug > 3 and
			dumpXMLFile( $doc, $pspXSL );
		}

		return $doc;
	}


	public static function errorMessage( $a, $b = null )
	{
		$msg = isset( $b ) ? $b : $a;
		$mod = isset( $b ) ? $a : $b;
		AbstractModule::smessage( $msg, 'error', $mod );
	}
}

?>
