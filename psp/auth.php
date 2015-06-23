<?php
/**
 * Authentication and Authorisation module.
 *
 * By default the built-in XMLDB storage provider (db/auth.xml) is used.
 * To use SQL set $pspAuthStorage to 'sql|DSN|USERNAME|PASSWORD' prior to
 * loading the Auth module.
 *
 * @author: Kenney Westerhof <kenney@neonics.com>
 */
require_once( 'Session.php' );

class SecurityException extends Exception
{
	public function __construct( $msg ) { parent::__construct( $msg ); }
}

abstract class AbstractAuthModule extends AbstractModule
{

	protected $db;

	protected function __construct( $db = null )
	{
		parent::__construct( 'auth', "http://neonics.com/2000/xsp/auth" );
		if ( null !== $db )
			$this->db = $db;
	}

	public function setParameters( $xslt )
	{
	}

	protected abstract function _init();
	protected abstract function _db_begin();
	protected abstract function _db_commit();
	protected abstract function _db_rollback();
	protected abstract function _get_user_by_id( $id );
	protected abstract function _get_user_by_name( $username );
	protected abstract function _get_user_id( $user );
	protected abstract function _get_username( $user );
	protected abstract function _get_roles( $user );
	protected abstract function _get_numusers();
	protected abstract function _listUsers();
	protected abstract function _list_roles();

	public function init()
	{
		global $request; // XXX ref

		Session::start();	// auth requires session

		ModuleManager::loadModule( "psp" );
		$this->_init();

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
			$this->_db_begin();

			if ( ! $this->firstrun() )
			{
				$this->_db_rollback();
				return $this->errorMessage( 'Administrator account already created' );
			}
			else
			{
				#echo "Putting new user...";
				$user = $this->newUser(
					$_REQUEST['username'], $_REQUEST['password'], 'admin' );

				$xmldb->put( $this->name, $user );	// XXX

				#echo str_replace( '<', '&lt;', $this->authTable->saveXML() );
				#echo "Numusers: ". $this->numusers();

				$this->setSessionUser( $user );
			}

			$this->_db_commit();
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
		return $this->_get_numusers();
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

			$user = $this->_get_user_by_name( $username );

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
					$p = $this->_get_password( $user );

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
					debug('auth', "security info: authenticated user ". $this->_get_username( $user )
						. " from " . $_SERVER["REMOTE_ADDR"] );
				}
				else
				{
					debug('auth', "security info: invalid credentials for user ". $this->_get_username( $user )
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
					if ( $this->_get_password( $user ) == $this->hash( $_REQUEST["password"], $m ) )
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
		$_SESSION["realm[$request->requestBaseURI]:auth.user.id"] = $this->_get_user_id( $user );
	}

	private function getSessionUser()
	{
		global $request;

		$u= $this->user() ? $this->_get_user_by_id( $_SESSION["realm[$request->requestBaseURI]:auth.user.id"] ) : null;

		return $u;
	}

	public function listUsers()
	{
		// TODO: Sanitize, roles as list
		return $this->permission( 'admin' ) ? $this->_listUsers() :
			$this->errorMessage( 'No permission to list users' )
		;
	//		$xmldb->xpath( 'auth', "''" );
	}

	public function listRoles() {
		return $this->role( 'admin' ) || $this->permission( 'list-roles' )
		? $this->_list_roles()
		: $this->errorMessage( 'No permission to list roles' );
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
			? $this->_get_username( $user )
			: "";
	}

	public function roles()
	{
		$user = $this->getSessionUser();
		return $user ? $this->_get_roles( $user ) : null;
	}

	public function xml_roles()
	{
		$user = $this->getSessionUser();

		$doc = new DOMDocument();
		$x_roles = $doc->createElementNS( $this->ns, 'roles' );

		if ( $user )
		{
			foreach ( $this->_get_roles( $user ) as $role )
			{
				$r = $doc->createElementNS( $this->ns, 'role' );
				$x_roles->appendChild( $r );
				$r->setAttribute( 'name', $role );
			}
		}

		return $x_roles;//$doc->documentElement();
	}

	public function role( $role )
	{
		global $debug;

		$user = $this->getSessionUser();
		if ( !isset ($user) )
		{
			if ( $debug > 2 )
			debug( "No user - can't check role '$role'" );
			return false;
		}
#		echo "Session User: id=" . $_SESSION['auth.user.id'].': '. str_replace( '<', '&lt;', $this->authTable->saveXML( $user) ); var_dump( $user);
		$roles = $this->_get_roles( $user );

		return $user
			? in_array( $role, $roles )
			|| in_array( 'root', $roles ) # backdoor
			: false;
	}

	public function permission( $role )
	{
		return $this->role( $role );
	}

	function test( $var )
	{
		var_dump( $var );
	}
}

class XMLDBAuthModule extends AbstractAuthModule
{
	public function __construct()
	{
		parent::__construct();
	}

	private $authTable;

	protected function _init()
	{
		global $xmldb;
		psp_module( 'db' );
		$this->db = $xmldb;
		$this->authTable = $this->db->table( "auth", $this->ns );
	}

	protected function _db_begin() {
		return $this->db->begin( 'auth' );
	}

	protected function _db_commit() {
		return $this->db->commit( 'auth' );
	}

	protected function _db_rollback() {
		return $this->db->rollback( 'auth' );
	}

	protected function _get_user_by_id( $id ) {
		return $this->db->get( 'auth', $id );
	}

	protected function _get_username( $user )
	{
		return $user->getAttribute( 'username' );
	}

	protected function _get_roles( $user )
	{
		return explode( ",", $user->getAttribute( 'roles' ) );
	}

	protected function _get_numusers()
	{
		return $this->db->query( 'auth', "count(/auth:auth/auth:user)" );
	}

	protected function _listUsers() {
		return $this->authTable;
	}

	protected function _list_roles() {
		return $this->errorMessage( "XMLDB does not support a roles table yet" );
	}


	protected function _get_user_by_name( $username )
	{
		$user = $this->db->query( 'auth',
			'/auth:auth/auth:user[@username="' . $username . '"]'
		);
		return $user = isset( $user ) && $user->length > 0 ? $user->item(0) : null;
	}

	protected function _get_user_id( $user )
	{
		return $user->getAttributeNS( $this->db->ns, 'id' );
	}

	protected function _get_password( $user ) {
		return $user->getAttribute('password');
	}
}


class SQLDBAuthModule extends AbstractAuthModule
{
	static $db_conninfo;

	public function __construct()
	{
		parent::__construct( PDODB::init( self::$db_conninfo ) );
	}

	protected function _init()
	{
	}

	protected function _db_begin() {
		return $this->db->begin();
	}

	protected function _db_commit() {
		return $this->db->commit();
	}

	protected function _db_rollback() {
		return $this->db->rollback();
	}

	protected function _get_user_by_id( $id ) {
		$sth = $this->db->prepare( "SELECT * FROM users WHERE id=?" );
		$sth->execute( array( $id ) );
		return $sth->rowCount()
			? $sth->fetch( \PDO::FETCH_ASSOC )
			: null;
	}

	protected function _get_username( $user )
	{
		return $user['username'];
	}


	protected function _get_roles( $user )
	{
		$sth = $this->db->prepare( "
			SELECT name
			FROM user_roles
			LEFT JOIN roles ON user_roles.role_id = roles.id
			WHERE user_roles.user_id = ?
		" );
		$sth->execute( array( $user['id'] ) );
		return $sth->fetchAll( \PDO::FETCH_COLUMN, 0 );
	}

	protected function _get_numusers()
	{
		$sth = $this->db->prepare( "SELECT COUNT(*) FROM users" );
		$sth->execute();
		return $sth->fetchColumn();
	}

	protected function _listUsers()
	{
		$sth = $this->db->prepare( "SELECT * FROM users" );
		$sth->execute();

		$dom = new DOMDocument();
		$top = $dom->createElementNS( $this->ns, 'auth' );

		while ( $row = $sth->fetch( \PDO::FETCH_ASSOC ) )
		{
			$u = $dom->createElementNS( $this->ns, 'user' );
			$top->appendChild( $u );
			foreach ( explode(' ', 'id username' ) as $c )
				$u->setAttribute( $c, $row[ $c ] );

			$u->setAttribute( 'roles', implode(',', $this->_get_roles( $row ) ) );
		}

		return $top;
	}

	protected function _list_roles() {
		$sth = $this->db->prepare( "SELECT * FROM roles" );
		$sth->execute();

		$dom = new DOMDocument();
		$top = $dom->createElementNS( $this->ns, 'roles' );

		$sthp = $this->db->prepare( "SELECT * FROM permissions" );
		$sthp->execute();

		while ( $row = $sth->fetch( \PDO::FETCH_ASSOC ) )
		{
			$u = $dom->createElementNS( $this->ns, 'role' );
			$top->appendChild( $u );
			foreach ( $row as $k => $v )
				$u->setAttribute( $k, $v );

		}

		return $top;
	}

	protected function _get_user_by_name( $username )
	{
		$sth = $this->db->prepare( "SELECT * FROM users WHERE username=?" );
		$sth->execute( [ $username ] );
		if ( $sth->rowCount() )
			return $sth->fetch( \PDO::FETCH_ASSOC );
		else
			return null;
	}

	/*...
	protected function _xml_get_user_by_name( $username )
	{
		if ( $row = $this->_get_user_by_name( $username ) )
		{
			$dom = new DOMDocument();
			$u = $dom->createElementNS( $this->ns, 'user' );
			foreach ( explode(' ', 'id username' ) as $c )
				$u->setAttribute( $c, $row[ $c ] );
			$u->setAttribute( 'password', $row['password_type'] . ':' . $row['password'] );
			return $u;
		}
		else
			return null;
	}
	*/

	protected function _get_user_id( $user )
	{
		return $user['id'];
	}

	protected function _get_password( $user ) {
		return $user['password_type'] . ':' . $user['password'];
	}
}

$auth_class = "XMLDBAuthModule";

global $pspAuthStorage;
if ( isset( $pspAuthStorage ) )
{
	$vals = explode( '|', $pspAuthStorage );
	echo "<pre>".print_r($vals,1)."</pre>";
	switch ( $vals[0] )
	{
		case 'xml':
			$auth_class = 'XMLDBAuthModule';
			break;
		case 'sql':
			require_once 'db/pdo.php';
			$auth_class = 'SQLDBAuthModule';
			SQLDBAuthModule::$db_conninfo = array_combine(
				array( 'SQLDSN', 'SQLUSER', 'SQLPASS' ),
				array_slice( $vals, 1 )
			);
			break;
		default:
			fatal( "unknown value for \$pspAuthStorage: '{$vals[0]}'; expect either 'xml' or 'sql'" );
	}
}


class AuthStorage {
}


class XMLDBAuth extends AuthStorage {
}

class SQLDBAuth extends AuthStorage {
}

?>
