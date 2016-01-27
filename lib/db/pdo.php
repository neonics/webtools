<?php
/**
 * PDO Wrapper
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
require_once( 'db/meta.php' );
require_once( 'db/upgrade.php' );

class PDODB extends PDO
{
	public $dsn;
	public $user;
	public $name;
	public $driver;

	/**
	 * @Readonly update via db_upgrade( $db, $version, 'auto' );
	 */
	public $prefix = 'auto_';

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

	public function __debugInfo() {
		return array(
			'driver' => $this->driver,
			'dsn' => $this->dsn
		);
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

		if ( isset( $connInfo->prefix ) )
			$db->prefix = rtrim( $connInfo->prefix, '_' ) . '_';

		return $db;
	}


	public function q( $sql, $args = [] ) {
		$sth = $this->prepare( $sql );
		$sth->execute( $args );
		return $sth;
	}



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

	public function identifier( $id ) {
		Check::identifier( $id );

		$q = $this->_id_quote_char();
		return $q . str_replace( $q, "\\$q", $id ) . $q;
	}

	protected function _id_quote_char() {
		switch ( $this->driver )
		{
			case 'pgsql': return '"';
			default:
			case 'mysql': return '`';
		}
	}

}


/* Utility Functions */


/**
 * produces comma separated string of count($array) question marks for use
 * in 'IN (...)' clauses.
 */
function sql_q_list( $array ) { return substr( str_repeat( ",?", count( $array ) ), 1 ); }

/**
 * @returns last_insert_id
 */
function executeInsertQuery( $db, $table, $fields )
{
	static $sth = array();
	static $query = null;

	$sthn = $db->name . "_".$table."_insert:" . md5( implode(":", array_keys($fields) ) );

	if ( ! array_key_exists( $sthn, $sth ) )
	{
		$query = "INSERT INTO $table ("
			. implode( ",", array_keys( $fields ) )
			. ") VALUES ("
			. implode( ",", array_map( function(){ return "?"; }, $fields ) )
			. ")"
		. $db->returning();

		#notice("<b>Query</b>: $query  (db: $db->driver)" );

		if ( ( $sth[ $sthn ] = $db->prepare( $query ) ) === false )
			throw new Exception( "error preparing query '$query': ". implode(':', $db->errorInfo() ) );
	}

	#debug(__FUNCTION__.": $query<pre>".print_r($fields,1)."</pre>" );

	$result = null;

	$arr = array_map( [$db, 'fix_sql_value'], array_values( $fields ) );

	if ( $result = $sth[ $sthn ]->execute( $arr ) )
	{
		debug( __FUNCTION__, "ok" );
	}
	else
	{
		// in case PDOException isn't thrown
		throw new Exception( "Error inserting into $table: ". implode(':', $sth[ $sthn ]->errorInfo() ) );
	}

	$lid = $db->last_insert_id( $sth[ $sthn ] );

	$sth[ $sthn ]->closeCursor(); // xxx not closed on exception
	return $lid !== null && $lid !== false ? $lid : $result;
}


function executeSelectQuery2( $db, $sql, $values = [] ) {
	$sth = $db->prepare( $sql );
	$values = array_map( [$db, 'fix_sql_value'], $values );
	$sth->execute( $values );
	$result = $sth->fetchAll( \PDO::FETCH_ASSOC );
	$sth->closeCursor();
	return $result;
}


/**
 * @param PDODB		$db (PDO will work too)
 * @param string	$table
 * @param array		$where	optional [ 'column' => 'value' ] equality tests; will be joined using AND.
 * @param mixed		$extra	legacy: string, "ORDER BY" etc.,
 *   Or[ $extra => "ORDER BY LIMIT...", $fetch_mode => \PDO::FETCH_OBJ ]
 */
function executeSelectQuery( $db, $table, $where = null, $extra = null )
{
	static $sth = array();

	if ( empty( $extra ) || is_string( $extra ) ) // backwards compat
	$extra = [ 'extra' => $extra ];

	$args = (object) array_merge(
		[
			'extra' => null,
			'fetch_mode' => \PDO::FETCH_ASSOC,
		],
		$extra
	);
	// XXX FIXME: a parse error here can cause a print_r db settings someplace!

	$sthn = $table."_select:" . ( is_null($where) ? "" : implode(':', array_keys( $where ) ) );
#	debug( "(executeSelectQuery: $sthn)" );

	if ( ! array_key_exists( $sthn, $sth ) )
	{
		$sth[ $sthn ] = $db->prepare( $q = "SELECT * FROM $table"
			. ( is_null( $where ) || empty( $where )
				? ""
				: " WHERE " . implode( " AND ", array_map( function($i) { return "$i=?"; }, array_keys( $where ) ) )
				)
			. " $args->extra"	// order by, limit etc.
		);
#		debug( "sthn: $sthn; " . print_r( $sth[ $sthn ], true ) );
	}

	$result = null;
	$values = is_null( $where ) ? [] : array_map( [$db, 'fix_sql_value'], array_values( $where ) );
	if ( ( $result = $sth[ $sthn ]->execute( $values ) ) !== false )
	{
#		debug( "queried $table; numrows: ".$sth[ $sthn ]->rowCount() );
		$result = $sth[ $sthn ]->fetchAll( $args->fetch_mode );
	}
	else
		throw new Exception( "Error querying $table: " . implode(':', $sth[ $sthn ]->errorInfo() ) );

	$sth[ $sthn ]->closeCursor();

	return $result;
}

function executeSelectQueryRequireSingle( $db, $table, $where, $extra = null ) {
	$rows = executeSelectQuery( $db, $table, $where, $extra);

	if ( $rows === false || $rows === null )
		throw new Exception( "no rows for $table using where: " .
			implode( " AND ", array_map( function($i) { return "$i=".$where[$i]; }, array_keys( $where ) ) )
		);

	if ( count($rows) != 1 )
		throw new Exception( "expected 1 row for query on $table, got ".count( $rows ) );

	return $rows[0];
}


/**
 * @param $table table name
 * @param $where hash
 * @param $update hash
 * @return num rows updated
 */
function executeUpdateQuery( $db, $table, $where, $update )
{
	static $sth = array();
	#echo "<pre>".print_r(func_get_args(),1)."</pre>";

	$sthn = $table."_update_" . md5( implode( '_', array_keys( $where ) ) . '_SET_' . implode('_', array_keys( $update ) ) );
#	debug( "(executeUpdateQuery: $sthn)" );

	if ( ! array_key_exists( $sthn, $sth ) )
	{
		$q=null;
		$sth[ $sthn ] = $db->prepare( $q="UPDATE $table SET "
			. implode( ", ", array_map( function($i) { return "$i=?"; }, array_keys( $update ) ) )
			." WHERE "
			. implode( " AND ", array_map( function($i) { return "$i=?"; }, array_keys( $where ) ) )
		);
	#	echo fold_debug(__FUNCTION__.": $q" );
	#	debug( "sthn: $sthn; " . print_r( $sth[ $sthn ], true ) );
	# debug ("prepared query: <b>$q</b>" );
	}

	$result = null;
	$arr = array_map( [$db, 'fix_sql_value'],
		array_merge( array_values( $update ), array_values( $where ) )
	);

	#echo "<pre>"; foreach ($arr as $k=>$v) { echo "$k=>$v: ". (is_null($v)?'NULL':'')."\n"; } echo "</pre>";
	if ( $result = $sth[ $sthn ]->execute( $arr ) )
		{}#echo "queried $table; numrows: ".$sth[ $sthn ]->rowCount(). "\n";
	else
		throw new Exception( "Error updating $table: " . implode(':', $sth[ $sthn ]->errorInfo() ) );


	$r = $sth[ $sthn]->rowCount();

	#debug("result: ".($result===false?"FALSE":$result) . " rowcount: $r");

	$sth[ $sthn ]->closeCursor();
	return $r;
}


function executeDeleteQuery( $db, $table, $where )
{
	static $sth = array();
	if ( $where == null || ! is_array( $where ) || ! count( $where ) )
		fatal("refusing delete without WHERE clause at ".__FILE__);

	$sthn = $table."_delete:" . ( is_null($where) ? "" : implode(':', array_keys( $where ) ) );

	if ( ! array_key_exists( $sthn, $sth ) )
	{
		$sth[ $sthn ] = $db->prepare( $q = "DELETE FROM $table WHERE "
			. implode( " AND ", array_map( function($i) { return "$i=?"; }, array_keys( $where ) ) )
		);
		#echo "<pre>$q</pre>";
	}

	$result = null;
	$arr = array_map( [$db, 'fix_sql_value'], array_values( $where ) );
	if ( ( $result = $sth[ $sthn ]->execute( $arr ) ) !== false )
	{
		$result = $sth[ $sthn ]->rowCount();
	}
	else
		throw new Exception( "Error querying $table: " . implode(':', $sth[ $sthn ]->errorInfo() ) );

	$sth[ $sthn ]->closeCursor();

	return $result;

}

