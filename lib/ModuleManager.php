<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

interface IModule
{
	public function ns();

	public function init();

	public function deinit();

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

	public static function messages()
	{
		return isset( self::$messages )
			? self::$messages : new DOMNode();
	}

	protected function message( $msg, $type = null )
	{
		$lns = "http://www.neonics.com/xslt/layout/1.0";

		if ( !isset( self::$messages ) )
		{
			$l = self::$messages = new DOMDocument();
			$l->appendChild( $l->createElementNS( $lns, 'messages' ) );
			$l->documentElement->setAttribute( 'module', $this->name );
		}
		else
		{
			$l = self::$messages;
		}

		$m = $l->documentElement->appendChild( 
			$l->createElementNS( $lns, 'message' ) );

		$m->setAttribute( 'module', $this->name );
		isset( $type ) and $m->setAttribute( 'type', $type );
		$m->appendChild( $l->createTextNode( $msg ) );

		debug( 'core', 'add message: ' . $l->saveXML( $m ) );
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

		if ( $debug > 2 )
		{
			debug( 'module', "module $m" );
			foreach ( self::$stylesheets as $s )
			{
				debug( 'module', "  sheet " . $s[0] );
				
				for ($i=1; $i<count($s); $i++)
					debug( 'module', "      ".$s[$i]);
			}
		}
	}
	
	private function createProxies( $m )
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

		$dflt = $arg->isOptional()? $arg->getDefaultValue() : null;

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


	public function setParameters( $xslt, $sheet )
	{
		global $debug;

		$debug > 2 and
		debug( 'module', "  setParameters $sheet");

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

	public function registerFunction( $fname )
	{
		self::$xsltFunctions[] = $fname;
	}

	public function registerFunctions( $xslt )
	{
		global $debug;

		$debug > 2 and
		debug( 'module', "Registering functions: " . join(', ', self::$xsltFunctions ) );
			$xslt->registerPHPFunctions(
				self::$xsltFunctions
			);
	}


	private static $sheetToModule = array();

	public function addXSL( $sheet, $m = null )
	{
		global $debug;
		$z =  DirectoryResource::findFiles( $sheet, $m==null?'style':'logic' );
		$z = array_reverse( $z );

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

			array_unshift( self::$stylesheets, $z );
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
		#loadXSL( $sheet );

		if ( isset ( $m ) )
		{
			self::$sheetToModule[$sheet] = $m;
			self::$modules[$m]["sheet"] = $z[0];
		}
	}


	/**
	 * 'xsp:deinit'
	 */
	public function deinit()
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

	public function transform( $doc )
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
}

?>
