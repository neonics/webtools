<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */
require_once( "Util.php");
require_once( "Debug.php");

	function loadXML( $in, $debugType = "xml" )
	{
		global $debug;
		$debug and debug( 'xml', "load $debugType $in" );
		$doc = new DOMDocument();
		$doc->load( $in );
		$doc = ModuleManager::processDoc( $doc );	# ADDED
		$doc->xinclude();

		return $doc;
	}

	function transform( $doc, $sheets )
	{
		global $debug;

		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		if ( $debug > 3 )
		{
			ob_start();debug_print_backtrace();$t = ob_get_clean();
			debug('xml', "transform $doc->documentURI with ".implode(', ', $sheets) . " stacktrace:\n". $t);
		}

		$debug > 1 and
		debug( 'xml', "transform: loadXSL ".implode(', ', $sheets ) );

		$sheet = loadXSL( $sheets );

		$debug > 1 and
		debug ( 'xml', "transform ".
			filename( $doc->documentURI ).
			" sheet(s) ".implode(", ", $sheets) );

		$docURI = $doc->documentURI;
		$doc = $sheet->transformToDoc( $doc );
		$doc->documentURI = $docURI;

		$debug > 2 and
		debug('xml', 'transformation complete');

		return $doc;
	}

	function serializeDoc( $doc, $sheets )
	{
		global $debug;

		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		debug( 'xml', "serialize $doc->documentURI with style-sheet ".$sheets[0] );
		foreach ( $sheets as $s ) {
			$debug > 1 and debug ('xml', "   - sheet $s");
		}

		$sheet = loadXSL( $sheets );

		$doc->formatOutput = true;
		$doc->encoding="utf-8";

		$ret = trim ( $sheet->transformToXml( $doc ) );

		debug( 'xml', "serialize done." );

		return $ret;
	}


	function loadXSL( $sheets )
	{
		global $debug;

		static $xsltDocCache = array(); // for merged/parsed XML
		static $xsltCache = Array();

		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		$key = implode( ';', $sheets );
		$xslt = null; // there's a bug that resets the connection under certain conditions...
			//isset( $xsltCache[$key] ) ? $xsltCache[$key] : null;

		$doc = isset( $xsltDocCache[ $key ] ) ? $xsltDocCache[ $key ] : null;

		if ( $doc === null )
		{
			#$debug > 1 and
			#debug( 'xml', "load sheet $key" );

			$doc = mergeXSLT( $sheets );
			$doc = ModuleManager::processDoc( $doc );
			$xsltDocCache[ $key ] = $doc;

			#debug( str_replace("<", "&lt;", $doc->saveXML() ) );
			$debug > 3 and
			dumpXMLFile( $doc );
		}

		if ( $xslt === null )
		{
			//debug('xml', "!!! WARNING !!!  caching disabled");

			$xslt = new XSLTProcessor();
			ModuleManager::registerFunctions( $xslt );
			ModuleManager::setParameters( $xslt, $sheets );
			$xslt->importStylesheet( $doc );

			// XXX non-existing field
			$xslt->doc = $doc;

			$xsltCache[ $key ] = $xslt;
		}
		else
		{
			$debug > 2 and
			debug('xml', "updating cached sheet parameters for $key" );

			ModuleManager::registerFunctions( $xslt );
			ModuleManager::setParameters( $xslt, $sheets );

			if ( $debug > 3 )
			{
				ob_start();
				debug_print_backtrace();
				$t=ob_get_clean();
				debug( 'xml', "sheet $key configured, called from \n" . $t );
			}
		}

		return $xslt;
	}

	function mergeXSLT( $sheets )
	{
		global $debug;

		if ( count( $sheets ) == 1 )
			return loadXML( $sheets[0], 'sheet' );

		$debug and debug( 'xml', "merging sheets" );

		$doc = new DOMDocument();
		$docURI = null;
		$xsltns = "http://www.w3.org/1999/XSL/Transform";

		// quick and dirty - php 5.2 has some importNode errors wrt xmlns
		if ( true )
		{
			$a = <<<EOF
<?xml version="1.0"?><xsl:merge xmlns:xsl="$xsltns">
EOF;
			$b = "</xsl:merge>\n";

			foreach ( $sheets as $s )
			{
				$d = loadXML( $s );//->documentElement;
				$d = ModuleManager::processDoc( $d );

				// check to see if merge="no" for custom sheet
				if ( in_array( $d->documentURI, ModuleManager::$modules[ "psp" ][ "instance" ]->nomerge ) )
				{
					if ( $debug > 1 )
					debug('xml', "skip merge");
					return $d;
					#return loadXML( $sheets[1], 'sheet' );
				}


				#!isset( $docURI ) and
				# takes the first docuri
				$docURI == null and
				$docURI = $d->documentURI;
				$debug > 3 and debug("DOC URI: $docURI");

				$a .= "<xsl:node base='".file_to_uri(dirname($d->documentURI))."'>"
					. preg_replace("@<\?xml[^>]+>@", "", $d->saveXML() )//file_get_contents( $s ))
					. "</xsl:node>";
			}
			#echo "<pre>". str_replace( "<", "&lt;", $a.$b ) ."</pre>";
			$doc->loadXML( $a . $b );
			$doc->documentURI = $docURI;
			$doc->xinclude();
		}
		else
		{
			$doc->appendChild( $doc->createElementNS( $xsltns, "xsl:merge") );

			$docURI = null;

			if ( $debug > 2 ) debug( 'xml', "Merging stylesheets:" );
			foreach ( $sheets as $s )
			{
				debug( 'xml', "  sheet $s");

				$d = loadXML( $s );//->documentElement;
				#!isset( $docURI ) and
				$docURI = $d->documentURI;

				$n = $doc->createElementNS( $xsltns, "xsl:node" );
				$n->setAttribute( "base", $docURI );

				$doc->documentElement->appendChild( $n );

				foreach ( $d->childNodes as $a )
				{
					#debug( 'xml', "NODE: " . $a->nodeType .' '.$a->nodeName);

					$n->appendChild(
						$doc->importNode( $a, true ) );
				}
			}
		}

		if ( $debug > 3 )
		debug( 'xml', "MERGING: <code style='color:blue;'>" . str_replace( '<', '&lt;', $doc->saveXML() ) ."</code>" );

		$doc->documentURI = $docURI.'-merge';//filename( $docURI );
		dumpXMLFile( $doc );

		$xslt = loadXSL( $xsl=DirectoryResource::findFile( 'mergexsl.xsl', 'logic' ) );

		$doc = $xslt->transformToDoc( $doc );
		$doc->documentURI = $docURI;//filename( $docURI );
		dumpXMLFile( $doc, $xsl );

		if ( $debug > 3 )
		debug( 'xml', "MERGED: <code style='color:green'>" . str_replace( '<', '&lt;', $doc->saveXML() ) .'</code>');


		return $doc;
	}


function removeChildren( &$node )
{
	$node->parentNode->replaceChild( $n = $node->cloneNode(false), $node );
	$node = $n;
}

?>
