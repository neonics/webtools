<?php

class TemplateRequestHandler extends RequestHandler
{
	public function __construct()
	{
		$this->regexp = '@^/(.*?)\.(html|php)@';
	}


	public function _handle( $request )
	{
		global $debug;

#		print_r( $request);

		$matches;
		if ( preg_match( $this->regexp, $request->requestURI/*RelPathURI*/, $matches ) )
		{
			if ( $debug )
			debug( 'request', "[template] match $this->regexp: ".$matches[0] );
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
				$file = dirname(__FILE__).'/../pages/'.$matches[1].'.php';

				if ( file_exists( $file ) )
				{
					require_once( 'template.php' );	// MUST define class Template
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
						return false;
					}
					else
					{
						ob_start();
						require_once( $file );
						Template::$legacyContent = ob_get_clean();
						template_do( $request );
					}
					return true;
				}
				else
				{
					# 404
					echo "404 - not found: $file";
				}
			}
			return false;
			#true; # never try other handlers for html files
		}

		if ( $debug )
		debug( 'request', "[template] no match for $this->regexp  ($request->requestRelPathURI)" );
		return false;
	}
}


?>
