<?php
require_once 'Util.php';
require_once 'APIClient.php';

$consumer_key			= 'username';
$consumer_secret	= 'password';

if ( auth_user() )
{
	$consumer_key = auth_username();

	require_once( 'db.php' );
	$auth = new SQLAuthentication( initMMSdb() );
	$auth->trace=10;

	$x = $auth->init_consumer( 'api-v1', $consumer_key );
	if ( count($x)==1 )
	{
		$consumer_secret = $x[0]->secret;

		echo "
			<p style='border-left: 4px solid orange; padding-left: 10px;'><code>
				Using auto-created token for $consumer_key<br/>
				<a href='/auth.html?action:auth:logout'>Logout</a> to use hardcoded values.
			</code></p>
		";
	}
	#exit;
}

$r = new APIClient('http://'.$_SERVER['SERVER_NAME'].'/api/v1/', $consumer_key, $consumer_secret );
$r->debug = intval( gad( $_REQUEST, 'debug' ) );

$params = ( $tmp = gad( $_REQUEST, 'trace' ) ) ? ['trace'=>$tmp] : null;
$call = 'test';
$data = null;

list ( $code, $headers, $body ) = $r->api_request( 'GET', $call, $params, $data );
list ( $code, $headers, $body ) = $r->api_request( 'GET', $call, $params, $data, $headers );

if ( $code == '200' )
	echo $body;
else
	echo "<pre><b>FINAL:</b> $code\n".print_r($headers,1)."\n\n<div style='border:2px solid black'>$body</div></pre>";

