<?php
/**
 * 
 */
class AuthRequestHandler extends RequestHandler
{
	protected $require_login = true;

	public function __construct() {
	}

	protected function _handle( $request )
	{
		ModuleManager::loadModule( "auth" );
		if ( $this->require_login )
		{
			if ( auth_user() )
				return false; // continue processing
			else
			{
				if ( preg_match( "/\.html$/", $request->requestFileURI ) )
				{
					$request->requestRelURI = "auth.html";
				}
				else if ( preg_match( "/\.(css|js|png|jpe?g|gif|woff2?|ttf|eot|ico|map)$/", $request->requestFileURI ) )
				{
					// allowed resources
				}
				else
				{
					header( 'HTTP/1.1 401 Permission Denied' );
					echo "Permission denied.\n";
					return true; // abort processing.
				}
			}
		}

		return false;	// continue processing
	}
}
