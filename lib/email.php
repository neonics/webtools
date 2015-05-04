<?php
/**
 * Simple Multi-part MIME E-Mail wrapper.
 *
 * Usage:
 *

	print
			new_email()
			->sender("test")
			->recipient( "kenneyw@gmail.com" )
			->subject( "Test - [Webtools]" )
			->text( "Called from ".__FILE__ )
			->attachment( "Plain content", "text/plain" )
			->attachment( "Attached text file", "text/plain", "test.txt" )
			->send()
			;
 *
 * NOTE: the "new_email()" call is because PHP-5.4 does not support
 * "(new EMail())->" grammar.
 *
 * @author <kenney@neonics.com>
 */

class MimeMessage
{
	private static $_idcounter = 0;
	protected $parts= array();
	protected $boundary = null;
	private $subtype;

	/**
	 * @param $subtype "mixed" || "alternative" || "related" etc
	 */
	public function __construct( $subtype = "mixed" ) {
		self::$_idcounter++;
		$this->subtype = $subtype;
	}

	protected function init()
	{
		if ( ! $this->boundary && count( $this->parts ) )
			$this->boundary = "part" . crc32( sprintf("%f-%d", microtime(true), self::$_idcounter ) );
	}

	protected function boundary( $default = null )
	{
		return $this->boundary ? "\r\n--$this->boundary\r\n" : $default;
	}

	protected function mime_headers()
	{
		$this->init();
		return ! count( $this->parts ) ? array() : array_map(
			function($a,$b) {return "$a: $b";}, array_keys( $a =
			array(
				"MIME-Version"	=> "1.0",
				"Content-Type"	=> "multipart/$this->subtype; boundary=\"$this->boundary\""
			) ), $a
		);
	} 

	public function attachment( $data, $type="text/plain", $name=null, $addit_hdr = array() )
	{
	#	$this->parts[] = "ATTACHMENT: $name TYPE $type -- ".gettype($data); return $this;
		$this->parts[] =
			$data instanceof MimeMessage ? $data->format() :
			implode("\r\n", array_merge(
				$addit_hdr,
				!$name?array(): array( "Content-Disposition: attachment;\n filename=\"$name\"", "Content-ID: <$name>" ), // inline
				array( "Content-Type: $type".(!$name?null:";\n name=\"$name\"") ),
				preg_match( "@^text/@", $type )
					?	array ( "", $data )
					: array (
						"Content-Transfer-Encoding: base64",
						"",
						chunk_split( base64_encode( $data ), 68, "\n" )
					)
			) )
		;

		return $this;
	}

	public function addPart( $content, $type = "text/plain", $charset = "utf-8", $format = "flowed" )
	{
		$this->parts[] =
			$content instanceof MimeMessage ? $content->format() :
			"Content-Type: $type; charset=$charset" . ( $format ? "; format=$format" : "" ) . "\r\n"
		. "\r\n$content"
		;
	}

	public function text($r) { $this->addPart( $r ); return $this; }
	public function html($r) { $this->addPart( $r, "text/html", null, null ); return $this; }

	public function format()
	{
		$this->init();
		return implode("\r\n",
			array(
				"Content-Type: multipart/$this->subtype; boundary=\"$this->boundary\"", # multipart/alternative
				# XXX this->mime_headers()
				$this->boundary().
				implode( $this->boundary(), $this->parts ).
				$this->boundary()
			)
		);
	}
}


class EMail extends MimeMessage
{
	public $sender;
	public $recipient;
	public $subject;
	public $headers = array();
	public $body;

	public function __construct()
	{
		parent::__construct( "mixed" );
	}

	protected function init()
	{
		parent::init();
		foreach ( explode(' ', "sender recipient" ) as $f )
			if ( ! $this->$f ) throw new Exception(__CLASS__.": no $f");
	}

	public function send()
	{
		$this->init();

		if (0)	// 0: send mail
		{
			if ( 1 )
				return "<pre>".$this->format()."</pre>";
			else
				return "<pre>mail(
					\"$this->recipient\",
					\"$this->subject\",
					\"".$this->format_body()."\",
					\"".implode( "\r\n", $this->headers() )."\"
				);</pre>";
		}
		else
			return mail(
				$this->recipient,
				$this->subject,
				$this->format_body(),
				$this->format_headers()
			);
	}

	public function format()
	{
	#	return print_r( $this,1 );
		return implode("\r\n", array(
					"To: $this->recipient",
					"Subject: $this->subject",
					$this->format_headers(),
					"",
					$this->format_body()
			) );
	}

	private function headers() {
		return array_merge(
			array( "From: $this->sender" ),
			$this->headers,
			$this->mime_headers()
		);
	}

	private function format_headers() {
		return implode("\r\n", $this->headers() );
	}


	private function format_body()
	{
		return implode(
			$this->boundary("\r\n"),
			array_merge
			(
				! count($this->parts) ? array() : array_merge(
					array( "This is a MIME-multipart encoded message" ),
					$this->body ? array( $this->body ) : array(),
					$this->parts,
					array( "" ) // for closing boundary
				)
			)
		);

				#$body .= "Content-Type: $part->type; charset=utf-8; format=flowed\r\n\r\n";
	}

	private function _attr( $k, $v ) {
		if ( $v === null )
			return $this->$k;//$k=='body' ? $this->format_body() : $this->$k;
		$this->$k = $v;
		return $this;
	}

	public function sender		($r = null)	{ return $this->_attr( 'sender',		$r ); }
	public function recipient	($r = null)	{ return $this->_attr( 'recipient', $r ); }
	public function subject		($r = null)	{ return $this->_attr( 'subject', 	$r ); }
	public function body			($r = null)	{ return $this->_attr( 'body',			$r ); }

}

function new_email() { return new EMail(); }
function new_message( $subtype = "mixed" ) { return new MimeMessage( $subtype ); }
