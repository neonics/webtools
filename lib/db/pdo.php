<?php
/**
 * PDO Wrapper
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
require_once( 'db/meta.php' );

class PDODB extends PDO
{
	public $dsn;
	public $user;
	public $name;
	public $driver;

	public function __construct( $dsn, $user, $pass, array $attributes = null )
	{
		parent::__construct( $this->dsn = $dsn, $this->user = $user, $pass, $attributes );

		$this->driver = $this->getAttribute( PDO::ATTR_DRIVER_NAME ); // unfortunately dbname is lost

		if ( preg_match( '/^([^:]+):.*?dbname=([^;]+)/', $dsn, $m ) )
		{
			if ( $this->driver != $m[1] )
				warn( "[".__FILE__.":".__LINE__."] DB driver conflict: DSN reports <code>{$m[1]}</code>, PDO reports <code>$this->driver</code>");
			$this->name = $m[2];
		}
		else fatal("can't extract driver/dbname from '$dsn'");
	}

	/**
	 * Generates SQL to append to insert queries to have them return the last_insert_id.
	 * This is a NOP for MySQL, but required for PostgreSQL.
	 *
	 * @return string
	 */
	public function returning() {
		return ""; // temp disable
		return $this->driver == 'pgsql' ? ' RETURNING id' : "";
	}

	/**
	 * Returns the auto_increment/serial value of insert queries. It depends on
	 * $this->returning() to have been appended to the query for PostgreSQL.
	 *
	 * @param unknown $sth
	 * @return string
	 */
	public function last_insert_id( $sth ) {
		if ( 0 )
		return $this->driver == 'pgsql' ? $sth->fetchColumn(0) : $this->lastInsertId();
		else
		{
			switch ( $this->driver )
			{
				case 'pgsql':
					if ( $t = $this->_is_insert( $sth->queryString ) )
					{
						$tm = db_get_tables_meta( $this )[ $t ];
						if ( isset( $tm['columns']['id'] ) ) { // TODO better check for serial columns
							$val = $this->query( "SELECT currval(pg_get_serial_sequence('$t','id'))" )->fetchColumn(0);
							#notice("query <code>".$sth->queryString."</code> insert id <code>$val</code><br/>" );
							return $val;
						}
					}
					#warn( "no <code>id</code> column for <code>$t</code><pre>".print_r($sth,1)."</pre>" );
					return null;

				default:
					return $this->lastInsertId();
			}

		}
	}

	/*
	private $_is_insert = null;

	public function prepare( $sql, $driver_options = array() ) {
		$this->_is_insert = self::_is_insert( $sql );
		return parent::prepare( $sql, $driver_options );
	}
	*/

	private static function _is_insert( $sql ) {
		return preg_match( "/^\s*insert\s+into\s+(\S+)/i", $sql, $m )
			? $m[1]
			: null;
	}

	/**
	 * Factory method.
	 *
	 * @param array $auth associative array with SQLDSN, SQLUSER, and SQLPASS.
	 */
	public static function init( array $connInfo )
	{
		$connInfo = (object) $connInfo;

		$db = new PDODB( $connInfo->SQLDSN, $connInfo->SQLUSER, $connInfo->SQLPASS,
			array(
				PDO::MYSQL_ATTR_INIT_COMMAND	=> "SET NAMES utf8",
				PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION
			)
		)
		or fatal( "error opening database" );

		return $db;
	}


	public function rollback() { return $this->rollBack(); }

	/** array_map function */
	public function fix_sql_value( $v ) {
		switch ( $this->driver )
		{
			case 'pgsql':
				switch ( gettype( $v ) )
				{
					case 'boolean': return $v ? "TRUE":"FALSE";// unfortunately php prints false as ''
					default: return $v;
				}

			default:	return $v;
		}
	}
}

/**
 * produces comma separated string of count($array) question marks for use
 * in 'IN (...)' clauses.
 */
function sql_q_list( $array ) { return substr( str_repeat( ",?", count( $array ) ), 1 ); }
