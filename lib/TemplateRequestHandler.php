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
		return DirectoryResource::findFile( "$file.php", 'php' );
	}

	public function _handle( $request )
	{
		global $debug;

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
					if ( ! auth_user() )
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
						ob_start();
						require_once( $file );
						template_do( $request, ob_get_clean() );
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
}


?>
