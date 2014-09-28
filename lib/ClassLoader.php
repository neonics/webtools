<?php
if ( ! defined( 'CLASSLOADER' ) )
	define( 'CLASSLOADER', 'ClassLoader' );

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
				require_once $includePath . DIRECTORY_SEPARATOR . $filePath;
				return;
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
