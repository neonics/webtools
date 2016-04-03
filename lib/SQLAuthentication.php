<?php
require_once __DIR__.'/Authentication.php';
require_once __DIR__.'/db/pdo.php';
/**
 * Implementation using SQL storage engine for IP/nonce and consumer token (consumer_key/consumer_secret) pairs.
 */

class SQLAuthentication extends Authentication
{
	/**
	 * Session table name: stores nonces.
	 */
	protected $table_sessions = 'auto_oauth_sessions';

	/**
	 * Token table name: stores realm/consumer_key/consumer_secret.
	 */
	protected $table_tokens  = 'auto_oauth_tokens';


	/**
	 * Do not read. Call $this->dbinit();
	 */
	private $db;

	public function __construct( PDODB $db )
	{
		parent::__construct();
		$this->db = $db;
		$this->dbinit();
	}

	/**
	 * Make sure required tables exist by examining the db metadata.
	 */
	private function dbinit() {
		Check::identifier( $this->table_sessions );
		Check::identifier( $this->table_tokens );

		$change = 0;
		if ( ! gad( db_get_tables_meta( $this->db ), $this->table_tokens ) )
		{
			$this->trace( "creating $this->table_tokens" );
			$this->db->exec( "
				CREATE TABLE IF NOT EXISTS $this->table_tokens(
					realm		varchar(32) not null,
					token		varchar(128) not null,
					key			varchar(64) not null,
					secret	varchar(64) not null,
					created	timestamptz not null default NOW(),
					PRIMARY KEY ( realm, token, key )
				)
			" );
			$change++;
		}

		if ( ! gad( db_get_tables_meta( $this->db ), $this->table_sessions ) )
		{
			$this->db->exec( "
				CREATE TABLE IF NOT EXISTS $this->table_sessions(
					nonce		char(40),
					site		varchar(255),
					data		varchar(255),
					host		varchar(255),
					ip			varchar(16),
					".$this->db->identifier('when')." timestamp not null default NOW()
				)
			");
			$change++;
		}

		if ( $change ) {
			db_clear_meta( $this->db );
			db_get_tables_meta( $this->db );
		}

		return $this->db;
	}

	/**
	 * @Implement
	 * @return an array with either 0 or 1 elements (or more if the DELETE is scheduled).
	 */
	protected function fetch_consumer_tokens( $realm, $key )
	{
		#echo "<pre>".__FUNCTION__."</pre>";

		// XXX postgres only
		$this->db->exec("DELETE FROM $this->table_tokens WHERE NOW() - created > '1h'::interval" );

		$sth = $this->db->prepare("SELECT * from $this->table_tokens WHERE realm=? AND key=?");
		$sth->execute( [ $realm, $key ] );
		$res = $sth->fetchAll( PDO::FETCH_OBJ );

		if ( empty( $res ) )
		{
			$this->trace( "no tokens, considering creating one.." );
			$user = executeSelectQuery( $this->db, "users", [ "username" => $key ] );
			if ( count( $user ) == 1 )
			{
				$this->trace( "creating token for user $key" );
				if (
					executeInsertQUery( $this->db, $this->table_tokens, $row = [
						"realm"		=> $realm,
						"key"			=> $key,
						"secret" 	=> $user[0]['password'],	// XXX FIXME TODO issue token
						"token"		=> "TODO-".time(),
					] )
				) return [ (object) $row ];
			}
		}
		#echo "<pre>".__METHOD__."($realm, $key): ".print_r($res,1)."</pre>";
		return $res;
	}

	/**
	 * @Implement
	 * @param string $nonce a nonce value as created by $this->create_nonce();
	 * @param int a timeout in seconds; a minimum of 10s applies.
	 * @return array of nonce structures. Only the size of the array is used by the caller.
	 */
	function fetch_nonce( $nonce, $timeout = 60 )
	{
		$timeout = max( intval( $timeout ), 10 );
		$when    = $this->db->sql_timestampdiff( 'when', 'NOW()', 'SECONDS' );
		$timeout = $this->db->sql_interval( $timeout, 'SECONDS' );

		$this->db->query( $q = "DELETE FROM $this->table_sessions WHERE $when >= $timeout" );

		$sth = $this->db->prepare( $q = "
			SELECT * FROM $this->table_sessions
			WHERE ip=? AND nonce=? AND $when < $timeout
		" );

		$this->trace >= 10 and
		$this->trace( "[{$this->db->driver}] $q" );
		$sth->execute( [ $ip = $_SERVER['REMOTE_ADDR'], $nonce ] );
		$res = $sth->fetchAll( PDO::FETCH_ASSOC );

		$this->trace and
		$this->trace("NONCE check for ip=$ip, nonce=$nonce: "
		.($this->trace > 1 ? "Got ".count($res). " results":(count($res)==1?"Ok":"Not found")),
			$this->trace > 3 ? $res : null
		);

		return $res;
	}

	/**
	 * @Override
	 *
	 * @param string $key			a optional classname for the nonce (i.e. peer site name)
	 * @param string $value		some optional information to associate with this nonce. Max 255 chars.
	 * @return the generated nonce, associated with REMOTE_ADDR.
	 */
	function create_nonce( $key = null, $value = null )
	{
		$nonce = parent::create_nonce();

		executeInsertQuery( $this->db, $this->table_sessions, [
			"nonce" => $nonce,
			"site"	=> $key,
			"data"	=> $value,
			"host"	=> $h = gad( $_SERVER, 'REMOTE_HOST') ? $h : gethostbyaddr( $_SERVER['REMOTE_ADDR'] ),
			"ip"		=> $_SERVER['REMOTE_ADDR'],
		] );

		return $nonce;
	}

	public function init_consumer( $realm, $consumer_key )
	{
		return $this->fetch_consumer_tokens( $realm, $consumer_key );
	}

	var $authenticated_user_id;

	/** @Override */
	public function process_headers() {
		$this->authenticated_user_id = null;
		if ( ! parent::process_headers() )
			return false;

		global $request;
		$_SESSION[ "realm[$request->requestBaseURI]:auth.user.id" ] = 
		$this->authenticated_user_id = executeSelectQueryRequireSingle( $this->db, 'users', [ 'username' => $this->authenticated_user ] )['id'];
		return true;
	}
}
