<?php
if ( ! defined( 'CLASSLOADER' ) )
	define( 'CLASSLOADER', 'ClassLoader' );

require_once( __DIR__ . "/Util.php" );
require_once( __DIR__ . "/Debug.php" );

class ClassNotFoundException extends Exception {}
class ClassLoader
{
	var $_logname = "class";	# debug category

	public function __construct() { debug( $this, "init" );
	}

	public function load( $className )
	{
		$className = self::resolveNS( $className );

		// HACK for Amazon:
		if ( strpos( $className, "MarketplaceWebService" ) === 0 )
			$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		// HACK for Google:
		elseif ( strpos( $className, "Google" ) === 0 )
			$filePath = join( '/', array_slice( explode( '_', $className), 0, 3 ) ).'.php';
		else
			$filePath = $className . '.php';

		debug( $this, "resolvedNS( $className ): $filePath" );
		#echo "<pre> - try $filePath</pre>";

		// reverse so lib/ gets scanned before .
		$includePaths = array_reverse( explode(PATH_SEPARATOR, get_include_path()) );

		foreach($includePaths as $includePath)
		{
			debug( $this, "  try includePath $includePath -- for $filePath: " .
				$includePath . DIRECTORY_SEPARATOR . $filePath
			);

			if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath))
			{
				#echo "<pre><b>including...$filePath</b>";
				#debug_print_backtrace();
				#echo "</pre>";
				#die("....:");
				require_once $includePath . DIRECTORY_SEPARATOR . $filePath;
				return;
			}

			// HACK for using case sensitive namespaces and lowercase directory
			// NOTE: we aleady assume that the namespaces have been converted to directory names
			//       i.e. '\' => '/' (we can use '/' on '\' systems too)
			if ( strpos( $className, DIRECTORY_SEPARATOR ) !== false )
			{
				//debug( $this, "$className TEST" .strpos( $className, DIRECTORY_SEPARATOR ) .  DIRECTORY_SEPARATOR );
				$filePath_lc = implode( DIRECTORY_SEPARATOR,
					array_map( 'strtolower', array_slice( $els = explode( DIRECTORY_SEPARATOR, $className ), 0, -1 ) )
				) . DIRECTORY_SEPARATOR . array_pop( $els )
				. '.php'
				;
				//echo "<pre>try loading $filePath_lc</pre>";

				if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath_lc))
				{
					require_once $includePath . DIRECTORY_SEPARATOR . $filePath_lc;
					return;
				}
			}


		}

		throw new ClassNotFoundException( "Class not found: $className" );
	}

	protected function resolveNS( $className )
	{
		return str_replace( '\\', DIRECTORY_SEPARATOR, $className );
	}
}


function __autoload( $className ) {
	static $classLoader = null;
	if ( $classLoader == null )
		$classLoader = new CLASSLOADER;
	debug( 'class', "autoloading $className" );
	return $classLoader->load( $className );
}
