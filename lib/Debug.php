<?php
	function sortLength($a, $b)
	{
			return strlen($a) > strlen($b) ? $a : $b;
	}

	function fatal( $catOrMsg, $themsg = null, $exception = null )
	{
		debug( $catOrMsg, isset( $exception ) ? $themsg  . $exception : $themsg );
		error( $catOrMsg.(isset( $exception ) ? $themsg  . $exception : $themsg ) );
		error( "fatal error, exiting." );
		exit;
	}

	function debug( $catOrMsg, $themsg = null )
	{
		global $logging, $debug;//, $debugFixes;

		// prefix and postfix
		static $templates = Array(
			'1' => '<!-- [$cat:$level$pad] $msg -->\n',
			'2' => '<code style=\'white-space:pre; font-size: 8pt; color:black;\'>[<span style=\'color:".$templates[$cat]["color"]."\'>$cat:$level$pad</span>] $msg</code><br/>\n',
			'[]' => Array( 'color' => '#33f' ),
			'default' => Array( 'color' => 'black' ),
			'resource' => Array( 'color' => '#aa0' ),
			'xml' => Array( 'color' => 'grey' ),
			'module' => Array( 'color' => 'green' ),
			'db' => Array( 'color' => 'blue' ),
		);

			
		$msg = isset( $themsg ) ? $themsg : $catOrMsg;
		$cat = isset( $themsg ) ? $catOrMsg: "default";

		$cat = is_object( $cat )
			? ( isset( $cat->_logname ) ? $cat->_logname : get_class( $cat ) )
			: $cat;

		#$msg = str_replace( '<', '&lt;', $msg );

		$level = "".min( $debug, 2 );
		$pad = array_reduce( array_keys($templates ), "sortLength" );
		$pad = str_repeat( ' ', max( 0, strlen($pad)-strlen($cat) ) );

		if ( $logging & 1 )
		error_log( sprintf( "[%s%s] %s", $cat, $pad, $msg ) );

		$msg = str_replace( "[", "[<span style='font-weight:bold;color:".$templates['[]']['color']."'>", $msg );
		$msg = str_replace( "]", "</span>]", $msg );
		#$msg = preg_replace( "@([\[^\]\]+])@",
		#	"<span style='color:purple;'>\\1</span>", $msg );

		if ( !array_key_exists( $cat, $templates ) )
			$templates[$cat] = Array( 'color' => 'red' );

		if ( $debug && ( $logging & 2) )
		{
			# if MessageListener ... else
			$r= $templates[ $level ];
			eval ( "echo \"$r\";" );
		}
	}


	function debugDocURI( $doc )
	{
		if ( $debug > 1 )
		{
			debug( "DocumentURI:  ".$doc->documentURI );
			debug( "DocumentFile: ". parse_url( $doc->documentURI, PHP_URL_PATH ) );
			debug( "DocumentPath: ". pathinfo(
				parse_url( $doc->documentURI, PHP_URL_PATH ), PATHINFO_DIRNAME )
			);
		}
	}
	

	// ideally, the $p (a $sheet filename) should be some construct
	// containing the resource type.
	function dumpXMLFile( $doc, $sheet = null )
	{
		global $debug, $debugDumpFiles;

		if ( /*$debug <2  && */ ! $debugDumpFiles )
			return;

		static $fileIdx = 0;

		$f = safeFile( localFile( $doc->documentURI ) );
		if ( !isset($f) )
		{
			debug("Not dumping - not a local file probably: ".$doc->documentURI.", local: " . localFile( $doc->documentURI ) );
			return;
		}

		$debug > 3 and
		debug("SAFEFILE: $f");

		$sheet = isset( $sheet ) ? safeFile(
			//localFile( $sheet )
			pathinfo( dirname( $sheet ), PATHINFO_FILENAME ).'-'.
			pathinfo( $sheet, PATHINFO_FILENAME ).'.'.
			pathinfo( $sheet, PATHINFO_EXTENSION )
		) : null;

		$p = isset($sheet) ? "-$sheet"  : "";
		$debug > 3 and debug("P: $p");

		$fileIdx++;
		debug("Dumping file: dump-$f-$fileIdx$p.xml");
		file_put_contents( "dump-$f-$fileIdx$p.xml", $doc->saveXML() );
	}
	
?>
