<?php
	include 'SAXHandler.php';

	$scriptFile;
	$in;
	$stylesheets = array();

	foreach ( $argv as $arg )
	{
		if ( !isset( $scriptFile ) )
			$scriptFile = $arg;
		else if ( isset( $in ) )
		{
			$stylesheets[] = $arg;
		}
		else
		{
			$in = $arg;
		}
	}

	class GenTimeXSPHandler
	{
		public $contenthandler;

		public function evaluate( $code ) {
			eval( $code );
			return $this->contenthandler->dom;
		}
	}

	function transform( $doc )
	{
		global $stylesheets;

		foreach ( $stylesheets as $sheet )
		{
			$xslt = new XSLTProcessor();
			$xslt->registerPHPFunctions('runCode');

			$sdoc = new DOMDocument();
			$sdoc->load( $sheet );
			$xslt->importStylesheet( $sdoc );

			$doc = $xslt->transformToDoc( $doc );
		}

		return $doc;
	}

	function serializeDoc( $doc )
	{
		// Serialize
		$doc->formatOutput = true;
		$doc->encoding="utf-8";

		$xslt = new XSLTProcessor();

		$sdoc = new DOMDocument();
		$sdoc->load( "logic/text.xsl" );
		$xslt->importStylesheet( $sdoc );

		return trim( $xslt->transformToXml( $doc ) );
	}

	/***** XSL called functions *****/

	function addXSL( $ss )
	{
		array_unshift( $stylesheets, $ss );
	}


	function runCode($code)
	{
		$code=trim($code);
		#echo "CODE { $code }\n";

		$h = new GenTimeXSPHandler();
		$h->contenthandler = new SAXHandler();

		$h->contenthandler->startElement( "http://neonics.com/2001/xsp", "content", "xsp:content" );
		$h->evaluate( $code );

		$h->contenthandler->endElement( "http://neonics.com/2001/xsp", "content", "xsp:content" );

		$h->contenthandler->dom->xinclude();

		#echo "OUTPUT { " . $h->contenthandler->dom->saveXML()  ."}\n";
		
		return serializeDoc( transform( $h->contenthandler->dom ) );
	}

	$doc = new DOMDocument();
	$doc->load( $in );
	$doc->xinclude();

	$doc = transform( $doc );

	echo serializeDoc( $doc );
?>
