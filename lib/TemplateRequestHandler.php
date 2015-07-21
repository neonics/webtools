<?php
/**
 * Backwards compatible template system
 *
 * The idea is to allow legacy .php files to be executed as-is,
 * expecting them however to merely wrap their code between
 *
 * 'function template_content( $request ) {'
 *
 * and '}'
 *
 * (where $request is optional), although, if no such function
 * is found, all captured output the php file produced is
 * substituted where the template content is expected.
 *
 * See Template.php.
 *
 * Override mechanism:
 *
 * 1) class OverrideTemplateRequestHandler extends TemplateRequestHandler
			{
				protected function resolve( $file ) { return "$file.php"; }
			}
 *
 * 2) handle.php:
 *    $psp_custom_handlers = array( 'template' => 'OverideTemplateRequestHandler' );
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
#require_once( __DIR__."/Template.php" );
class TemplateRequestHandler extends RequestHandler
{
	public function __construct()
	{
		$this->regexp = '@^/(.*?)\.(html|php)@';
	}

	protected function resolve( $file ) {
		return DirectoryResource::findFile( "$file.php", 'pages' );
	}

	public function _handle( $request )
	{
		global $debug;

		if ( ob_get_level() ) ob_end_clean(); // close/clear debug


#		print_r( $request);

		$matches;
		if ( preg_match( $this->regexp, $request->requestURI/*RelPathURI*/, $matches ) )
		{
			if ( $debug )
			debug( $this, "match $this->regexp: ".$matches[0] );
#			$request->requestRelPathURI = "";
#			$request->requestFileURI = $this->redir[ $matches[1] ];
#			$request->requestRelURI = $request->requestFileURI;
#			return false;

			if ( strpos($matches[1], '.inc' ) !== false || strpos( $matches[1], '..' ) !== false )
			{
				#echo "(call ".$mycount++.") illegal file - no hacking!\n";
				// returns false so will try other handlers
			}
			else
			{
				$file = $this->resolve( $matches[1] );

				#dirname(__FILE__).'/../pages/'.$matches[1].'.php';

				debug( $this, "resolved $file" );

				if ( file_exists( $file ) )
				{
					#require_once( 'template.php' );
					#if ( session_status() === PHP_SESSION_NONE )
					#	session_start();
					#if ( ! isset( $_SESSION['user'] ) )
					ModuleManager::loadModule( "psp" );
					ModuleManager::loadModule( "auth" );

					global $publicURIs;
					if ( ! auth_user() && ! array_filter( $publicURIs, function($v)use($request){return $v == $request->requestURI;} ) )
					{
					  #header( "Location: $request->requestBaseURI"."login.html" );
						#echo "login first.";
						#return true;
						#require_once( 'login.php' );

						$request->requestRelURI = "auth.html";#"login.html";#"auth.html";
						#RequestHandler::handle( $request );
						return false;	// we're early in the request chain
					}
					else
					{
						// this could be (and was) written simpler, but we want any errors in 
						// tempalte_data_filter to show up.
						ob_start();
						$request->template_data =	require_once( $file );
						$legacy_content = ob_get_clean();

						$request->template_data = $this->template_data_filter( $request, $request->template_data );

						#echo "<pre>".print_r($request,1)."</pre>";

						template_do( $request, $legacy_content, is_array( $request->template_data ) ? gd( $request->template_data['template'] ) : null );
					}
					return true;
				}
				else
				{
					# 404
				}
			}
		}

		if ( $debug )
		debug( 'request', "[template] no match for $this->regexp  ($request->requestRelPathURI)" );
		return false;
	}


	/**
	 * Last moment to update template_data before template_do is called.
	 * This can be used to initialize $template_data->template. By default,
	 * it is not set, and template_do will initialize it with \template\BootstrapTemplate.
	 */
	protected function template_data_filter( $request, $template_data ) {
		return $template_data;
	}
}

// template_do( request, content, templateclassname ); see Template.php

?>
