<?
class APIClient
{
	var $debug = 1;

	protected $apiURL;

	protected $consumer_key;
	protected $consumer_secret;

	public function __construct( $apiURL, $consumer_key, $consumer_secret )
	{
		$this->apiURL = $apiURL;
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
	}

	/**
	 * @param string $method				'GET' / 'POST' / 'PATCH' / 'DELETE'
	 * @param string $uri						a single word indicating the API section
	 * @param array  $params				HTTP request parameters  (limit/offset: pagination; limit max 30)
	 * @param mixed  $data					POST body, will be JSON encoded if not a string
	 * @param array  $response_headers	The response headers from a previous call, used to respond to WWW-Authenticate challences.
	 * @return array( $responseCode, array $headers, $raw_response_body )
	 */
	function api_request( $method, $uri, $params = array(), $data = null, $response_headers = array() )
	{
		$debugOut = $this->debug
			? "<code>".__CLASS__." api call $uri</code>\n"
			: null;

		$body = $data === null ? '' : ( is_string( $data ) ? $data : json_encode($data) );

		// Define all the mandatory headers
		$headers = array_merge(
			[
	#			'Accept: application/json',
	#			'X-Client: ' . $this->consumer_key,
	#			'X-Timestamp: ' . ( $timestamp = time() ), // Current Unix timestamp in seconds
	#			'X-Signature: ' . $this->signRequest($method, $uri, $body, $nonce="NOT USED!", $timestamp),
			],
			empty( $response_headers ) ? [] : $this->handle_auth_headers( 'GET', $uri, $data, $response_headers )
		);

		$url = $this->tourl( $uri, $params  );
		$uri = $this->touri( $uri, $params );

		if ( $this->debug ) {
			$debugOut .= "<code><b>Params:</b> ".print_r($params,1)."</code><br/>\n";
			$debugOut .= "<code><b>Request URI:</b> $uri</code><br/>\n";
			$debugOut .= "<code><b>Request URL:</b> $url</code><br/>\n";
			$debugOut .= "<code><b>Content:</b> $body</code><br/>\n";
		}

		if ( $this->debug )
			$debugOut .= "<pre><b>Request headers:</b>\n" .print_r($headers,1)."</pre>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

		switch ( $method )
		{
			default:
			case 'DELETE':
			case 'GET':
				break;

			case 'POST':
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
				break;

			case 'PUT':

			case 'PATCH':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
				break;
		}

		$response = curl_exec($ch);
		$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		list($header, $body) = explode("\r\n\r\n", $response, 2);

		if ( $this->debug > 1 )
			echo <<<HTML
	<pre style='border-left: 10px solid blue; padding-left: 10px'>
	<h1>DEBUG:</h1>
	$debugOut
	<b>Response Code:</b> $responseCode
	<b>header:</b>
	$header

	<b>body:</b>
	<div class='received-body' style='border: 2px solid black; box-shadow: 2px 2px rgba(0,0,0,.3)'>
	$body
	</div>
	</pre>
HTML;

		return array( $responseCode, self::parse_headers( $header ), $body );
	}


	private function touri( $uri, $params = array() )
	{
		 return preg_replace( "@^https?:\/\/[^\/]+@", "", $this->tourl( $uri, $params ) );
	}

	private function tourl( $uri, $params = array() )
	{
		return rtrim( $this->apiURL, '/' ) . ($uri?"/$uri":'/') . ( !empty( $params )
			#? "?" . implode('&', array_map(function($k,$v){return "$k=$v"; }, array_keys($params), array_values($params) ) )
			? "?" . http_build_query( $params )
			: "" );
	}



	private function signRequest($method, $uri, $body, $nonce, $timestamp )
	{
		// OAuth signature
		return Authentication::sign_request( $method, $uri, $body, $nonce, $timestamp, $this->consumer_secret );
	}

	/**
	 * @param string $raw_header
	 * @return array [ headername => headervalue ]
	 */
	public static function parse_headers( $raw_header )
	{
		$raw_headers = array_filter(
			explode( "\n", preg_replace('/\n[ \t]/', '', str_replace( "\r\n", "\n", $raw_header ) )
		), 'strlen' );

		$headers = array();

		foreach ( $raw_headers as $header ) {
			// skip response codes (appears as HTTP/1.1 200 OK or HTTP/1.1 100 Continue)
			if ( 'HTTP/' === substr( $header, 0, 5 ) )
				continue;

			list( $key, $value ) = explode( ':', $header, 2 );
			$value = trim( $value );

			$headers[ $key ] = ! isset( $headers[ $key ] ) ? $value :
				array_merge(
					is_array( $headers[ $key ] ) ? $headers[ $key ] : array( $headers[ $key ] ),
					array( $value )
				)
			;
		}
		return $headers;
	}

	/**
	 * Extracts WWW-Authenticate headers (case insensitive) and returns them parsed.
	 *
	 * For example, with $headers = [ 'WWW-Authenticate' => 'Digest realm="X",nonce="FOO"' ],
	 * the result will be
	 * [
	 *   [ 'Digest', (object) [ 'realm' => 'X', 'nonce' => 'FOO' ] ],
	 * ]
	 * .
	 *
	 * @param array $headers
	 * @return [ [ string auth_method, [ arg => val ] ], ... ] or an empty array.
	 */
	public static function parse_auth_headers( $headers )
	{
		$result = [];

		$headers = array_combine(
			array_map( 'strtolower', array_keys( $headers ) ),
			array_values( $headers )
		);

		if ( ! isset( $headers['www-authenticate'] ) )
		{
			echo "<pre>NO auth headers!</pre>";
			return [];
		}

		$ah = is_array( $ah = $headers['www-authenticate'] ) ? $ah : [ $ah ];

		foreach ( $ah as $i => $h )
			$result[] = self::parse_auth_header( $h );

		return $result;
	}

	/**
	 * @param string $v the value of a 'WWW-Authenticate' header
	 * @return [ string method, (object) [ arg => val ] ]
	 */
	public static function parse_auth_header( $v )
	{
		// simple parse:
		$h = preg_replace_callback( '@(".*?")@', function($m){ return "ENCODED{".base64_encode( $m[0] ). "}"; }, $v );
		$h = explode( ' ', $h );
		$h = array_map(
			function($v)
			{
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
			}, explode(',',implode(' ', array_slice($h,1)))
		);

		return [ $h[0], (object) $hv ];
	}


	/**
	 * Handles any WWW-Authenticate headers and generates a Authorization response header.
	 * @return [ 'Authorization: .... ]
	 */
	function handle_auth_headers( $api_req_method, $uri, $body, $headers )
	{
		$uri = $this->touri( $uri );

		foreach ( $auth_headers = Authentication::parse_auth_headers( $headers ) as $i => $data )
		{
			list( $method, $attributes ) = $data;

			switch ( strtolower( $method ) )
			{
				case 'oauth':
					$time = time();
					$signature = Authentication::sign_request( $api_req_method, $uri, $body, $attributes->oauth_nonce, $time, $this->consumer_secret );
					return [ "Authorization: OAuth " . implode( ',',
						array_map( function($k,$v) { return "$k=\"$v\""; },
							array_keys( $arr =
							[
								'realm' => $attributes->realm,
								'oauth_nonce' => $attributes->oauth_nonce,
								'oauth_consumer_key' => $this->consumer_key,
								'oauth_token' => '',
								'oauth_signature_method' => 'HMAC_SHA256',
								'oauth_signature'  => $signature,
								'oauth_verifier' => '',
								'oauth_timestamp' => $time,
							] ),
							array_values ( $arr )
						)
					) ];

				case 'digest':
					$digest = Authentication::digest( $api_req_method, $uri, $attributes, $this->consumer_key, $this->consumer_secret );
					return [ 'Authorization: Digest ' . implode(',',
						array_map( function($k,$v) { return "$k=\"$v\""; },
							array_keys( $arr =
							[
								'realm' => $attributes->realm,
								'uri' => $uri,
								'nonce' => $attributes->nonce,
								'username' => $this->consumer_key,
								'response' => $digest,
							] ),
							array_values( $arr )
						)
					) ];
					break;

				default: fatal("unknown auth mechanism: $method,".print_r($auth_headers,1));
			}
		}
	}

}
