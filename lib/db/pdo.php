<?php
/**
 * PDO Wrapper
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
class PDODB extends PDO
{
	public $dsn;
	public $name;
	public $driver;

	public function __construct( $dsn, $user, $pass, array $attributes = null )
	{
		parent::__construct( $this->dsn = $dsn, $user, $pass, $attributes );

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
		return $this->driver == 'pgsql' ? $sth->fetchColumn(0) : $this->lastInsertId();
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
}