<?php
	function sortLength($a, $b)
	{
			return strlen($a) > strlen($b) ? $a : $b;
	}

	function fatal( $catOrMsg, $themsg = null, $exception = null )
	{
		debug( $catOrMsg, isset( $exception ) ? $themsg  . $exception : $themsg );
		//error( $catOrMsg.(isset( $exception ) ? $themsg  . $exception : $themsg ) );
		//error( "fatal error, exiting." );
		die( #"<pre>".__FILE__."</pre>"
		"<pre style='color:red'><b>[$catOrMsg]</b> ".(isset( $exception ) ? $themsg  . $exception : $themsg ) ."</pre>\n" );
		exit;
	}

	function sys_message( $kind, $msg )
	{
		debug( "sys_$kind", $msg );
	}

	function debug( $catOrMsg, $themsg = null )
	{
		global $logging, $debug;//, $debugFixes;

		// prefix and postfix
		static $templates = Array(
			'1' => '<!-- [$cat:$level$pad] $msg -->\n',
			'2' => '<code style=\'white-space:pre; font-size: 8pt; color:black;\'>[$time][<span style=\'color:".$templates[$cat]["color"]."\'>$cat:$level$pad</span>] $msg</code><br/>\n',

			'[]' => Array( 'color' => '#33f' ),
			'default' => Array( 'color' => 'black' ),
			'resource' => Array( 'color' => '#aa0' ),
			'xml' => Array( 'color' => 'grey' ),
			'module' => Array( 'color' => 'green' ),
			'db' => Array( 'color' => 'blue' ),

			'sys_msg' => <<<'HTML'
	<div class='alert alert-warning sys_msg $cat'>
		<style type='text/css' scoped='scoped'>
			.sys_msg {
				box-shadow: 2px 2px 5px 0px rgba(0, 0, 0, 0.35);
				padding: 1em;
				margin: 1.5em;
				background: radial-gradient(circle at 2em 50%, gold, black 4em, #82f 50%, black);
				width: auto;
				display: block;
				color: white;
			}

			.sys_msg i {
				color: yellow;
				font-size: large;
				padding-right: 1em;
				padding-left: .5em;
				margin: -.5em 0;
			}

			.sys_msg a {
				color: white;
			}

			.sys_msg pre {
				margin: .5em;
				padding: .2em;
			}

			.sys_msg > i.fa:first-child {
				float:left;
				font-size: xx-large;
				margin-right: .5em;
			}

			.sys_msg.sys_error {
				background: radial-gradient(circle at 2em 50%, red, black 4em, #82f 50%, black);
			}
			.sys_msg.sys_error i { color: red; }
		</style>
		<i class='fa fa-exclamation-circle'></i>
		$msg
	</div>
HTML
			,
		);

		$msg = isset( $themsg ) ? $themsg : $catOrMsg;
		$cat = isset( $themsg ) ? $catOrMsg: "default";

		$cat = is_object( $cat )
			? ( isset( $cat->_logname ) ? $cat->_logname : get_class( $cat ) )
			: $cat;

		if ( is_string( $cat ) && substr( $cat, 0, 4 ) == 'sys_' )
		{
			$level = 'sys_msg';
		}
		else
		{
			# echo "<pre>DBG MSG: [$cat] $msg</pre>";

			$level = "".min( $debug, 2 ); // XXX 1 = 

			global $PSP_TIMING_BEGIN; // should be set-up in serve.php
			if ( ! $PSP_TIMING_BEGIN )
				$PSP_TIMING_BEGIN = microtime(true);

			$time = sprintf("%8s", sprintf( "%.3f", (microtime(true)-$PSP_TIMING_BEGIN)*1000));
			$pad = array_reduce( array_keys($templates ), "sortLength" );
			$pad = str_repeat( ' ', max( 0, strlen($pad)-strlen($cat) ) );

			if ( $logging & 1 )
			error_log( sprintf( "[%s%s] %s", $cat, $pad, $msg ) );

			if ( ! ( $debug && $logging & 2 ) )
				return;

			$msg = str_replace( "[", "[<span style='font-weight:bold;color:".$templates['[]']['color']."'>", $msg );
			$msg = str_replace( "]", "</span>]", $msg );
			#$msg = preg_replace( "@([\[^\]\]+])@",
			#	"<span style='color:purple;'>\\1</span>", $msg );

			if ( !array_key_exists( $cat, $templates ) )
				$templates[$cat] = Array( 'color' => 'red' );
		}

		$r = $templates[ $level ];
		eval ( "echo \"$r\";" );
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
		file_put_contents( "/tmp/dump-$f-$fileIdx$p.xml", $doc->saveXML() );
	}
	
?>
