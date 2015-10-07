<?php
require_once 'Check.php'; // is also classloaded automatically
require_once 'Util.php';	// for gd()

/**
 * OAuth and Digest authentication - server side.
 *
 */
abstract class Authentication
{
	var $trace = false;

	/**
	 * @param $realm the HTTP authentication realm.
	 * @param string $consumer_key a user/consumer identification (username).
	 * @param $token ignored.
	 * @return an array of 0 or 1 stdClass (more are ignored).
	 */
	protected abstract function fetch_consumer_tokens( $realm, $consumer_key );

	public function __construct()
	{
		$this->trace = // XXX FIXME TODO: dev only
		intval( gad( $_REQUEST, 'trace' ) );
		;
	}

	protected function trace( $msg, $obj = null ) {
		if ( ! $this->trace )
			return;
		echo "[<b>TRACE</b>] <i>".get_class($this)."</i>: $msg<br/>";
		if ( $obj )
			echo "<pre>".print_r($obj,1)."</pre>";
	}

	function send_challenge( $realm )
	{
		header( 'HTTP/1.1 401 Permission denied' );
		#header( 'WWW-Authenticate: OAuth realm="'.$realm.'",oauth_nonce="'.($nonce=sha1(microtime())).'"' );
		//oauth_version="1.0",oauth_nonce="'.sha1( microtime() ) );

		$nonce = $this->create_nonce();

		#header( "WWW-Authenticate: OAuth realm=\"$realm\",oauth_nonce=\"$nonce\"" );
		header( "WWW-Authenticate: Digest realm=\"$realm\", nonce=\"$nonce\"", false );
	}

	/**
	 * @return false or an array of token objects ([stdClass])
	 */
	final function _fetch_consumer_tokens( $realm, $consumer_key /*, token */ )
	{
		$res = $this->fetch_consumer_tokens( $realm, $consumer_key, null );

		if ( empty( $res ) ) {
			$this->trace("no matching consumer token for realm <code>$realm</code>, consumer_key <code>$consumer_key</code>" );
			return false;
		}
		$this->trace("examining ".count($res)." matching consumer tokens" );
		return $res;
	}

	/**
	 * @param  extra seedd value;
	 * @return the generated nonce, associated with REMOTE_ADDR.
	 */
	function create_nonce( $seed = null )
	{
		return sha1( microtime().":".$_SERVER['REMOTE_ADDR'].($seed?":$seed":null) );
	}

	/**
	 * @return bool whether the response nonce was previously offered to the peer
	 */
	function verify_nonce( $nonce )
	{
		return 1 === count( $this->fetch_nonce( $nonce ) ); // nonce verified.
	}

	/**
	 * @param $hv oauth parameters/attributes: (object) ['oauth_nonce' => '..' etc ]
	 * @return mixed null if the nonce wasn't found, false if calculation differs
	 */
	function oauth_verify( $hv ) {
		#echo "<pre>".__METHOD__.": ".print_r($hv,1)."</pre>";
		if ( ! verify_nonce( $hv->oauth_nonce ) )
			return null;

		// TODO: token, uid etc checking

		$res = $this->fetch_consumer_tokens( $hv->realm, $hv->oauth_consumer_key, $hv->oauth_token );
		
		#$this->trace("decoded licence: " . base64_decode($res[0]['license_key']) );
		//if ( array_pop(explode(":",base64_decode($res[0]['license_key']))) == $hv['oauth_token'] )
		#echo "<Pre>req meth:".$_SERVER['REQUEST_URI']."</pre>";
		foreach ( $res as $i=>$r )
		{
			#echo "<pre>$i:".print_r($r,1);

			$this->secret = $r->secret;
			$signature = self::sign_request(
				$_SERVER['REQUEST_METHOD'],
				$_SERVER['REQUEST_URI'],
				file_get_contents('php://input'),
				$hv->oauth_nonce,
				$hv->oauth_timestamp,
				$r->secret
			);
			#echo "Signature: $signature";

			if ( $signature == $hv->oauth_signature )
			{
			#	echo "Signatures match!";
				return true;
			}
		}



		return false;
	}

	public static function sign_request($method, $uri, $body, $nonce, $timestamp, $secret )
	{
		$string = implode("\n", array(
			$method,
			$uri,
			$body,
			$nonce,
			$timestamp,
		));

		#echo "<div><b>signing:</b><pre>$string</pre><b>with</b> <code>$secret</code></div>";
		#echo "<pre>";debug_print_back$this->trace(DEBUG_BACKTRACE_IGNORE_ARGS);echo"</pre>";

		$sig = hash_hmac('sha256', $string, $secret);
		#echo "<pre><b>sig:</b> $sig</pre>";
		return $sig;
	}




	/**
	 * @return mixed null if the nonce wasn't found, false if calculation differs
	 */
	function digest_verify( $data )
	{
		$this->trace( "digest details: ".$_SERVER['PHP_AUTH_DIGEST'], $data );

		if ( is_array( $data ) )
			$data = (object) $data;

		if ( ! $this->verify_nonce( $data->nonce ) )
			return null;

		$h_qop = null; // the Quality of Protection we sent in the WWW-Authenticate header

		if ( $res = $this->_fetch_consumer_tokens( $data->realm, $data->username ) )	// !== false
		foreach ( $res as $r )
		{
			$A1 = md5( $data->username . ':' . $data->realm . ':' . $r->secret );
			$A2 = md5( $_SERVER['REQUEST_METHOD'] . ':' . $data->uri );

			if ( $h_qop != null )
				$verify = md5( $A1 . ':' . $data->nonce . ':' . gd($data->nc) . ':' . gd($data->cnonce) . ':' . gd($data->qop) . ':' . $A2 );
			else
				$verify = md5( $A1 . ':' . $data->nonce . ':' .                                                                        $A2 );

			$this->trace( "  given     : " . $data->response );
			$this->trace( "  calculated: " . $verify );
			if ( $data->response == $verify )
				return true;
		}
		return false;
	}

	public static function digest( $method, $uri, $data, $username, $password )
	{
		$A1 = md5( $username . ':' . $data->realm . ':' . $password );
		$A2 = md5( $method . ':' . $uri );

		$h_qop = null; // qop we sent in request

		if ( $h_qop != null )
			return md5( $A1 . ':' . $data->nonce . ':' . gd($data->nc) . ':' . gd($data->cnonce) . ':' . gd($data->qop) . ':' . $A2 );
		else
			return md5( $A1 . ':' . $data->nonce . ':' .                                                                        $A2 );
	}


	function sanitize($v) {
		return preg_replace( "@[^0-9a-zA-Z\.\-_]@", "", $v);
	}



	public static function parse_auth_headers( $headers, $headername = 'WWW-Authenticate' )
	{
		$result = [];

		$headers = array_combine(
			array_map( 'strtolower', array_keys( $headers ) ),
			array_values( $headers )
		);

		$headername = strtolower( $headername );
		if ( ! isset( $headers[ $headername ] ) )
		{
			echo("expected header <code>$headername</code> not set!<pre>".print_r( $headers,1 )."</pre>" );
			return $result;
		}

		$ah = is_array( $ah = $headers[ strtolower( $headername ) ] ) ? $ah : [ $ah ];
		
		foreach ( $ah as $i => $h )
			$result[] = self::parse_auth_header( $h );

		return $result;
	}

	/**
	 * @param string $v the value of a 'WWW-Authenticate' header
	 */
	public static function parse_auth_header( $v )
	{
		// simple parse
		$h = preg_replace_callback( '@(".*?")@', function($m){ return "ENCODED{".base64_encode( $m[0] ). "}"; }, $v );
		$h = explode( ' ', $h );
		$h = array_map(
			function($v) {
				return preg_replace_callback(
					'@ENCODED{(.*?)}@',
					function($m){ return base64_decode( $m[1] ); },
					$v
				);
			}, 
			$h
		);

		$hv=array();
		array_map( function($v) use (&$hv)
			{
				list($k,$v) = explode( '=', $v );
				$hv[$k] = preg_replace( '@^"(.*?)"$@', '$1', $v );
			},
			array_map( 'trim', explode( ',', implode(' ', array_slice($h,1)) ) )
		);

		$h[1] = (object) $hv;

		return $h;
	}

	public function process_headers()
	{
	#	echo "<pre>".__FUNCTION__.": ".print_r($_SERVER,1)."</pre>";
		foreach ( self::parse_auth_headers( getallheaders(), 'Authorization' ) as $i => $h )
		{
			#echo "<pre><b>".__METHOD__." -- Server</b>: ".print_r($h,1)."</pre>";
			list( $method, $attributes ) = $h;
			switch ( strtolower( $method ) )
			{
				case 'oauth':
					return $this->oauth_verify( $attributes );
				case 'digest':
					return $this->digest_verify( $attributes );
				default:
					echo "<code>error: unknown method: $method</code>";
					break;
			}
		}
		return false;
	}

}
