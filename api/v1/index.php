<?php
if ( isset( $TEMPLATE ) )
{
	if ( isset( $TEMPLATE_INCLUDE ) )
		require_once $TEMPLATE_INCLUDE;
	$template = new $TEMPLATE();
}
else
{
	class APINoticeBar extends \template\NoticeBar {
		protected function gitRevision() {
			global $pspBaseDir;
			return exec( "cd $pspBaseDir && git log -"."-oneline | wc -l");
		}
		protected function getLogo() {
			return "<span>Webtools</span>";
		}
	}
	class APITemplate extends \template\BootstrapTemplate {
		public function __construct() {
			parent::__construct();
			$this->noticebar = new APINoticeBar( $this );
		}
		function menu( $request ) {
	    return auth_user() ? parent::menu( $request ) : null;
		}

	}
	$template = new APITemplate();
}

$request = (object) [
	'requestBaseURI' => '/'
];
echo $template->main($request, 'legacy content');

#function template_init() { return []; }

function template_content() {
	?>
	<style type='text/css' scoped='scoped'>
		section > h2 > a {
			display:block;
			max-width: 10em;

			x-color: black;
			x-background-color: gray;
		}
		section > h2 > a::after {
			font-family: FontAwesome, font-awesome;
			content: "\f106";
			float: right;
		}
		section > h2 > a.collapsed::after {
			content: "\f107";
		}

		section > h2 + div {
			margin-bottom: 3em;
			margin-left: 1em;
		}

		section ul { margin-left: 2em; margin-bottom: 1em; }
		
		section ul.auth-methods { list-style: inside none; }
		/* simulate <a> */
		section ul.auth-methods > li > h3 { color: #337ab7; foo-color: #23527c; cursor: pointer; }
		section ul.auth-methods > li > h3:hover { color: #23527c; text-decoration: underline; }

		section ul.auth-methods > li > h3::before {
			display:inline-block;
			float:left;
			margin-right: .5em;
			margin-left: -1em;

			font-family: FontAwesome, font-awesome;
			content: "\f104";
			font-size: 24px;
		}
		section ul.auth-methods > li > h3.collapsed::before {
			content: "\f105";
		}

		.v { color: blue; background-color: #ddf; }
		.x { color: green; background-color: #dfd; }
		.c { color: #f60; background-color: #fed; } /*orange*/
		.r { color: black; background-color: #ffa; }

		code { white-space: nowrap; }

		pre {
			width: auto;
			max-width: 40em;
			line-height: 1.1em;
			white-space: pre; clear:both
		}

		dl,dt,dd { width: auto; }
		dl { max-width: 40em; margin: 1em; }
		dd { margin-bottom: 1em; margin-top: 1em;}

	</style>
	<header class='navbar'>
		<nav class='menu navbar'>
			<ul class='nav navbar-nav'>
				<li><h2>API v1</h2>
			</ul>
		</nav>
	</header>
	<div>
		<div class='container-fluid'>
	<?php

		$time = time();

	#	do_section( 'Overview', 'overview...' );
		do_section( 'Authentication', <<<HTML
			<p>
				This API makes use of a <u>variation</u> of <strong>OAuth 1.0</strong> for simplicity,
				but HTTP Digest authentication is also supported. HTTP Basic authentication is not
				supported except over SSL - which is not set up at the moment.
			</p>


			<p>
				When doing an API request, a <code>HTTP 401 Authentication Required</code>
				response is sent by the server, including multiple <code>WWW-Authenticate</code> headers:
				<ul class='auth-methods'>


					<li>
						<h3 data-toggle='collapse' data-target='#ss-oauth' class='collapsed'>OAuth</h3>
						<div id='ss-oauth' class='collapse'>
							<p>You will need to obtain
								a <code class='v'>consumer_key</code> and
								a <code class='v'>consumer_secret</code>,
								which are simply your account <b>username</b> and <b>password</b>, for now.
							</p>
							<p>The OAuth challenge looks like this:</p>
							<pre>WWW-Authenticate: OAuth realm="<code class='x'>realm</code>",oauth_nonce="<span class='x'>nonce</span>"</pre>
							<p>
								The client should then make another request including an <code>Authorization</code> header:<br/>
							</p>
							<pre>Authorization: OAuth realm="<code class='x'>realm</code>",
	oauth_nonce="<span class='x'>nonce</span>",
	oauth_consumer_key="<span class='v'>consumer_key</span>"
	oauth_signature_method="HMAC_SHA256",
	oauth_signature="<span class='c'>signature</span>",
	oauth_timestamp="<span class='c'>timestamp</span>"
							</pre>
							<p><i>NOTE: line-breaks and extra white-space added for readability. The above must conform to a standard HTTP 1.0 Header specification.</i></p>
							<p>
								Here,
								<dl>
									<dt class='x'>realm</dt>
									<dd>
										is the value of the <code class='x'>realm</code> field in the <code>WWW-Authenticate</code> header,
										and is <code>api-v1</code> for this specification.
									</dd>


									<dt class='x'>nonce</dt>
									<dd>
										is the value of the <code>oauth_nonce</code> field in the <code>WWW-Authenticate</code> response header,
										20 bytes encoded as a 40 character hex string.
									</dd>

									<dt class='v'>consumer_key</dt>
									<dd>is a client identification number: your account username.</dd>

									<dt class='v'>consumer_secret</dt>
									<dd>This is your account password</dd>

									<dt class='c'>timestamp</dt>
									<dd>
										is a UNIX timestamp (seconds since the Epoch). For example, the current timestamp is <code class='c'>{$time}</code>.
										This timestamp is used to calculate the <code class='c'>signature</code>.
									</dd>

									<dt class='c'>signature</dt>
									<dd>
										The signature calculation (actually a <a href='http://en.wikipedia.org/wiki/Message_authentication_code'>MAC</a>)
										is derived from <a href='https://tools.ietf.org/html/rfc5849#section-3.4.1'>RFC5849</a>,
										with some minor changes:
										<ul>
											<li><code>SHA256</code> is used rather than <code>SHA1</code>.</li>
											<li>The separator character is a newline (<code>\\n</code>) rather than <code>&amp;</code>;</li>
											<li>The <code class='r'>URI</code> and <code class='r'>BODY</code>code are taken as-is,
												rather than <a href='https://tools.ietf.org/html/rfc5849#section-3.4.1.3.2'>normalizing</a>;
											</li>
											<li>The <a href='https://tools.ietf.org/html/rfc5849#section-3.4.1.2'>base string URI</a> is not used;</li>
											<li>The <code class='x'>nonce</code> is added, rather than all the normalized <code>WWW-Authenticate</code> attributes.</li>
										</ul>
										This makes the algorithm much simpler:
										<pre>
		<span class='c'>signature</span> = hash_hmac( 'sha256',

			// the content to sign:
			<span class='r'>METHOD</span> . "\\n" .
			<span class='r'>URI</span>    . "\\n" .
			<span class='r'>BODY</span>   . "\\n" .
			<span class='x'>nonce</span>  . "\\n" .
			<span class='c'>timestamp</span>,

			// the secret:
			<span class='v'>consumer_secret</span>

		);</pre>
									<br/>
									The hash function:
									<pre>
		// PHP:
		\$signature = hash_hmac('sha256', \$content, \$secret );

		// Perl:
		use Digest::SHA qw(hmac_sha256_hex); 
		\$signature = hmac_sha256_hex( \$content, \$secret );
									</pre>
									</dd>
									<dt class='r'>METHOD</dt>
									<dd>is the HTTP Request Method (<code class='r'>GET</code>, <code class='r'>POST</code> etc),</dd>
									<dt class='r'>URI</dt>
									<dd>is the path and query components of the request URI (f.e. <code class='r'>/api/v1/order/ship?id=123</code>),</dd>
									<dt class='r'>BODY</dt>
									<dd>is the contents of the request-entity body (POST body) as-is (f.e. <code class='r'>{foo:"bar"}</code>),</dd>
								</dl>
							</p>
						</div>
					</li>



					<li>
						<h3 data-toggle='collapse' data-target='#ss-digest' class='collapsed'> Digest </h3>
						<div id='ss-digest' class='collapse'>
							<p>
								This method is mainly included for manual browsing and getting a client working quickly. 
								See <a href='http://tools.ietf.org/html/rfc2617'>RFC2617</a> for details.
							</p>
							<p>
								The HTTP Digest challenge header looks like this:
								<pre>WWW-Authenticate: Digest realm="<code class='x'>realm</code>",nonce="<span class='x'>nonce</span>"</pre>
							</p>
							<p>The response is detailed in the RFC, and looks like this:
								<pre>
Authorization: Digest realm="<code class='x'>realm</code>",
	username="<code class='v'>username</code>",
	nonce="<code class='x'>nonce</code>",
	uri="<code class='r'>URI</code>",
	response="<code class='c'>digest</code>"
								</pre>
								<i>NOTE: line-breaks and extra white-space added for readability. The above must conform to a standard HTTP 1.0 Header specification.</i>
							</p>
							Here,
							<dl>
								<dt class='x'>realm</dt>
								<dd>
									is the value of the <code class='x'>realm</code> field in the <code>WWW-Authenticate</code> response header,
									and is <code>api-v1</code> for this specification.
								</dd>
								<dt class='v'>username</dt>
								<dd>your account username</dd>
								<dt class='x'>nonce</dt>
								<dd>is the <code class='x'>nonce</code> value from the <code>WWW-Authenticate</code> header</dd>
								<dt class='r'>URI</dt>
								<dd>is the HTTP Request URI, for example <code>/api/v1/test?foo=bar</code></dd>
								<dt class='c'>digest</dt>
								<dd>is a calculated HASH as per the RFC. The algorithm is: <pre>
\$A1 = md5( <span class='v'>username</span> . ':' . <span class='x'>realm</span> . ':' . <span class='v'>password</span> );
\$A2 = md5( <span class='r'>METHOD</span> . ':' . <span class='r'>URI</span> );

if ( <span class='x'>qop</span> != null )
	\$digest = md5( \$A1 . ':' . <span class='x'>nonce</span> . ':'
	 . <span class='x'>nc</span> . ':' . <span class='x'>cnonce</span> . ':' . <span class='x'>qop</span> . ':' . \$A2 );
else
	\$digest = md5( \$A1 . ':' . <span class='x'>nonce</span> . ':' . \$A2 );
</pre>
									The value of the field <code class='x'>realm</code> is <code>api-v1</code> here, but may change in the future.
									Note that the <code class='x'>qop</code>, <code class='x'>nc</code> and <code class='x'>cnonce</code>
									are not specified in this example, but the server may choose to send these in the authentication header.
								</dd>
							</dl>
						</div>
					</li>



				</ul>
			</p>
HTML
		);
	?>
		</div>
	</div>
	<?php
}

function do_section( $title, $content )
{
	static $__section_id = 0;
	list( $l_class, $s_class ) = $__section_id++
	? ["collapsed", "collapse"]
	: ["", "collapse in"];
	echo <<<HTML

		<section>
			<h2>
				<a data-toggle='collapse' data-target='#s$__section_id' class='$l_class'>
					$title
				</a>
			</h2>
			<div id='s$__section_id' class='$s_class'>
				$content
			</div>
		</section>

HTML;
}
