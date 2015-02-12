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
		global $pspNS, $request, $requestURI, $requestPathURI;
		global $requestBaseURI;
		global $theme;

		$xslt->setParameter( $pspNS, "slashmode", $request->slashmode );
		$xslt->setParameter( $pspNS, "slashpage", $request->slashpage );
		$xslt->setParameter( $pspNS, "slashpath", $request->slashpath );
		$xslt->setParameter( $pspNS, "requestURL", $request->requestURL );
		$xslt->setParameter( $pspNS, "requestBaseURL", $request->requestBaseURL );
		$xslt->setParameter( $pspNS, "requestURI", $request->requestURI );
		$xslt->setParameter( $pspNS, "requestPathURI", $request->requestPathURI );
		$xslt->setParameter( $pspNS, "requestBaseURI", $request->requestBaseURI );
		$xslt->setParameter( $pspNS, "requestDir", $request->requestDir );
		$xslt->setParameter( $pspNS, "requestFile", $request->requestFile );
		$xslt->setParameter( $pspNS, "requestQuery", $request->requestQuery );
		$xslt->setParameter( $pspNS, "requestPage", preg_replace( "/\.xml$/", "", $request->requestFile ) );
		$xslt->setParameter( $pspNS, "theme", $theme );

		$xslt->setParameter( "", "lang", $request->requestLang);
		$xslt->setParameter( $pspNS, "lang", $request->requestLang);
	}

	public function init()
	{
		Session::start();

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

	public function arg( $key, $default = null )
	{
		return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : $default;
	}

	/**
	 * Evaluate an <xsp:expr>PHP code</xsp:expr>
	 * Typically a PHP expression is expected, unless the code contains "return",
	 * in which case a list of statements is accepted. For example:
	 *
	 *   <psp:expr>global $var; return $var;</psp:expr>
	 *
	 */
	public function expr( $data )
	{
		debug('psp', "EVAL: $data");
		$ret = eval( strpos($data, "return")===false? "return $data;" : $data );
		debug('psp', "EVAL result: $ret");

		return $ret;
	}

	public function isaction( $data )
	{
		return isset( $_REQUEST["action:$data"] );
	}

	public function lastmodfile( $name, $type )
	{
		debug('psp', "lastmodfile( $name, $type )" );
		return filemtime( DirectoryResource::findFile( $name, $type ) );
	}

	public function lastmodfilestr( $name, $type )
	{
		debug('psp', "lastmodfilestr( $name, $type )" );
		$mt = filemtime( DirectoryResource::findFile( $name, $type ) );

		# http://www.w3.org/TR/NOTE-datetime
		$str = strftime( "%Y-%m-%dT%H:%M:%S", $mt);

		$dtz = new DateTimeZone( date_default_timezone_get() );
		$tzo = $dtz->getOffset( new DateTime( $str ) );	# lame!

		return $str . ( $tzo==0 ? 'Z' :
			sprintf("%s%02s:%02s", $tzo>=0?'+':'-', $tzo / 3600, $tzo % 3600 ) );
	}

	public function slashpath( $data )
	{
		global $request;
		return ( strlen( $data ) < strlen( $request->slashpath )
				? strpos( $request->slashpath, $data.'/' )
				: strpos( $request->slashpath, $data )
		) === 0;
	}

	public function slasharg( $data, $one=1 )
	{
		global $request;
		$idx = strlen( $data ) < strlen( $request->slashpath )
				? strpos( $request->slashpath, $data.'/' )
				: strlen( $data ) == strlen( $request->slashpath)
				? false
				: strpos( $request->slashpath, $data );
		if ( $idx === 0 )
		{
			$idx2 =
				strpos( $request->slashpath, '/', strlen( $data ) + 1 );
			if ( $idx2 === false || $one == 0 )
			{
				return substr( $request->slashpath, strlen( $data )+1 );
			}
			else
			{
				return substr( $request->slashpath, strlen( $data )+1, $idx2 - strlen( $data) - 1 );
			}
		}
		else
			return null;
	}


	public function variable( $name, $val = null )
	{
		static $varhash = array();
		debug('psp', "variable( $name, $val )" );
		if ( defined( $val ) ) $varhash[ $name ] = $val;
		return isset( $varhash[ $name ] ) ? $varhash[ $name ] : false;
	}

	/**
	 * Called using <xsl:value-of select="php:function('psp_module', 'modulename', .)"/>
	 *
	 * @param string $a modulename
	 * @param array $b array of DOMElement (why array? XSLT does this)
	 * @return string returnvalue required due to <xsl:value-of/>.
	 */
	public function module( $modname, $args = null )
	{
		debug("psp:module - args: <pre>".htmlentities(print_r($args,1))."</pre>");
		// verify $args:
		if ( $args !== null )
		{
			if ( is_array( $args ) && count( $args ) == 1 && is_object( $args[0] ) && get_class( $args[0] ) == 'DOMElement' )
			{
				// ok.
				// Apply psp.xsl (processDoc).
				//
				// (Passing the result of xsl:apply-templates via a xsl:variable to the function as such in psp.xsl:
				//   <xsl:variable name="arg"><xsl:apply-templates/></xsl:variable>
				//   <xsl:apply-templates select="psp:function('module',string(@name), $arg)"/>
				// would be better, but the value received through this mechanism is a string, not
				// a DOMNodeList).
				// NOTE: another solution would be to split xsp.xsl and apply only certain templates
				// in sequence (such as first a global xsp:expr), however an <xsp:if><xsp:else> would not
				// need to be evaluated. Further, the sequence would be lost, and side effects disordered.

				$doc = new DOMDocument();
				// The loadModule will ignore the container element and only process child elements,
				// so we can name the element as we wish:
				$doc->appendChild( $doc->createElement( "arg" ) );
				// Only append the children, otherwise there is recursion (as the top node is <psp:module>).
				for ( $i = 0; $i < $args[0]->childNodes->length; $i++)
					$doc->documentElement->appendChild( $doc->importNode( $args[0]->childNodes->item($i), 1 ) );
				$doc = ModuleManager::processDoc( $doc ); // apply psp.xsl, resolving any nested <xsp:expr> etc:
				$args[0] = $doc->documentElement; // put it back

			}
			else
			{
				$this->errorMessage( "load module '$modname': illegal argument (type ".gettype($args).": ".htmlentities($args).")" );
			}
		}

		return ModuleManager::loadModule( $modname, $args );
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
							{
								$this->nomerge[] = $this->curDoc->documentURI;
								debug( 'psp', "Marking as 'do not merge': " . $this->curDoc->documentURI );
							}
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

	public function xsl_uri( $href )
	{
		global $pspBaseDir;
		return file_to_uri( $pspBaseDir ) . '/' . $href;
	}

	public function xml_uri( $href, $type )
	{
		return DirectoryResource::findFile( $href, $type );
	}

	public function xml_include( $href, $type )
	{
		$doc = new DOMDocument();
		$doc->load( DirectoryResource::findFile( $href, $type ) );
		return $doc->documentElement;
	}

	/**
	 * same as xml_include, except this one will evaluate {$VAR} by substituting
	 * the request variable $request->VAR.
	 */
	public function xml_include_eval( $href, $type )
	{
		$content = file_get_contents( DirectoryResource::findFile( $href, $type ) );
		$content = preg_replace_callback(
			'/{\$([^}]+)}/',
			function( $m ){ global $request; return gd( $request->$m[1], null ); },
			preg_replace( '/<\?php[^?]+\?>/', "", $content )	// strip php code which may use {$..}
		);
		$doc = new DOMDocument();
		$doc->loadXML( $content );
		return $doc->documentElement;
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
