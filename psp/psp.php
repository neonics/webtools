<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

class PSPModule extends AbstractModule
{
	public function __construct()
	{
		parent::__construct( 'psp', "http://neonics.com/2011/psp" );

		eval( 'function psp_messages($module=null) { return AbstractModule::messages($module); }' );
		ModuleManager::registerFunction( 'psp_messages' );
	}

	public function setParameters( $xslt )
	{
		global $pspNS, $request, $requestURI, $requestPathURI, $slashmode;
		global $requestBaseURI;
		global $theme;

		$xslt->setParameter( $pspNS, "slashmode", $slashmode );
		$xslt->setParameter( $pspNS, "requestURI", $request->requestURI );
		$xslt->setParameter( $pspNS, "requestPathURI", $request->requestPathURI );
		$xslt->setParameter( $pspNS, "requestBaseURI", $request->requestBaseURI );
		$xslt->setParameter( $pspNS, "requestDir", $request->requestDir );
		$xslt->setParameter( $pspNS, "requestFile", $request->requestFile );
		$xslt->setParameter( $pspNS, "requestQuery", $request->requestQuery );
		$xslt->setParameter( $pspNS, "requestPage", preg_replace( "/\.xml$/", "", $request->requestFile ) );
		$xslt->setParameter( $pspNS, "theme", $theme );
		if ($request->requestLang)
		$xslt->setParameter( "", "lang", $request->requestLang );
	}

	public function init()
	{
		session_start();

		$fn = "errorHandler";

		#if ( false )
		{
		$code = <<<EOF
			function $fn( \$errno, \$errstr, \$errfile, \$errline, \$errcontext )
			{
				switch ( \$errno )
				{
					case E_NOTICE: case E_USER_NOTICE: \$errno = "notice";break;
					case E_WARNING: case E_USER_WARNING: \$errno = "warning";break;
					case E_ERROR: case E_USER_ERROR: default: \$errno = "error";
				}
				AbstractModule::smessage( Array( "\$errstr", "@ \$errfile:\$errline"), "error" );

				echo "<div width='100%' style='color:white; background-color:red;font-weight:bold;'>"
					."[\$errno] \$errstr<br/>"
					."<i>  @ \$errfile:\$errline</i></div><br/>";
			}
EOF;
		eval( $code );
		set_error_handler( $fn );
		}
	}


	/***** PSP Module specifics - XSL called functions *****/

	/** set by ModuleManager::processDoc which applies the psp.xsl
		Architecture here needs some adjustment... consider it WIP.
	*/
	public $curDoc;

	public $nomerge = array();

	public function arg( $key )
	{
		return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : null;
	}

	public function expr( $data )
	{
		return eval( "return $data;" );
	}

	public function isaction( $data )
	{
		return isset( $_REQUEST["action:$data"] );
	}

	public function module( $modname )
	{
		return ModuleManager::loadModule( $modname );
	}

	/**
	 * processing instruction
	 */
	public function pi( $name, $data )
	{
		$hash = $this->attrHash( $data );

		switch ( $name )
		{
			case "psp":
				foreach ( $hash as $attr => $value )
				{
					switch ( $attr )
					{
						case "module":
							ModuleManager::loadModule( $value );
							break;

						case "template":
							break;

						case "merge":
							// correlate value to the xslt currently being processed
							if ( $value == 'no' )
								$his->nomerge[] = $this->curDoc->documentURI;
							debug( 'psp', "Marking as 'do not merge': " . $this->curDoc->documentURI );
							break;

						case "style":
							global $request;
							debug( 'psp', "XXX Style $value" );
							$request->style = $value;
							break;
					}
				}
				break;

			case "xml-stylesheet":
				ModuleManager::addXSL( $hash["href"] );
				break;

		}
	}


	public function accessLogs()
	{
		$al = DirectoryResource::findFile( "access.xml" );
		if ( isset( $al ) )
		{
			$al = "<accessLogs xmlns='".$this->ns."'>\n" . file_get_contents( $al ) . "</accessLogs>";

			$doc = new DOMDocument();
			$doc->loadXML( $al );

			return $doc->documentElement;
		}
	}

	/****** Utility ******/

	/**
	 * converts a string with xml attributes to a hash.
	 *
	 * For instance:
	 * In <tag a="b" c='d'>, the string is: a="b" c='d'
	 * and the resulting hash is ( 'a' => 'b', 'c' => 'd' )
	 */
	private function attrHash( $data )
	{
		// match: foo="bar"  or  foo='bar' => 1=foo, 3=bar

		$hash = array();

		// backreferences like [^\2] doesn't work
		foreach ( array('"', "'") as $quote )
		{
			preg_match_all( '/\s*(.*?)=('.$quote.')([^'.$quote.']*)\2\s*/',
				$data, $matches, PREG_SET_ORDER );

			foreach ( $matches as $match )
			{
				$hash[ $match[1] ] = $match[3];
			}
		}

		return $hash;
	}
}

$psp_class = "PSPModule";

?>
