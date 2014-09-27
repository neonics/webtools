<?php

	function filename( $s )
	{
		return substr( $s, 1+strlen(pathinfo( $s, PATHINFO_DIRNAME )));
	}

	/**
	 * get default: return var if set, else return default.
	 */
	function gd( &$var, $default )
	{
		return isset( $var ) ? $var : $default;
	}

	function gad( $array, $key, $default )
	{
		return array_key_exists( $key, $array ) && isset( $array[ $key ] )
			? $array[ $key ] : $default;
	}


	function localFile( $uri, $base = null )
	{
		global $requestBaseDir;
		$base = isset( $base ) ? $base : $requestBaseDir;
		$base = parse_url( getDirectory( $base ), PHP_URL_PATH );

		$p =  parse_url( $uri, PHP_URL_PATH );

		if ( startsWith( $p, $base ) )
		{
			$p = substr( $p, strlen( $base ) );
		}
		elseif ( strpos( $p, "/" ) === 0 && strpos( $p, "\\") == 0 )
		{
			$p = filename( pathinfo( $p, PATHINFO_DIRNAME ) ) .'/'.
				filename( $uri );

			//return null;//warn ( "Not a local path: $p");
		}
		else // relative path
			;

		return $p;
	}

	function safeFile( $f )
	{
		return $f==null?null:str_replace( "\\", '_', str_replace( "/", '_', $f ) );
	}

	function safePath( $f )
	{
		return $f==null?null:str_replace("..","", str_replace("\\","_", $f ));
	}

	function endsWith( $haystack, $needle )
	{
		return substr( $haystack, strlen( $haystack ) - strlen( $needle ) )
			== $needle;
	}

	function startsWith( $haystack, $needle )
	{
		return substr( $haystack, 0, strlen( $needle ) )
			== $needle;
	}


	function getDirectory( $a )
	{
		$a = preg_replace( "@/[^\/]+\/\.\.@", "" , $a );	# resolve ..
		$a = preg_replace( "@\\\@", "/" , $a );						# \ to /
		if ( $a != "" ) $a .= '/';
		return stripDoubleSlash( $a );//pathinfo( $a, PATHINFO_DIRNAME ) );
	}

	function stripDoubleSlash( $a )
	{
		return str_replace( "//", "/", $a );
	}


	function mimetype( $fn )
	{
		switch ( pathinfo( $fn, PATHINFO_EXTENSION ) )
		{
			case "xml":
			case "xsl":	return "application/xml";
			case "js":  return "text/javascript";
			case "css": return "text/css";
			case "html": return "text/html";
			case "png": return "image/png";
			case "jpg": return "image/jpeg";

			default: return null;
		}
	}

	function sendmime( $fn )
	{
		$mime = mimetype( $fn );
		#debug( 'util', "mime for '$fn': $mime" );
		if ( isset( $mime ) )
			header( "Content-Type: $mime" );
	}


	function requestURL()
	{
		return
			( $_SERVER["SERVER_PORT"] == 443 ?"https://" : "http://" ) .
			$_SERVER["SERVER_NAME"] .
			(
			$_SERVER["SERVER_PORT"] == 80 ? ""
				: $_SERVER["SERVER_PORT"] == 443 ? ""
				: ":".$_SERVER["SERVER_PORT"]
			) .
			$_SERVER["REQUEST_URI"] . gd( $_SERVER["QUERY_STRING"], "" );
	}
?>
