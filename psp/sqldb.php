<?php
/**
 * @author Kenney Westerhof <kenney@neonics.com>
 */
require_once ( 'db/pdo.php' );
require_once ( 'db/meta.php' );

$sqldbNS = "http://neonics.com/2014/db/sql";
class SQLDBModule extends AbstractModule
{
	var $db;

	public function __construct( $args )
	{
		global $sqldbNS;
		parent::__construct( 'sqldb', $sqldbNS );
		debug( "<p>CONSTRUCT " . __CLASS__ . " with args: <pre>" . print_r( $args, 1 ) . "</pre></p>" );

		// translate XSLT argument passing:
		if ( is_array( $args ) && count( $args == 1 ) )
			$args = $args[ 0 ];

		$connconf = array();
		// parse configuration
		foreach ( $args->childNodes as $c )
			$connconf[ $c->nodeName ] = $c->nodeValue;

		$missing = array_filter( explode( ' ', "username password dsn" ), function ( $v ) use($connconf )
		{
			return ! isset( $connconf[ $v ] );
		} );
		if ( count( $missing ) )
			throw new Exception( __CLASS__ . ": missing required SQL connection parameters: " . implode( ', ', $missing ) );

		$this->db = PDODB::init( array(
				'SQLDSN' => $connconf[ 'dsn' ],
				'SQLUSER' => $connconf[ 'username' ],
				'SQLPASS' => $connconf[ 'password' ]
		) );

		debug( $this, "initialized PDO" );
	}

	public function __destruct()
	{
	}

	public function begin( $table )
	{
	}

	public function rollback( $table )
	{
	}

	public function commit( $table )
	{
	}

	public function listusers()
	{
		$doc = new DOMDocument();
		$root = $doc->appendChild( $doc->createElementNS( $this->ns, "db:result" ) );

		$sth = $this->db->prepare( "SELECT * FROM users" );
		$sth->execute();
		while ( $row = $sth->fetch( PDO::FETCH_OBJ ) )
			$root->appendChild( $doc->createElementNS( $this->ns, 'db:user', $row->username ) );
		return $root;
	}

	public function listtables()
	{
		$doc = new DOMDocument();
		$root = $doc->appendChild( $doc->createElementNS( $this->ns, "db:result" ) );

		$tm = db_get_tables_meta( $this->db );
		// $root->appendChild( $doc->createElementNS($this->ns, 'db:table', print_r($tm,1)) );

		foreach ( $tm as $table => $data )
		{
			$root->appendChild( $t = $doc->createElementNS( $this->ns, 'db:table' ) );
			$t->setAttribute( "name", $table );
			if ( isset( $data[ 'inherits' ] ) )
				$t->setAttribute( "inherits", implode( ", ", $data[ 'inherits' ] ) );

				// columns, foreign_keys, inherits

			foreach ( $data[ 'columns' ] as $k => $v )
			{
				$t->appendChild( $c = $doc->createElementNS( $this->ns, "db:column" ) );
				$c->setAttribute( "name", $k );

				foreach ( $v as $n => $d )
					switch ( $n )
					{
						case 'fk_single':
							foreach ( $d as $fk )
							{
								$c->appendChild( $a = $doc->createElementNS( $this->ns, "db:$n" ) );
								$a->setAttribute( "table", $fk[ 0 ] );
								$a->setAttribute( "column", $fk[ 1 ] );
							}
							break;

						default:
							if ( is_array( $d ) )
								$c->appendChild( $doc->createElementNS( $this->ns, "db:$n", print_r( $d, 1 ) ) );
							else
								$c->setAttribute( $n, $d ); // appendChild( $doc->createElement("div", "$n - ".print_r($d,1)));//TextNode( print_r( $v, 1)));
							break;
					}
			}
		}

		// $doc->loadXML( '<p>test!</p>' );
		return $doc->documentElement;
	}

	public function query( $arg )
	{
		$doc = new DOMDocument();

		$my_debug = false;

		if ( $my_debug )
		{
			$doc->appendChild( $root = $doc->createElement( "div" ) );

			//$root->appendChild( $doc->createElement( "pre", print_r( $arg, 1 ) ) );
			$root->appendChild( $doc->createTextNode( "SQL: " . $arg[ 0 ]->getAttribute( "sql" ) ) );
		}
		else
			$root = $doc;

		// html_entity_decode not necessary ( &gt; seems to work in > comparisons ),
		// however, just to be safe:
		$sth = $this->db->prepare( html_entity_decode( $arg[ 0 ]->getAttribute( "sql" ) ) );
		$sth->execute();

		if ( $my_debug )
			$root->appendChild( $doc->createTextNode("NUM RESULTS: " . $sth->rowCount() ) );

		$root->appendChild(  $root = $doc->createElementNS( $this->ns, "db:result" ) );

		$elname = $arg[0]->getAttribute( 'element' );
		$elname = empty($elname) || $elname=='' ? null : $elname;

		while ( $row = $sth->fetch( PDO::FETCH_ASSOC ) )
			$root->appendChild( $this->row2xml( $doc, $row, $elname ) );

		return $doc->documentElement;
	}

	protected function row2xml( $doc, array $row, $entityname = "row" )
	{
		empty( $entityname ) and $entityname = 'row';
		$ret = $doc->createElementNS( $this->ns, "db:$entityname" );
		foreach ( $row as $k => $v )
			$ret->setAttribute( $k, $v );
		return $ret;
	}
}

$sqldb;

function DISABLED_sqldb_init()
{
	global $sqldb, $sqldbNS, $pspBaseDir;

	debug( 'db', "Initializing SQL DB ($sqldbNS)" );
	$sqldb = new SQLDB( $sqldbNS );
}

$sqldb_class = 'SQLDBModule'; // notify PSP of the module class

?>
