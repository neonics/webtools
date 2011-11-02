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

		$xslt->setParameter( $pspNS, "slashmode", $slashmode );
		$xslt->setParameter( $pspNS, "requestURI", $request->requestURI );
		$xslt->setParameter( $pspNS, "requestPathURI", $request->requestPathURI );
		$xslt->setParameter( $pspNS, "requestBaseURI", $request->requestBaseURI );
		$xslt->setParameter( $pspNS, "requestDir", $request->requestDir );
		$xslt->setParameter( $pspNS, "requestFile", $request->requestFile );
		$xslt->setParameter( $pspNS, "requestQuery", $request->requestQuery );
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
					}
				}
				break;

			case "xml-stylesheet":
				ModuleManager::addXSL( $hash["href"] );
				break;

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
