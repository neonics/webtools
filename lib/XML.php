<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */
require_once( "Util.php");
require_once( "Debug.php");

	function loadXML( $in, $debugType = "xml" )
	{
		debug( 'xml', "load $debugType $in" );
		$doc = new DOMDocument();
		$doc->load( $in );
		$doc->xinclude();

		return $doc;
	}

	function transform( $doc, $sheets )
	{
		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		$sheet = loadXSL( $sheets );

		debug ("transform ".
			filename( $doc->documentURI ).
			" sheet ".$sheets[0] );

		$docURI = $doc->documentURI;
		$doc = $sheet->transformToDoc( $doc );
		$doc->documentURI = $docURI;

		return $doc;
	}

	function serializeDoc( $doc, $sheets )
	{
		global $debug;

		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		debug( 'xml', "serialize $doc->documentURI with style-sheet ".$sheets[0] );

		$sheet = loadXSL( $sheets );

		$doc->formatOutput = true;
		$doc->encoding="utf-8";

		return trim ( $sheet->transformToXml( $doc ) );
	}


	function loadXSL( $sheets )
	{
		global $debug;

		static $xsltCache = Array();

		if ( !is_array( $sheets ) )
			$sheets = Array( $sheets );

		$key = "";
		foreach ( $sheets as $s )
			$key .= $s.';';

		$xslt = isset( $xsltCache[$key] ) ? $xsltCache[$key] : null;
		
		if ( $xslt == null )
		{
			#$debug > 1 and
			#debug( 'xml', "load sheet $key" );
			$doc = mergeXSLT( $sheets );

			{
				$pspXSL = ModuleManager::$modules[ "psp" ][ "sheet" ];
				if ( isset( $pspXSL ) && $pspXSL.';' != $key )
				{
					$debug > 2 and
					debug( 'xml', "transforming $key with $pspXSL" );
					$doc = transform( $doc, $pspXSL );
				}
			}
#debug( str_replace("<", "&lt;", $doc->saveXML() ) );

			$xslt = new XSLTProcessor();
			ModuleManager::registerFunctions( $xslt );
			ModuleManager::setParameters( $xslt, $sheets );
			$xslt->importStylesheet( $doc );

			$xsltCache[ $key ] = $xslt;
		}


		return $xslt;
	}

	function mergeXSLT( $sheets )
	{
		global $debug;

		if ( count( $sheets ) == 1 )
			return loadXML( $sheets[0], 'sheet' );

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
				#!isset( $docURI ) and
				$docURI == null and 
				$docURI = $d->documentURI;

			debug("DOC URI: $docURI");
				$a .= "<xsl:node>"
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

		$doc->documentURI = $docURI.'-merged-doc';//filename( $docURI );
		dumpXMLFile( $doc );

		$xslt = loadXSL( $xsl=DirectoryResource::findFile( 'mergexsl.xsl', 'logic' ) );

		$doc = $xslt->transformToDoc( $doc );
		$doc->documentURI = $docURI;//filename( $docURI );
		dumpXMLFile( $doc, $xsl );

		if ( $debug > 3 )
		debug( 'xml', "MERGED: <code style='color:green'>" . str_replace( '<', '&lt;', $doc->saveXML() ) .'</code>');


		return $doc;
	}


?>
