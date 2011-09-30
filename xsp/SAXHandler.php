<?php

/**
 * @author Kenney Westerhof / Neonics
 */
class SAXHandler
{
	public $dom;

	private $stack = array();

	function __construct()
	{
		$this->dom = new DOMDocument("1.0", "utf-8");
		array_push( $this->stack, $this->dom );
	}

	public function startPrefixMapping( $prefix, $uri )
	{
	}

	public function endPrefixMapping( $prefix )
	{
	}

	public function addAttribute( $uri, $localname, $name, $value )
	{
	#echo "** ADD ATTRIBUTE $name=$value\n";
		$att = $this->dom->createAttributeNS( $uri, $name );
		$att->value = $this->sanitize( $value );
		$this->stack[count($this->stack)-1]->setAttributeNodeNS( $att );
	}


	public function startElement( $uri, $localname, $name )
	{
	#echo "** ELEMENT $name\n";
		$el = $this->dom->createElementNS( $uri, $name );
		end( $this->stack )->appendChild( $el );
		array_push( $this->stack, $el );
	}

	public function endElement( $uri, $localname, $name )
	{
		array_pop( $this->stack );
	}

	public function characters( $chars )
	{
		#$chars = $this->sanitize( $chars );
		end( $this->stack )->appendChild(
			$this->dom->createTextNode( $chars ) );
	}


	private function sanitize( $chars )
	{
		$chars = str_replace( "&", "&amp;", $chars );

		return $chars;
	}

	public function evaluate( $code )
	{
		return eval( $code );
	}
}



?>
