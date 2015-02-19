<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */
class SecurityException extends Exception
{
	public function __construct( $msg ) { parent::__construct( $msg ); }
}

class AuthModule extends AbstractModule
{
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
		global $xmldb, $request; // XXX ref

		Session::start();	// auth requires session

		ModuleManager::loadModule( "psp" );
		psp_module( 'db' );
		$this->authTable = $xmldb->table( "auth", $this->ns );

		if ( $this->isAction( 'logout' ) )
		{
			unset( $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] );
			Session::destroy();

			foreach ( $_COOKIE as $k=>$v )
				unset( $_COOKIE[$k] );

			header( 'HTTP/1.1 302 Logout Redirect' );
			header( 'Location: ' . $request->requestBaseURI );
			exit();
		}


		if ( $this->isAction( 'firstuser' ) )
		{
			$xmldb->begin( 'auth' );

			if ( ! $this->firstrun() )
			{
				$xmldb->rollback( 'auth' );
				return $this->errorMessage( 'Administrator account already created' );
			}
			else
			{
				#echo "Putting new user...";
				$user = $this->newUser(
					$_REQUEST['username'], $_REQUEST['password'], 'admin' );

				$xmldb->put( $this->name, $user );

				#echo str_replace( '<', '&lt;', $this->authTable->saveXML() );
				#echo "Numusers: ". $this->numusers();

				$this->setSessionUser( $user );
			}

			$xmldb->commit( 'auth' );
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

	private function hash( $s, $m = 'sha1' )
	{
		return "$m:" . ($m != 'plain' ? hash( $m, $s ) : $s );
	}

	public function challenge()
	{
		return $this->setSessionChallenge( hash( 'sha1', mt_rand() ) );
	}

	public function numusers()
	{
		global $xmldb;
		return $xmldb->query( 'auth', "count(/auth:auth/auth:user)" );
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
		global $xmldb;

		$user = null;

		try
		{
			$username = gad( $_REQUEST, 'username', null );

			if ( !isset( $username ) )
				throw new SecurityException( "missing username" );

			if ( strpbrk( $username, "&[]'\"<>/\\" ) )
				throw new SecurityException( "username contains invalid characters: " . $username );

			$user = $xmldb->query( 'auth',
				'/auth:auth/auth:user[@username="' . $username . '"]'
			);
			$user = isset( $user ) && $user->length > 0 ? $user->item(0) : null;

			if ( !isset( $user ) )
				throw new SecurityException( 'login attempt for unknown username: '.$username );


			# sanity check complete: the authentication is for an existing user.

			# determine authentication method:
			if ( isset( $_REQUEST["auth:challenge"] ) )
			{
				if ( $this->getSessionChallenge() != $_REQUEST["auth:challenge"] )
					throw new SecurityException( 'challenge mismatch: expect '.$this->getSessionChallenge() . ", got " . $_REQUEST['auth:challenge'] );

				# split the password field into hash-id and value
				$passhash;
				{
					$p = $user->getAttribute('password');

					if ( ( $sep = strpos( $p, ':' ) ) === false )
						throw new SecurityException( "password field corruption for user " . $username . ": no hash separator" );

					$hashtype = substr( $p, 0, $sep );
					$p = substr( $p, $sep +1 );

					if ( $hashtype != 'sha1' )
						if ( $hashtype == 'plain' )
							$p = hash( 'sha1', $p );
						else
						{
							throw new SecurityException( "password not sha1 for user " . $username .
								"; login prohibited." );
						}

					$passhash = $p;
				}

				$h = hash( 'sha1', $_REQUEST['auth:challenge'] . $passhash );

				debug('auth', "calculated: $h");
				if ( $h == $_REQUEST["password"] )
				{
					debug('auth', "security info: authenticated user ". $user->getAttribute("username")
						. " from " . $_SERVER["REMOTE_ADDR"] );
				}
				else
				{
					debug('auth', "security info: invalid credentials for user ". $user->getAttribute("username")
						. " from " . $_SERVER["REMOTE_ADDR"] );
					$user = null;
				}
			}
			else
			{
				# legacy
				$myuser = $user;
				$user = null;

				foreach ( Array( 'sha1', 'md5', 'plain' ) as $m )
				{
					if ( $user->getAttribute('password') == $this->hash( $_REQUEST["password"], $m ) )
					{
						$user = $myuser;
						break;
					}
				}
			}


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
		catch ( SecurityException $e )
		{
			debug( 'auth', "security warning: " . $e->getMessage()
				. ' from ' . $_SERVER['REMOTE_ADDR'] );
			$this->errorMessage( "Invalid credentials (".$e->getMessage().")" );
		}
	}

	private function setSessionChallenge( $v )
	{
		global $request;
		return $_SESSION["realm[$request->requestBaseURI]:auth.challenge"] = $v;
	}

	private function getSessionChallenge()
	{
		global $request;
		return gad( $_SESSION, "realm[$request->requestBaseURI]:auth.challenge", null );
	}

	private function setSessionUser( $user )
	{
		global $xmldb, $request;

#echo "Setting session user id: " . $user->getAttributeNS( $xmldb->ns, 'id' );
		$_SESSION["realm[$request->requestBaseURI]:auth.user.id"] = $user->getAttributeNS( $xmldb->ns, 'id' );
	}

	private function getSessionUser()
	{
		global $xmldb, $request;

		$u= $this->user() ? $xmldb->get( 'auth', $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] ) : null;

		return $u;
	}

	public function listUsers()
	{
		global $xmldb;
		// TODO: Sanitize, roles as list

		return $this->permission( 'admin' ) ? $this->authTable :
			$this->errorMessage( 'No permission to list users' )
		;
	//		$xmldb->xpath( 'auth', "''" );
	}

	public function user()
	{
		global $request;
		return !empty( $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] );
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
			: "(no user)";
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
