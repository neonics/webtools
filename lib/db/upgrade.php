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
 * Specifying a higher db_version than 0 REQUIRES*) you to write
 * callback functions named db_upgrade_v\d+( $db, $options_table )
 * where \d ranges from 1 to $version.
 *
 * @param string $prefix default 'auto'; Must be formatted as an identifier.
 *
 * The table prefix to use for managed tables.
 *
 * *) REQUIRES: fatal()**) is called otherwise
 * **) (default implementation in Debug.php)
 */
function db_upgrade( $db, $db_version = 0, $table_prefix = 'auto' )
{
	$db_version	= max( 0, intval( $db_version ) );
	$db->prefix = Check::identifier( $prefix = rtrim( $table_prefix , '_' ) . '_' );
	$table = $db->prefix . 'options';

	$options = db_get_auto_options( $db, $table );

	if ( ( $oldv = $cur_version = gad( gad( $options, 'db_version' ), 'value', 0) ) < $db_version )
	{
		if ( $err = gad( $options, 'db_upgrade_error' ) )
		{
			if ( isAction( 'db-upgrade-retry' ) ) {
				executeDeleteQuery( $db, $table, [ 'name' => 'db_upgrade_error' ] );
				$err = null;
			}
			else
				debug( 'sys_warning', "A previous database upgrade failed: <pre>".$err['value']."</pre>"
					. "<a href='?action:db-upgrade-retry'>Retry?</a>"
				);
		}

		if ( ! $err )
		{
			debug( 'sys_notice', "Upgrading database from version $cur_version to $db_version, one moment please..." );
			flush();

			while( ++$cur_version <= $db_version )
				try
				{
					debug( 'sys_notice', "Upgrading to version $cur_version" );
					db_upgrade_invoke( $db, $table, "db_upgrade_v$cur_version" );
					executeUpdateQuery( $db, $table, [ 'name' => 'db_version' ], ['value' => $cur_version ] );
				}
				catch ( \Exception $e )
				{
					debug( 'sys_error', "Upgrade to $cur_version failed: " . $e->getMessage() );
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

function db_get_auto_options( $db, $table )
{
	static $__db_option_cache = null;
	if ( $__db_option_cache !== null )
		return $__db_option_cache;

	try
	{
		$options = executeSelectQuery( $db, $table, ['autoload'=>1] );
	}
	catch (Exception $e)
	{
		$options = [];
	}

	return $__db_option_cache = array_hash( $options, 'name' );
}

function db_upgrade_invoke( $db, $table, $callable )
{
	if ( is_string( $callable ) && ! function_exists( $callable ) )
		throw new Exception( "Missing DB upgrade function <code>$callable</code>" );
	if ( !is_callable( $callable ) )
		throw new Exception( __FUNCTION__ . ": Argument not callable" );

	$callable( $db, $table );
}


function db_upgrade_v0( $db, $table )
{
	foreach ( [
		"DROP TABLE IF EXISTS $table",
		"CREATE TABLE $table (
			name			varchar(128) NOT NULL PRIMARY KEY,
			value			text,
			autoload	int not null default 0,
			updated		timestamp NOT NULL DEFAULT NOW() ON UPDATE NOW()
		)",
	 ] as $q )
		$db->exec( $q );

	executeInsertQuery( $db, $table, $option = [
		'name'			=> 'db_version',
		'value'			=> '0',
		'autoload'	=> 1
	] );

	db_clear_meta( $db );

	$options = [ $option ]; // executeSelectQuery( $db, $table, ['autoload'=>1] );

	return [ $option ];
}
