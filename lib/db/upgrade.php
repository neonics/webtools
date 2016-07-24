<?php
/**
 * Database Model Version Control
 * ==============================
 *
 * Functional Basis for database metadata management.
 *
 * Intended for managing changes in the database structure,
 * such as adding, removing, or modifying tables and columns,
 * but can be used as a basis for managing content aswell.
 *
 *
 * Usage Example 1:
 *
 *   db_upgrade( $db [, 0 ] );
 *
 * This will ensure a managed name/value table 'auto_options' exists
 * by checking the existence of a row named 'db_meta_version'
 * one entry 'db_meta_version' => 0 (that must not be modified) exists,
 *
 *
 *
 * Usage Example 2:
 *
 *   db_upgrade( $db, 1 );
 *
 *   function db_upgrade_v1( $db, $options_table ) {}
 *
 */


/** Included from pdo.php, but can be used standalone */
require_once 'Util.php';	// for gd, notice etc.
require_once 'Check.php';	// preventing caller SQL injection
require_once 'action.php'; // UI interaction via ?action:


/**
 * Establishes an options table and records the non-exceptional execution
 * of callback functions up to a given integer $db_version.
 *
 * Note that this function emits 'sys_warning/notice/error' messages.
 *
 * @param PDODB $db The database to operate upon.
 *
 * @param int $db_version default 0; Complex versions are not implemented.
 *
 * Specifying a higher db_version than 0 requires you to write
 * callback functions named `db_upgrade_v\d+( $db, $options_table )`
 * where `\d` ranges from 1 to `$version`.
 *
 * @param string $prefix default 'auto'; Must be formatted as an identifier.
 *
 * The table prefix to use for managed tables.
 *
 * @param string $upgrader (optional) Name of a class or namespace containing db_upgrade_vXX() static methods/functions.
 */
function db_upgrade( $db, $db_version = 0, $table_prefix = 'auto', $upgrader = null )
{
	$db_version	= max( 0, intval( $db_version ) );
	if ( $table_prefix === null ) $table_prefix = 'auto';
	$db->prefix = Check::identifier( $prefix = rtrim( $table_prefix , '_' ) . '_' );
	$table = $db->prefix . 'options';

	$options = db_get_auto_options( $db, $table );

	if ( ( $oldv = $cur_version = gad( gad( $options, 'db_version' ), 'value', -1 ) ) < $db_version )
	{
		if ( $err = gad( $options, 'db_upgrade_error' ) )
		{
			if ( isAction( 'db-upgrade-retry' ) ) {
				executeDeleteQuery( $db, $table, [ 'name' => 'db_upgrade_error' ] );
				$err = null;
			}
			else
			{
				debug( 'sys_warning', "A previous database upgrade failed: <pre>".$err['value']."</pre>"
					#. "<a href='?action:db-upgrade-retry'>Retry?</a>"
					. "<form method='post'><button name='action:db-upgrade-retry'>Retry?</button>"
				);
			}
		}

		if ( ! $err )
		{
			debug( 'sys_notice', "Upgrading database from version $cur_version to $db_version, one moment please..." );
			flush();

			while( ++$cur_version <= $db_version )
				try
				{
					debug( 'sys_notice', "Upgrading to version $cur_version" );
					$db->beginTransaction();
					$m = "db_upgrade_v$cur_version";
					$callable = $cur_version === 0 ? $m : (
						$upgrader === null ? $m :
						( class_exists( $upgrader )
						? [ $upgrader, $m ]
						: "$upgrader\\$m"
						)
					);
					db_upgrade_invoke( $db, $table, $callable );
					executeUpdateQuery( $db, $table, [ 'name' => 'db_version' ], ['value' => $cur_version ] );
					$db->commit();
				}
				catch ( \Exception $e )
				{
					debug( 'sys_error', "Upgrade to version $cur_version failed: " . $e->getMessage()
					. ( $e instanceof PDOException ? "<pre>$db->last_query</pre>" : null )
					. ( "<pre>$e</pre>" )
					);
					$db->rollback();
					executeInsertQuery( $db, $table, [
						'name' => 'db_upgrade_error',
						'value' => $e->getMessage(),
						'autoload' => 1
					] );
					break;
				}
			$cur_version--;

			debug( 'sys_notice', "Updating database metadata...");
			flush();
			db_clear_meta( $db );

			if ( $oldv < $cur_version )
			{
				debug( 'sys_notice', "Upgraded database $db->name to version $cur_version"
				. ( $cur_version==$db_version ? ". Database up to date!" : " (current version is $db_version)" ) );
			}
		}
	}
}

function db_get_auto_options( PDODB $db, $table = null )
{
	if ( $table === null )
		$table = $db->prefix . 'options';

	static $__db_option_cache = [];
	$cache_key = $db->dsn . '|' . $table;
	if ( isset( $__db_option_cache[ $cache_key ] ) )
		return $__db_option_cache[ $cache_key ];

	try
	{
		if ( array_key_exists( $table, db_get_tables_meta( $db ) ) )
			$options = executeSelectQuery( $db, $table, ['autoload'=>1] );
		else
			$options = [];
	}
	catch (PDOException $e)
	{
		$options = [];
	}

	return $__db_option_cache[ $cache_key ] = array_hash( $options, 'name' );
}

function db_upgrade_invoke( $db, $table, $callable )
{
	if ( is_string( $callable ) && ! function_exists( $callable ) )
		throw new Exception( "Missing DB upgrade function <code>$callable</code>" );
	if ( !is_callable( $callable ) )
		throw new Exception( __FUNCTION__ . ": Argument not callable: " . print_r( $callable, 1 ) );

	try
	{
		$db->validate_meta = false;	// suppress column existence
		$callable( $db, $table );
		$db->validate_meta = true;
	}
	catch ( \Exception $e )
	{
		$db->validate_meta = true;
		throw $e;
	}
}


function db_upgrade_v0( $db, $table )
{
	foreach ( array_merge( [
		"DROP TABLE IF EXISTS $table",
		"CREATE TABLE $table (
			name			varchar(128) NOT NULL PRIMARY KEY,
			value			text,
			autoload	int not null default 0,
			updated		timestamp NOT NULL DEFAULT NOW()"
			. ( $db->driver == 'mysql' ? " ON UPDATE NOW()" : null ) . "
		)",
	 ],
	 ( $db->driver != 'pgsql' ? [] : [
	 	"CREATE OR REPLACE FUNCTION update_updated_column()
			RETURNS TRIGGER AS $$
			BEGIN
				 IF row(NEW.*) IS DISTINCT FROM row(OLD.*) THEN
						NEW.updated = now(); 
						RETURN NEW;
				 ELSE
						RETURN OLD;
				 END IF;
			END;
			$$ language 'plpgsql'
		",
		"CREATE TRIGGER update_{$table}_updated
		 BEFORE UPDATE ON $table
		 FOR EACH ROW EXECUTE PROCEDURE update_updated_column()
		"
	 ] )
	 ) as $q )
		$db->exec( $q );

	db_clear_meta( $db );

	executeInsertQuery( $db, $table, $option = [
		'name'			=> 'db_version',
		'value'			=> '0',
		'autoload'	=> 1
	] );

	$options = [ $option ]; // executeSelectQuery( $db, $table, ['autoload'=>1] );

	return [ $option ];
}
