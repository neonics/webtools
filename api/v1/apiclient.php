<?php
require_once 'Util.php';
require_once 'APIClient.php';


$consumer_key			= 'username';
$consumer_secret	= 'password';

if ( isset( $_REQUEST['u'] ) && isset( $_REQUEST['s'] ) )
{
	$consumer_key = $_SESSION['api-key'] = $_REQUEST['u'];
	$consumer_secret = $_SESSION['api-secret'] = $_REQUEST['s'];
}
else if ( isset( $_SESSION['api-key'] ) && isset( $_SESSION['api-secret'] ) )
{
	$consumer_key = $_SESSION['api-key'];
	$consumer_secret = $_SESSION['api-secret'];
}
else if ( auth_user() )
{
	$consumer_key = auth_username();

	if ( ! function_exists( 'api_get_auth_db' ) )
		fatal( "Function 'api_get_auth_db' not implemented!" );
	$auth = new SQLAuthentication( api_get_auth_db() );

	$x = $auth->init_consumer( 'api-v1', $consumer_key );
	if ( count($x)==1 )
	{
		$consumer_secret = $x[0]->secret;

		echo "
			<p style='border-left: 4px solid orange; padding-left: 10px;'><code>
				Using auto-created token <code>$consumer_secret</code> for <b>$consumer_key</b><br/>
				<a href='/auth.html?action:auth:logout'>Logout</a> to use hardcoded values.
			</code></p>
		";
	}
	#exit;
}
else
{
	echo "<p>Either <a href='/'>login</a> or fill out this form:
		<form method='post'>
			<label>consumer_key</label> <input type='text' name='u'/><br/>
			<label>consumer_secret</label> <input type='password' name='s'/><br/>
			<input type='submit'/>
		</form>
	";
	return;
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

