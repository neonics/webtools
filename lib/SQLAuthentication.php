<?php
require_once __DIR__.'/Authentication.php';
require_once __DIR__.'/db/pdo.php';
/**
 * Implementation using SQL storage engine for IP/nonce and consumer token (consumer_key/consumer_secret) pairs.
 */

class ManagedTable
{
	private $config;

	public function __construct( $db, $name, $config ) {
		$this->db = $db;
		$this->name = $name;
		$this->config = $config;
	}

	public function columns() { return array_keys( $this->columns ); }

	public function create( $optional = "IF NOT EXISTS"  )
	{
		$this->db->exec( implode(' ', //call_user_func_array('array_merge',  // ... // )
		array_map(function($v) {
			return is_array($v) ? implode(' ', $v) : $v ;
		},
		[
			"CREATE TABLE",
			$optional ? "IF NOT EXISTS" : null,
			$this->name,
			"(",
			"PRIMARY KEY",
			"(", array_map( ),
			")",
		]
		)
		) );
	}
}

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
		// tables are created lazily
	}

	/**
	 * Called right before performing queries.
	 * Prevents SQL injection through the $this->table_ fields.
	 */
	final function dbinit( $init_table = null ) {
		Check::identifier( $this->table_sessions );
		Check::identifier( $this->table_tokens );
		
		switch ( $init_table )
		{
			case 'tokens':
			case $this->table_tokens: 
				if( 0 )  // new feature
				{
				$t = new ManagedTable( $this->table_tokens, [
					'columns' => [
						'realm' => "varchar(32) not null",
						'key'		=> "varchar(64) not null",
						'secret'=> "varchar(64) not null",
						'token' => "varchar(128) not null",
					],
					'primary_key' => [ 'realm', 'key' ]
				] );
					$t->create();
				}
				else
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
				break;

			case 'nonce':
			case 'sessions':
			case $this->table_sessions: $this->db->exec( "
					CREATE TABLE IF NOT EXISTS $this->table_sessions(
						nonce		char(40),
						site		varchar(255),
						data		varchar(255),
						host		varchar(255),
						ip			varchar(16),
						".$this->db->identifier('when')." timestamp not null default NOW()
					)
				");
				break;

			default: $this->trace( __METHOD__. ": Warning: no table specified: $init_table" );
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
		$db = $this->dbinit( $this->table_tokens );

		// XXX postgres only
		$db->exec("DELETE FROM $this->table_tokens WHERE NOW() - created > '1h'::interval" );

		$sth = $db->prepare("SELECT * from $this->table_tokens WHERE realm=? AND key=?");
		$sth->execute( [ $realm, $key ] );
		$res = $sth->fetchAll( PDO::FETCH_OBJ );

		if ( empty( $res ) )
		{
			$this->trace( "no tokens, considering creating one.." );
			$user = executeSelectQuery( $db, "users", [ "username" => $key ] );
			if ( count( $user ) == 1 )
			{
				$this->trace( "creating token for user $key" );
				if (
					executeInsertQUery( $db, $this->table_tokens, $row = [
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
		$db = $this->dbinit( $this->table_sessions );
		$timeout = max( intval( $timeout ), 10 );
		$when    = null;
		switch ( $db->driver ) {
			case 'pgsql':#$when = "EXTRACT('epoch' FROM ".$db->identifier('when')." - NOW())"; break;
										$when = "\"when\" - NOW()";
										$timeout = "'{$timeout}s'::interval";
										break;
			case 'mysql': $when = "TIMESTAMPDIFF(SECONDS, `when`, NOW())";
										break;
			default: fatal( "not implemented for $db->driver: timestamp differences" );
		};

		# TODO: "DELETE FROM $this->table_sessions WHERE $when >= $timeout";

		$sth = $db->prepare( $q = "SELECT * FROM $this->table_sessions WHERE ip=? AND nonce=? AND $when < $timeout" );

		$this->trace >= 10 and
		$this->trace( "[$db->driver] $q" );
		$sth->execute( [ $ip = $_SERVER['REMOTE_ADDR'], $nonce ] );
		$res = $sth->fetchAll( PDO::FETCH_ASSOC );

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

		$db = $this->dbinit( $this->table_sessions );

		executeInsertQuery( $db, $this->table_sessions, [
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

}
