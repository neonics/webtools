<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

class AuthModule extends AbstractModule
{
	private $roles = array( "Kenney" => array( "author" ) );

	private $authTable;

	public function __construct()
	{
		parent::__construct( 'auth', "http://neonics.com/2000/xsp/auth" );
	}

	public function setParameters( $xslt )
	{
	}

	public function init()
	{
		global $db, $request; // XXX ref

		psp_module( 'db' );
		$this->authTable = $db->table( "auth", $this->ns );

		if ( $this->isAction( 'logout' ) )
		{
			unset( $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] );
		}


		if ( $this->isAction( 'firstuser' ) )
		{
			$db->begin( 'auth' );

			if ( ! $this->firstrun() )
			{
				$db->rollback( 'auth' );
				return $this->errorMessage( 'Administrator account already created' );
			}
			else
			{
				#echo "Putting new user...";
				$user = $this->newUser(
					$_REQUEST['username'], $_REQUEST['password'], 'admin' );

				$db->put( $this->name, $user );

				#echo str_replace( '<', '&lt;', $this->authTable->saveXML() );
				#echo "Numusers: ". $this->numusers();

				$this->setSessionUser( $user );
			}

			$db->commit( 'auth' );
		}

		if ( $this->isAction( 'login' ) )
		{
			$this->handleLogin();
		}
	}

	private function newUser( $username, $password, $roles )
	{
		$doc = new DOMDocument();
		$user = $doc->createElementNS( $this->ns, 'user' );
		#$user = new DOMElement( $this->name, null, $this->ns );

		$user->setAttribute( 'username', $username );
		$user->setAttribute( 'password', $this->hash( $password ) );
		$user->setAttribute( 'roles', 'admin' );

		return $user;
	}

	private function hash( $s, $m = 'md5' )
	{
		return "$m:" . ($m != 'plain' ? hash( $m, $s ) : $s );
	}

	public function numusers()
	{
		global $db;
		return $db->query( 'auth', "count(/auth:auth/auth:user)" );
	}

	public function firstrun()
	{
		return $this->numusers() == 0;
	}
/*
	private $messages = Array();
	public function messages()
	{
		$m = new DOMDocument();
		foreach ( $this->messages as $v )
			$m->appendChild( $m->importNode( $v, true ) );
		debug( 'auth', 'messages: ' . $m->saveXML() );
		return $m;//->childNodes;
	}
*/

	private function handleLogin()
	{
		global $db;

		foreach ( Array( 'md5', 'plain' ) as $m )
		{
			$user = $db->query( 'auth', 
			// XXX Sanitize	
				sprintf( '/auth:auth/auth:user[@username="%s" and @password="%s"]',
					$_REQUEST["username"],
					$this->hash( $_REQUEST["password"], $m )
				)
			);

			if ( isset( $user ) && $user->length > 0 )
				break;
		}
				// XXX : return single value if query is of that type...
				// XXX that requires metadata, unique values etc....

				$user = isset( $user ) ? $user->item( 0 ) : null;

		if ( isset( $user ) ) 
		{
			$this->setSessionUser( $user );
		}
		else
		{
			// XXX persistent logging (security log)
			debug( 'auth', 'invalid credentials' );
			$this->errorMessage( "Invalid credentials" );
			$_REQUEST['action:auth:show-login']='';
		}
	}

	private function setSessionUser( $user )
	{
		global $db, $request;

#echo "Setting session user id: " . $user->getAttributeNS( $db->ns, 'id' );
		$_SESSION["realm[$request->requestBaseURI]:auth.user.id"] = $user->getAttributeNS( $db->ns, 'id' );
	}

	private function getSessionUser()
	{
		global $db, $request;

		$u= $this->user() ? $db->get( 'auth', $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] ) : null;

		return $u;
	}

	public function listUsers()
	{
		global $db;
		// TODO: Sanitize, roles as list

		return $this->permission( 'admin' ) ? $this->authTable : 
			$this->errorMessage( 'No permission to list users' )
		;
	//		$db->xpath( 'auth', "''" );
	}

	public function user()
	{
		global $request;
		return isset( $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] );
	}

	public function username()
	{
		$user = $this->getSessionUser();
		return $user
			? $user->getAttribute( 'username' )
			: "";
	}

	public function roles()
	{
		$user = $this->getSessionUser();

		return $user
			? $user->getAttribute( 'roles' )
			: "";
	}

	public function permission( $role )
	{
		global $debug;

		$user = $this->getSessionUser();
		if ( !isset ($user) )
		{
			if ( $debug > 2 )
			debug( "No user - can't check permission $role" );
			return false;
		}
#		echo "Session User: id=" . $_SESSION['auth.user.id'].': '. str_replace( '<', '&lt;', $this->authTable->saveXML( $user) ); var_dump( $user);
		$roles = explode( ",", $user->getAttribute( 'roles' ) );

		return $user
			? in_array( $role, $roles )
			|| in_array( 'root', $roles ) # backdoor
			: false;
	}

	function test( $var )
	{
		var_dump( $var );
	}
}

$auth_class = "AuthModule";


?>
