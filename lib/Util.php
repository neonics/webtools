<?php

	function filename( $s )
	{
		return substr( $s, 1+strlen(pathinfo( $s, PATHINFO_DIRNAME )));
	}

	/**
	 * get default: return var if set, else return default.
	 */
	function gd( &$var, $default = null)
	{
		return isset( $var ) ? $var : $default;
	}

	/**
	 * use this version when the first argument is a function call
	 * or other non-referential type.
	 */
	function gd_( $var, $default = null )
	{
		return isset( $var ) ? $var : $default;
	}


	function gad( $array, $key, $default = null )
	{
		return $array !== null && array_key_exists( $key, $array ) && isset( $array[ $key ] )
			? $array[ $key ] : $default;
	}
		

	function make_object( $arg ) {
		switch ( gettype( $arg ) )
		{
			case 'array':
				foreach ( $arg as $k => $v )
					$arg[ $k ] = make_object( $v );
				return (object) $arg;
			default:
				return $arg;
		}
	}

	function make_array( $arg ) {
		switch ( gettype( $arg ) )
		{
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'object':
				$arg = (array) $arg;
			case 'array':
				foreach ( $arg as $k => $v )
					$arg[ $k ] = make_array( $v );
				return $arg;

			default:
				return $arg;
		}
	}

	/** makes an array of arrays into an array of objects */
	function make_object_array( $array )
	{
		foreach ( $array as $i => $data )
			$array[ $i ] = make_object( $data );
		return $array;
	}

	/**
	 * @param $array unassociative array of associative arrays (list of hashtables): $array of $items
	 * @param $key   returned array will be keyed by $array[*][$key]
	 * @param $value returned array will be valued as follows.
	 *                 null (default):   $array[*]         -> []
	 *								 string:           $array[*][$value] -> column value
	 * 								 [] (empty array)  [$array[*]]       -> for when $key is not unique, return array of arrays of arrays.
	 */
	function array_hash( $array, $key, $value = null )
	{
		$sh = array();
		foreach ( $array as $i=>$row )
			if ( is_array( $value ) ) {
				#if ( ! isset( $sh[$row[$key]] ) ) $sh[$row[$key]] = [];
				$sh[$row[$key]][] = $row;
			}
			else
				$sh[$row[$key]] = $value === null ? $row : $row[$value];
		return $sh;
	}


	function esc_js_str( $str )
	{
		return
		str_replace( '"', "\\\"",
		str_replace( "'", "\\'",
		str_replace( "\n", "\\n", $str )
		)
		);
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
			case "ttf": return "font/truetype";
			case "woff": return "application/font-woff";
			case "woff2": return "application/font-woff2";
			# TODO: .eot, .svg

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


	/**
	 * Closes the output buffers, saving the contents of each and returning them.
	 *
	 * Usage:
	 *
	 *   ob_save();
	 *   echo "Unbuffered out-of-band output";
	 *   ob_restore();
	 *
	 * @return array of output buffer content, from top level to most nested.
	 */
	global $__ob_save_buffers;
	function ob_save() {
		global $__ob_save_buffers;
		$obs = [];
		while ( ob_get_level() )
			$obs[] = ob_get_clean();
		return $__ob_save_buffers = array_reverse( $obs );
	}

	/**
	 * Re-opens and fills the output buffers closed by ob_save().
	 *
	 * @param array $obs (optional) the return value of ob_save(). If null,
	 * will use the buffer array created by the last ob_save() call.
	 */
	function ob_restore( $obs = null ) {
		global $__ob_save_buffers;
		if ( $obs === null )
			$obs = $__ob_save_buffers;
		else if ( empty( $obs ) )
			$obs = array_map(function($v){return null;}, $obs );
		$__ob_save_buffers = null;
		foreach ( $obs as $ob ) {
			ob_start();
			echo $ob;
		}
	}


	function qw( $string, $sep =' ' ) { return explode($sep, $string); }	// might need preg explode on \s+

	function cb_prefix( $with ) { return function($v) use($with) { return "$with$v"; }; }

	function cb_column( $key )  { return function($v) use($key)  { return $v[$key];  }; }

	/**
	 * Saves $content to the file "$name.$ext" after renaming "$name.$ext" to "$name-1.$ext",
	 * "$name-1.$ext" to "$name-2.$ext" etc., in the correct order.
	 *
	 * @param string $name File path and base name.
	 * @param string $ext Filename extension (without leading '.')
	 * @param string $content File content.
	 */
	function save_file_with_backups( $name, $ext, $content ) {
		$f = "$name.$ext";
		$i = 0;
		while ( file_exists( $f ) )
			$f = "$name-".++$i.".$ext";

		while ( $i > 0 )
			rename( $name.(--$i==0?"":"-$i").".$ext", "$name-".($i+1).".$ext" );

		file_put_contents( "$name.$ext", $content );
	}
