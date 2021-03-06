<?php
namespace db\meta {

use \Check;
use \PDODB;
use \PDO;
use \Exception;
use \DirectoryResource;

$_db_tables_meta_cache = [];

/**
 * clears the db meta cache.
 */
function db_clear_meta( $db = null )
{
	$tmpdir = DirectoryResource::findFile( "", 'default', 'tmp' );

	if ( $db !== null )
	{
		if ( file_exists( $x = "$tmpdir/_cache_metadb_" . _db_get_cache_id( $db ) ) )
		{
			unlink( $x );
			global $_db_tables_meta_cache;
			unset( $_db_tables_meta_cache[ _db_get_cache_id( $db ) ] );
			db_get_tables_meta( $db );
		}
	}
	else
		foreach ( scandir( $tmpdir ) as $f )
		{
			if ( is_file( $x = "$tmpdir/$f" ) && strpos( $f, '_cache_metadb_' ) === 0 )
				unlink( $x );
		}
}

function _db_get_cache_id( PDODB $db ) {
	return preg_replace( '@[^\w\d_-]+@', '_', $db->dsn );
}


/**
 * fetches information from the SQL standard information_schema,
 * and extends it with pg_catalog inheritance information IF the driver is PostgreSQL.
 *
 * @param PDODB $db
 * @param string $for_table optional, the table name. When given, returns only the metadata for the given table.
 * @return nested array structure with table info: [ table_name => [ 'columns' => [...], ...] ]
 */
function db_get_tables_meta( $db, $for_table = null )
{
	global $_db_tables_meta_cache;
	$db_cache_id = _db_get_cache_id( $db );

	if ( ! isset( $_db_tables_meta_cache[ $db_cache_id ] ) )
	{
		$timing = microtime(true);

		$dbid = $db->getAttribute( PDO::ATTR_DRIVER_NAME ); // unfortunately dbname is lost
		$dbid = _db_get_cache_id( $db );
		#echo "<pre>DBID: $dbid  ($db->dsn)</pre>";

		$tmpdir = rtrim( DirectoryResource::findFile( "", 'default', 'tmp' ), '/' );
		if ( !is_dir( $tmpdir ) ) throw new Exception("Cannot find temporary directory: not a directory: '$tmpdir' (create a 'tmp' dir!)" );
		if ( file_exists( $cf = "$tmpdir/_cache_metadb_$dbid" ) ) // filemtime( $cf )  - now > ....
		{
			$tables = json_decode( file_get_contents( $cf ), true );
		}
		else
		{
			$rows	= db_get_rows( $db );
			$fkrows = db_get_references( $db );
			$keys   = db_get_keys( $db );

			$tables = array();
			foreach ( $rows as $r )
			{
				// add lowercase copies of the fields (postgres=lc, mysql=uc)
				foreach ( array_keys( (array) $r ) as $a )
				{
					$fn = strtolower( $a );
					$r->$fn = $r->$a;
				}

				#echo "<pre>".print_r( $r, true )."</pre>";
				$arr = array();

				$row_arr = (array) $r;
				foreach ( explode(' ', "ordinal_position table_name column_name column_default is_nullable data_type column_default character_maximum_length numeric_precision" ) as $k )
				{
					$ku= strtoupper( $k );
					if ( array_key_exists( $k, $row_arr ) )//!empty( $r->$k ) )
						$arr[ $k ] = $r->$k;
					elseif ( array_key_exists( $ku, $row_arr ) )//!empty( $r->$ku ) )
					$arr[ $k ] = $r->$ku;
				}

				//if ( isset( $fkrows[ $arr['table_name'] ][ $arr['column_name'] ] ) )
				// XXX overwrites!
				//	$arr['foreign_key'] = $fkrows[ $arr['table_name'] ][$arr['column_name'] ];

				if ( isset( $fkrows[ $arr['table_name']] ) )
				{
					$tables[ $arr['table_name'] ]['foreign_keys'] = $fkrows[ $arr['table_name']];

					foreach ( $fkrows[$arr['table_name']] as $fkname =>$fk )
					{
						//die("....".print_r($fk,1));
						//			$arr['foreign_keys'][ $fkname ] = (array)$fk;//$fkrows[ $arr['table_name'] ][$arr['column_name'] ];

						foreach ( $fk as $fki => $fka )
							if ( $fka->fk_column_name == $arr['column_name'])
							{
								$arr[
										count( $fk ) == 1
										? 'fk_single'
										: 'fk_multi'
								][ $fkname ] = array($fka->referenced_table_name, $fka->referenced_column_name);

								if ( count( $fk )== 1 )
									$arr['foreign_key'] =  $fka;
							}
					}
				}

				$tables[ $arr['table_name'] ]['columns'][ $arr['column_name'] ] = $arr; //$r;
			}

			foreach ( $keys as $table => $columns )
				$tables[ $table ]['keys'] = $columns;

			foreach ( db_get_primary_keys( $db ) as $table => $col )
				$tables[ $table ]['primary_key'] = $col;

			foreach ( db_get_indices( $db ) as $table => $indices ) {
				$tables[ $table ]['indices'] = $indices;
				$tables[ $table ]['index_columns'] = array_unique( call_user_func_array( 'array_merge', array_values( $indices ) ) );
			}

			foreach ( db_get_inheritance( $db ) as $r )
			{
				if ( ! isset( $tables[$r->sub]['inherits'] )
						|| ! in_array( $r->super, $tables[$r->sub]['inherits'] ) ) // duplication due to rows
					$tables[ $r->sub ]['inherits'][] = $r->super;

				// update the child column info with the parent
				$tables[ $r->sub ]['columns'][ $r->colname ] = &$tables[ $r->super ]['columns'][ $r->colname ];
			}

			//echo "<pre><b>META</b>\n".print_r( $tables, 1 )."</pre>";
			//die();
			file_put_contents( $cf, $tables = json_encode( $tables ) );
			$tables = json_decode( $tables, true ); // make sure everything is array()
		}

		$_db_tables_meta_cache[ $db_cache_id ] = $tables;

		$timing = sprintf("%.3f", ( microtime(true) - $timing ) * 1000 );
		debug( 'lib/db', "<code>fetched metadata in $timing ms</code>" );
	}

	return $for_table !== null
		? gad( $_db_tables_meta_cache[ $db_cache_id ], $for_table )
		: $_db_tables_meta_cache[ $db_cache_id ];
}


/// internal

function db_get_rows( $db )
{
	return $db
	->query("SELECT * FROM information_schema.columns WHERE table_schema NOT IN ( 'information_schema', 'pg_catalog' ) ORDER BY ordinal_position")
	->fetchAll( PDO::FETCH_OBJ );
}

function db_get_primary_keys( $db )
{
	$sth = $db->prepare( <<<SQL
SELECT k.column_name, t.table_name
FROM information_schema.table_constraints t
JOIN information_schema.key_column_usage k
USING(constraint_name,table_schema,table_name)
WHERE t.constraint_type='PRIMARY KEY'
  AND t.table_schema=?
SQL
#  AND t.table_name=?
	);
	$sth->execute( array( $db->name ) );

	$ret = array();
	foreach ( $sth->fetchAll( PDO::FETCH_ASSOC ) as $i => $row )
		if ( isset( $ret[ $row['table_name'] ] ) )
		{
			if ( is_array( $ret[ $row['table_name'] ] ) )
				$ret[ $row['table_name'] ][] = $row[ 'column_name'];
			else
				$ret[ $row['table_name'] ] = [ $ret[ $row['table_name'] ], $row[ 'column_name'] ];
		}
		else
			$ret[ $row['table_name'] ] = $row[ 'column_name'];

	return $ret;
}

/**
 * NOTE: produces simple flat list of columns involved in keys, no multi-column key info.
 * This is the bare minimum in order to detect required columns; if a column is defined
 * as a NOT NULL KEY with a default value of '' it's unlikely that insert queries
 * will work properly without specifying unique values for the column.
 *
 * @return array( tablename => array( key columns ) )
 */
function db_get_keys( $db )
{
	// XXX constraint_schema = table_schema = dbname
	// XXX referenced_table_schema is not null for foreign keys - we want KEY, UNIQUE, and PRIMARY KEY.
	$sth = $db->prepare( <<<SQL
	SELECT  *
	FROM information_schema.key_column_usage
	WHERE constraint_schema = ?
	AND position_in_unique_constraint IS NULL
SQL
			//referenced_table_schema IS NULL -- mysql only (not postgres)
	);
	$sth->execute( array( $db->name ) );
	$rows = $sth->fetchAll( PDO::FETCH_ASSOC );

	$ret = array();
	foreach ( $rows as $i => $row )
	{
		$row = (object) array_combine(
				array_map( 'strtolower', array_keys( $row ) ),
				array_values( $row )
		);

		$tn = $row->table_name;
		$cn = $row->column_name;

		$ret[$tn] = gd( $ret[$tn], array() );
		if ( ! in_array( $cn, $ret[$tn] ) )
			$ret[$tn][] = $cn;
	}

	return $ret;
}

function db_get_indices( $db )
{
	switch ( $db->driver )
	{

		case 'pgsql':
			return [];	// TODO


		case 'mysql':
		// XXX constraint_schema = table_schema = dbname
		// XXX referenced_table_schema is not null for foreign keys - we want KEY, UNIQUE, and PRIMARY KEY.

		$sth = $db->prepare( <<<SQL
		SELECT  *
		FROM information_schema.statistics
		WHERE table_schema = ? and index_schema = table_schema
		AND NON_UNIQUE <> 0 
SQL
				//referenced_table_schema IS NULL -- mysql only (not postgres)
		);
		$sth->execute( array( $db->name ) );
		$rows = $sth->fetchAll( PDO::FETCH_ASSOC );

		$ret = array();
		foreach ( $rows as $i => $row )
		{
			$row = (object) array_combine(
					array_map( 'strtolower', array_keys( $row ) ),
					array_values( $row )
			);

			$tn = $row->table_name;
			$cn = $row->column_name;
			$in = $row->index_name;

			$ret[ $tn ][ $in ][] = $cn;
		}

		return $ret;
	}
}




/**
 * Returns all unique column names (which includes singular PK columns).
 * @return array( tablename => array( unique_columns ) )
 */
function db_get_table_constraints( $db )
{
	// XXX constraint_schema = table_schema = dbname
	$sth = $db->prepare( <<<SQL
	SELECT  *
	FROM information_schema.table_constraints
	WHERE constraint_schema = ?
SQL
	);
	$sth->execute( array( $db->name ) );
	$rows = $sth->fetchAll( PDO::FETCH_ASSOC );

	$ret = array();
	foreach ( $rows as $i => $row )
	{
		$row = (object) array_combine(
				array_map( 'strtolower', array_keys( $row ) ),
				array_values( $row )
		);

		switch ( $row->constraint_type )
		{
			case 'PRIMARY KEY': break;// constraint_name 'PRIMARY' - useless
			case 'FOREIGN KEY': break;// fk_.... names,handled with db_get_references differently
			case 'UNIQUE':	// constraint_name is the column name
				$ret[ $row->table_name ][] = $row->constraint_name;
				break;
			default: debug( 'db', "unknown constraint type: $row->constraint_type" );	# XXX warn not in this lib!
		}
	}

	die ( print_r( $ret, 1 ) );

	return $ret;
}

function db_get_references( $db )
{
	// XXX FIXME - mysql has exra rc.table_name/referenced_table_name which psql does not.

	//NOTE:
	// mysql stores dbname in constaint_schema, (and 'def' in constraint_catalog);
	// psql stores dbname in  constraint_catalog (and 'public' in constraint_schema)

	$query = "";
	switch ( $db->driver )
	{
		//https://bowerstudios.com/node/1052

		// here,
		// tc.table_catalog is the database name, (postgres specific), and
		// tc.constraint_schema is the schema name ('public' etc) - we don't filter it.
		case 'pgsql':$query=<<<SQL
		SELECT
				tc.constraint_name AS fk_constraint_name,
				tc.table_name      AS fk_table_name,
				kcu.column_name    AS fk_column_name,
				ccu.table_name     AS referenced_table_name,
				ccu.column_name    AS referenced_column_name,
				tc.table_catalog,
				kcu.ordinal_position op, kcu.position_in_unique_constraint pu

		FROM information_schema.table_constraints tc
		JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
		JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
		WHERE constraint_type = 'FOREIGN KEY'
		AND tc.table_catalog = ?
SQL;
		break;

		case 'mysql':
			// here, there is no table_catalog,
			// and constraint_schema is database name.
			$query=
			/* simple (too simple?)
			 <<<SQL
			 SELECT	constraint_name fk_constraint_name,
			 table_name		fk_table_name,
			 column_name		fk_column_name,
			 ordinal_position,
			 referenced_table_name,
			 referenced_column_name
			 FROM	information_schema.key_column_usage
			 WHERE	constraint_schema = ?
			 SQL;
			 */
			// this query is correct for mysql but not for psql
			// the original query did not have the table_name/referenced_table_name ON clauses
			<<<SQL
			 SELECT
			 kcu1.constraint_name    AS fk_constraint_name,
			 kcu1.table_name         AS fk_table_name,
			 kcu1.column_name        AS fk_column_name,
			 kcu1.ordinal_position   AS fk_ordinal_position,
			 kcu2.constraint_name    AS referenced_constraint_name,
			 kcu2.table_name         AS referenced_table_name,
			 kcu2.column_name        AS referenced_column_name,
			 kcu2.ordinal_position   AS referenced_ordinal_position

			 FROM information_schema.referential_constraints rc

			 LEFT JOIN information_schema.key_column_usage   AS kcu1
			 ON  kcu1.constraint_catalog     = rc.constraint_catalog
			 AND kcu1.constraint_schema      = rc.constraint_schema
			 AND kcu1.constraint_name        = rc.constraint_name
			 AND kcu1.table_name             = rc.table_name

			 LEFT JOIN information_schema.key_column_usage   AS kcu2
			 ON  kcu2.constraint_catalog      = rc.unique_constraint_catalog
			 AND kcu2.constraint_schema      = rc.unique_constraint_schema
			 AND kcu2.constraint_name        = rc.unique_constraint_name
			 AND kcu2.ordinal_position       = kcu1.ordinal_position
			 AND kcu2.table_name             = rc.referenced_table_name

			 WHERE rc.constraint_schema = ?
SQL;


			/* ORIGINAL - duplicates! first left join ok
			 <<<SQL
			 SELECT
			 kcu1.constraint_name    AS fk_constraint_name,
			 kcu1.table_name         AS fk_table_name,
			 kcu1.column_name        AS fk_column_name,
			 kcu1.ordinal_position   AS fk_ordinal_position,
			 kcu2.constraint_name    AS referenced_constraint_name,
			 kcu2.table_name         AS referenced_table_name,
			 kcu2.column_name        AS referenced_column_name,
			 kcu2.ordinal_position   AS referenced_ordinal_position

			 FROM information_schema.referential_constraints rc

			 LEFT JOIN information_schema.key_column_usage   AS kcu1
			 ON  kcu1.constraint_catalog     = rc.constraint_catalog
			 AND kcu1.constraint_schema      = rc.constraint_schema
			 AND kcu1.constraint_name        = rc.constraint_name

			 LEFT JOIN information_schema.key_column_usage   AS kcu2
			 ON  kcu2.constraint_catalog      = rc.unique_constraint_catalog
			 AND kcu2.constraint_schema      = rc.unique_constraint_schema
			 AND kcu2.constraint_name        = rc.unique_constraint_name
			 AND kcu2.ordinal_position       = kcu1.ordinal_position

			 WHERE rc.constraint_schema = ?
			 SQL;
			 */
			break;

		default:die("metadb not implemented for $db->driver");
	}




	/*
	 \d information_schema.key_column_usage
	 View "information_schema.key_column_usage"
		Column             |                Type                | Modifiers
		-------------------------------+------------------------------------+-----------
		constraint_catalog            | information_schema.sql_identifier  |
		constraint_schema             | information_schema.sql_identifier  |
		constraint_name               | information_schema.sql_identifier  |
		table_catalog                 | information_schema.sql_identifier  |
		table_schema                  | information_schema.sql_identifier  |
		table_name                    | information_schema.sql_identifier  |
		column_name                   | information_schema.sql_identifier  |
		ordinal_position              | information_schema.cardinal_number |
		position_in_unique_constraint | information_schema.cardinal_number |
		*/
	//simple approach - works in mysql, not psql again...
	/*
	 <<<SQL
	 */


	$sth = $db->prepare( $query );
	$sth->execute( array( $db->name ) );
	$rows = $sth->fetchAll( PDO::FETCH_OBJ );

	// reorder the rows for easy lookup.
	$ret = array();
	foreach ( $rows as $row )
		$ret[ $row->fk_table_name ][ $row->fk_constraint_name ][] = $row;

	//echo "<pre><b>foreign keys:</b>\n".print_r($ret,true)."</pre>";
	return $ret;
}

function db_get_inheritance( $db )
{
	if ( $db->getAttribute( PDO::ATTR_DRIVER_NAME ) != 'pgsql' )
		return array();

	// process PostgreSQL inheritance:
	$sth = $db->prepare( //"SELECT * FROM pg_catalog.pg_tablespace_oid_index
			// this lists all tables and their parents
			#"select i.*, r.relname sub, p.relname super from pg_inherits i left join pg_class r on r.oid = i.inhrelid left join pg_class p on p.oid = i.inhparent"

			// idem, but also lists inherited columns
			"
		SELECT i.*, r.relname sub, p.relname super, a.attname colname
		FROM pg_inherits i
		LEFT JOIN pg_class r ON r.oid = i.inhrelid
		LEFT JOIN pg_class p ON p.oid = i.inhparent
		LEFT JOIN pg_attribute a  ON i.inhparent=a.attrelid
		WHERE a.attnum>0
		"
	);

	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_OBJ);
}

/** only drops a column if it exists */
function db_meta_drop_column( $db, $table, $column )
{
	Check::identifier( $table );
	Check::identifier( $column );
	// mysql
	foreach ( [
		"drop procedure if exists schema_change",
		"create procedure schema_change() begin
			if exists (select * from information_schema.columns where table_name = '$table' and column_name = '$column') then
					alter table $table drop column $column;
			end if;
		end",
		"call schema_change()",
		"drop procedure if exists schema_change"
	] as $q )
		$db->query( $q );
}

function db_meta_drop_constraint( $db, $fromtable, $fromcolumn, $totable, $tocolumn )
{
	$m = db_get_tables_meta( $db, $fromtable );
	$fromcolumn = is_array( $fromcolumn ) ? $fromcolumn : [ $fromcolumn ];
	$tocolumn = is_array( $tocolumn ) ? $tocolumn : [ $tocolumn ];

	foreach ( gad( $m, 'foreign_keys', [] ) as $fk => $ar )
	{
		if ( count( $ar ) != count( $fromcolumn ) )
			continue;

		$match = true;

		foreach ( $ar as $i => $constraint )
			if ( $constraint['fk_column_name'] == $fromcolumn[$i]
				&& $constraint['referenced_table_name'] == $totable
				&& $constraint['referenced_column_name'] == $tocolumn[$i]
			)
			;
			else
			{
				$match = false;
				break;
			}

		if ( $match )
		{
			$db->query( "ALTER TABLE $fromtable DROP FOREIGN KEY ".$constraint['fk_constraint_name'] );
			return true;
		}
	}
	return false;
}

}

// backwards compatibility
namespace {
	function db_clear_meta( $db = null ) { return \db\meta\db_clear_meta( $db ); }
	function _db_get_cache_id( PDODB $db ) { return \db\meta\_db_get_cache_id( $db ); }
	function db_get_tables_meta( $db, $for_table = null ) { return \db\meta\db_get_tables_meta( $db, $for_table ); }
	function db_get_rows( $db ) { return \db\meta\db_get_rows( $db ); }
	function db_get_primary_keys( $db ) { return \db\meta\db_get_primary_keys( $db ); }
	function db_get_keys( $db ) { return \db\meta\db_get_keys( $db ); }
	function db_get_indices( $db ) { return \db\meta\db_get_indices( $db ); }
	function db_get_table_constraints( $db ) { return \db\meta\db_get_table_constraints( $db ); }
	function db_get_references( $db ) { return \db\meta\db_get_references( $db ); }
	function db_get_inheritance( $db ) { return \db\meta\db_get_inheritance( $db ); }
	function db_meta_drop_column( $db, $table, $column ) { return \db\meta\db_meta_drop_column( $db, $table, $column ); }
	function db_meta_drop_constraint( $db, $fromtable, $fromcolumn, $totable, $tocolumn ) { return \db\meta\db_meta_drop_constraint( $db, $fromtable, $fromcolumn, $totable, $tocolumn ); }
}
