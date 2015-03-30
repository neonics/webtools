<?php

class Cache {

	private static $singleton;

	static function init() {
		if ( self::$singleton )
			return;

		if ( php_sapi_name() == 'cli' )
		{
			$cd = dirname( $_SERVER['PWD'] .'/' . $_SERVER['SCRIPT_FILENAME'] );

			// detect webtools or client site documentroot
			$i=0;
			while ( !empty( $cd ) && !file_exists( "$cd/handle.php" ) && $i++<20 )
				$cd = dirname( $cd );

			if ( empty( $cd ) ) throw new Exception(__CLASS__.": cannot determine cache directory" );

			$cd .= "/tmp";
		}
		else
			$cd = $_SERVER['DOCUMENT_ROOT'] . "/tmp";

		if ( ! is_dir( $cd ) )
			throw new Exception( __CLASS__ . ": determined cache directory '$cd' is not a directory" );


		echo "!!! setting cacheDir to $cd\n";
		self::$singleton = new Cache( $cd );
	}

	private $cachedir;
	private $indexFile;

	private function __construct( $cachedir ) {
		$this->cachedir = $cachedir;
		$this->indexFile = "$this->cachedir/cache-".hash('md5', __CLASS__).'-index.json';
		// using hash to prevent illegal chars due to namespace
	}


	private function updateIndex( $cacheKey, $timeout, $cacheFile )
	{
		$index = make_array( file_exists( $this->indexFile ) ? json_decode( file_get_contents( $this->indexFile ), true ) : [] );
		$index[ $cacheKey ] = [ 'timeout' => $timeout, 'cachefile' => $cacheFile ];
		file_put_contents( $this->indexFile, json_encode( $index, JSON_PRETTY_PRINT ) );
	}



	public static function call( $cacheKey, $timeout, $callable ) {
		if ( ! self::$singleton ) throw new Exception( __CLASS__ . " not initialized" );
		if ( ! is_callable( $callable ) ) throw new Exception( __CLASS__ . "." . __METHOD__ . " argument \$callable not callable" );

		$cacheFile = self::$singleton->cachedir .'/'. preg_replace( "@[^a-zA-Z0-9_-]@", "_", $cacheKey );

		if ( file_exists( $cacheFile ) && time() - filemtime( $cacheFile ) < $timeout )
			return unserialize( file_get_contents( $cacheFile ) );
		else
		{
			file_put_contents( $cacheFile, serialize( $ret = $callable() ) );
			self::$singleton->updateIndex( $cacheKey, $timeout, $cacheFile );

			return $ret;
		}
	}
}


Cache::init();
