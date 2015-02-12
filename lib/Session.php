<?php

/*
// requires PHP 5.4+
class MySessionHandler implements SessionHandlerInterface
{
	private static $_initialized = false;

	public static function init()
	{
		if ( self::$_initialized )
			return;
		session_set_save_handler( new MySessionHandler(), true );
		self::$_initialize = true;
	}

	//abstract public bool close ( void )
	//abstract public bool destroy ( string $session_id )
	//abstract public bool gc ( string $maxlifetime )
	//abstract public bool open( string $save_path , string $name )
	//abstract public string read ( string $session_id )
	//abstract public bool write ( string $session_id , string $session_data )

}
*/

//

class Session
{
	/**
	 * Maximum session idletime duration
	 */
	public static $SESSION_TIMEOUT = 1800;

	/**
	 * Maximum age of a session before a new session ID is generated.
	 */
	public static $SESSION_REGENERATE_TIMEOUT = 1800;



	public static function start()
	{
		if ( ! isset( $_SESSION ) || session_status() === PHP_SESSION_NONE ) // catch duplicate calls
			session_start();
		// else warn

		$time = time();

		if ( ! isset( $_SESSION['__CREATED__'] ) )
			$_SESSION['__CREATED__'] = $time;
		else if ( $time - $_SESSION['__CREATED__'] > self::$SESSION_REGENERATE_TIMEOUT )
		{
			session_regenerate_id(true);
			$_SESSION['__CREATED__'] = $time;
		}

		if ( isset( $_SESSION['__ACCESSED__'] ) && $time - $_SESSION['__ACCESSED__'] > self::$SESSION_TIMEOUT )
		{
			session_unset();
			session_destroy();
		}

		$_SESSION['__ACCESSED__'] = $time;


		//file_put_contents("/tmp/webtools-session.log", "[".date('c',$time)."] [{$_SERVER['REQUEST_URI']}] start session ".session_id(). "\n", FILE_APPEND);
	}

	/**
	 * Release the session. Resume with the start() method to access $_SESSION.
	 * This frees any locks held on session files.
	 */
	public static function close()
	{
		//file_put_contents("/tmp/webtools-session.log", "[".date('c')."] [{$_SERVER['REQUEST_URI']}] close session ".session_id(). "\n", FILE_APPEND);
		session_write_close();
	}

	public static function destroy()
	{
		//file_put_contents("/tmp/webtools-session.log", "[".date('c')."] [{$_SERVER['REQUEST_URI']}] end session ".session_id(). "\n", FILE_APPEND);
		foreach ( $_SESSION as $k=>$v )
			unset( $_SESSION[$k] );
		session_unset();	// new - obsoletes the above loop.
		session_destroy();
	}

}
