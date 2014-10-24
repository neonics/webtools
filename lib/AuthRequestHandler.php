<?php
/** see handle.php and Template.php for template_do() */

class AuthRequestHandler extends RequestHandler
{
	protected function _handle( $request )
	{
		if ( !preg_match( "/\.(css|js|png|jpe?g|gif|woff|ttf|ico)$/", $request->requestFileURI ) )
		{
			ModuleManager::loadModule( "auth" );
			if ( ! auth_user() )
				$request->requestRelURI = "auth.html";
		}

		return false;	// continue processing
	}
}
