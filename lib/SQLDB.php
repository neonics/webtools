<?php
class WikiArticlesTable extends DBTable
{
	public function __construct( $dbh, $ns, $pfx, $name, $tablename )
	{
	debug( 'sqldb', "NAMESPACE: $ns; pfx: $pfx; tablename: $tablename" );
		parent::__construct(
			$dbh,
			array
			(
				'ns' => $ns,
				'prefix' => $pfx,
				'name' => $name,
				'tablename' => $tablename,
				'columns' => array
				(
					'id' => array
					(
						'type' => 'int',
						'sql' => 'serial not null primary key',
						'xml' => 'attribute',
						'key' => true
					),
					'title' => array
					(
						'type' => 'string',
						'sql' => 'varchar(255) not null',
						'xml' => 'attribute'
					),
					'date' => array
					(
						'type' => 'date',
						'sql' => 'date',
						'xml' => 'attribute'
					),
					'status' => array
					(
						'type' => 'string',
						'sql' => 'varchar(64)',
						'xml' => 'attribute'
					),
					'text' => array
					(
						'type' => 'string',
						'sql' => 'text',
						'xml' => 'element'
					)
				),
				'virtual' => array (
					'xmltext' => array
					(
						'type' => 'virtual',
						'xml' => 'element'
					)
				)
			)
		);
	}

	/** WikiParser */
	public function getXmlText()
	{
		debug( 'sqldb', "[table] getText" );
		$text = $this->row[ 'text' ];
		$text = preg_replace( "/\[(.*?)\]/", "<a href='\${1}'>\${1}</a>", $text );
		return $text;
	}
}


class DBTable
{
	protected $row;
	public $definition;
	private static $definitions = array();
	private $dbh;

	public function __construct( $dbh, $definition )
	{
		$this->dbh = $dbh;
		$this->init( $definition );

		$this->row = array();
		foreach ( $definition["columns"] as $cn => $c )
		{
			$this->row[$cn] = null;
		}

	}

	private function init( $definition )
	{
		if ( ! array_key_exists( $definition['tablename'], self::$definitions ) )
		{
			$this->definition = self::$definitions[ $definition[ 'tablename' ] ] = $definition;

			$this->create();
		}

		$this->definition = $definition;
	}

	public function __get( $fieldName )
	{
		if ( isset( $this->row ) && array_key_exists( $fieldName, $this->row ) )
		{
			return $this->row[$fieldName];
		}
		return FALSE;
	}

	public function __set( $fieldName, $value )
	{
	debug( 'sqldb', "Set $fieldName to '$value'" );
		if ( isset( $this->row ) && array_key_exists( $fieldName, $this->row ) )
		{
			$this->row[$fieldName] = $value;
		}
	}

	/** Abstract base method for getX() methods that return the value
	 * to be used in the toXML() method for any specific column.
	 * Thus, for instance, the wikitext is plain text, which then
	 * is reformatted into an XML string in the method getText.
	 */
	public function __call( $name, $args )
	{
		if ( startsWith( $name, "get" ) )
		{
			$field = strtolower( substr( $name, 3 ) );
			return $this->row[ $field ];
		}

		debug( 'sqldb', "Unknown method: $name( ".implode(', ', $args) );
	}

	public function create( $sqlFile = null )
	{
		try
		{
			$a = $this->dbh->query( "select count(*) from " . $this->definition['tablename'] );

			if ( ! $a )
			{
				$sql = null;

				if ( isset( $sqlFile ) )
				{
					$sql = file_get_contents( dirname( __FILE__ )."/wiki.sql" );
				}
				else
				{
					$sql = $this->makeCreateQuery();
				}
				
				$this->dbh->exec( $sql );
			}
		}
		catch ( PDOException $e )
		{
			ModuleManager::errorMessage( $e->getMessage() );
		}
	}

	private function makeCreateQuery()
	{
		$cols = "";
		foreach ( $this->definition["columns"] as $cn => $c )
		{
			$cols[] = "$cn ".$c['sql']."\n";
		}
	
		$sql = "create table " . $this->definition["tablename"] . " (\n"
		 .implode( ",\n", $cols )
		 . ");";

		debug( 'sqldb', "SQL: $sql" );

		return $sql;
	}

	public function toXML( $addxmlns = false )
	{
		$atts = "";
		$els = "";
		$pfx = $this->definition["prefix"];
		$tn = $this->definition["name"];
		$ns = $this->definition["ns"];

		// TODO: filter out columns that are not in the query
		$processCols = array_merge( $this->definition["columns"],
			$this->definition["virtual"] );
		foreach ( $processCols as $cn => $c )
		{
			$mname = "get$cn";
			$value = $this->$mname(); // $this->row[$cn];

			switch ( gad( $c, "xml", "element" ) )
			{
				case "element":
					$els.="  <$pfx:$cn>".$value."</$pfx:$cn>\n";
					break;
				case "attribute":
					$atts .= " $cn=\"" . htmlspecialchars( $value ) . "\"";
					break;
			}
		}


		$result = "<$pfx:$tn".($addxmlns?" xmlns:$pfx=\"$ns\"":"")."$atts>$els</$pfx:$tn>\n";

		debug( 'sqldb', "Result: " . htmlspecialchars( $result ) );
		return $result;
	}

	public function index()
	{

		$cols=array();
		foreach ( $this->definition["columns"] as $cn => $cd )
		{
			if ( $cd["xml"] == 'attribute' )
			{
				$cols[] = $cn;
			}
		}

		$q = "select ".implode(',', $cols)
			." from ". $this->definition['tablename']." order by ".$cols[0].";";

		$pfx = $this->definition['prefix'];
		$ns = $this->definition['ns'];
		$result = "<?xml version='1.0'?>\n\n";
		$result = "<$pfx:index xmlns:$pfx='$ns'>\n";

		$qr = $this->dbh->query( $q, PDO::FETCH_INTO, $this );

		#var_dump( $qr );

		foreach ( $qr as $row )
		{
			$result .= $row->toXML();
		}

		$result .= "</wiki:index>\n";

		debug( 'sqldb', "RESULT: ".htmlspecialchars($result) );
		
		$dd = new DOMDocument(); $dd->loadXML( $result );
		return $dd->documentElement;
	}

	public function get( $keys )
	{
		$q = "select * from ".$this->definition['tablename']." where ";

		$args = array();
		foreach ( $keys as $k => $v )
		{
			$q .= " $k = :$k";
			$args[ ":$k" ] = $v;
		}

		debug("Query: $q");

		$sth = $this->dbh->prepare( $q );
		$sth->setFetchMode( PDO::FETCH_INTO, $this );

		if ( ! $sth->execute( $args ) ) 
		{
			ModuleManager::errorMessage( implode(' ', $this->dbh->errorInfo() ) );
			return null;
		}

		return $sth->fetch();
	}

	public function getXML( $keys )
	{
		$row = $this->get( $keys );

		$result;

		if ( $row == null )
		{
			$atts = array(); foreach ( $keys as $k => $v ) { $atts[] = "$k='".htmlspecialchars( $v ) . "'"; }
			$result = "<db:result size='0' xmlns:db='".SQLDB::$ns."' "
				. implode( " ", $atts )
			."/>";
		}
		else
		{
			$result = $row->toXML( true );
			debug( 'sqldb', "Result: " . htmlspecialchars( $result ) );
		}
		$dd = new DOMDocument(); $dd->loadXML( $result );
		return $dd->documentElement;
	}

	public function store()
	{
		$cols = array();
		$keys = array();
		$colnames = array();
		$vals = array();
		foreach ( $this->definition["columns"] as $cn => $cd )
		{
			if ( gad( $cd, 'key', false ) )
				$keys[] = "$cn = :$cn";
			else
			{
				$cols[] = "$cn = :$cn";
				$colnames[] = $cn;
				$vals[ $cn ] = ":$cn"; # $this->row[ $cn ];
			}
		}

		$q;

		$args;

		if ( $this->id == null )
		{
			$q = "INSERT INTO " . $this->definition["tablename"] 
				. "(" . implode(', ', $colnames) . ") VALUES ( ". implode(', ', $vals ) . ");";

			$args = $colnames;
		}
		else
		{
			$q = "UPDATE " . $this->definition["tablename"] . " SET "
				. implode( ', ', $cols )
				. " WHERE " . implode( " AND ", $keys ) . ";";

			$args = array_keys( $this->definition["columns"] );
		}

		debug( 'sqldb', "Query: $q" );
			
		try
		{
			$sth = $this->dbh->prepare( $q );

			foreach ( $args as $cn )
			{
				$sth->bindParam( ":$cn", $this->row[ $cn ] );
			}

			$ret = $sth->execute();
			debug("Update query executed: $ret");
			#var_dump( $ret );
		}
		catch ( PDOException $e )
		{
			debug( 'sqldb', "Error: " . $e->getMessage() );
			ModuleManager::errorMessage( 'sqldb', $e->getMessage() );
		}
	}

	public function delete()
	{
		if ( gad( $this->row, 'id', null ) != null )
		{
			debug( 'sqldb', "Deleting page id $this->id" );

			$sth = $this->dbh->prepare( "DELETE FROM " . $this->definition["tablename"]
				. " WHERE id = :id" );
			$sth->bindParam( ':id', $this->row['id'] );
			$ret = $sth->execute();
			debug( 'sqldb', "Delete: $ret" );
		}
		else
		{
			debug( 'sqldb', "Not deleting - no primary key" );
		}
	}
}


class SQLDB
{
	public $dbh;
	public static $ns = "http://neonics.com/2011/db/xml";

	private $dbi = array( 
		'dsn' => 'pgsql:host=X;dbname=Y',
		'user'=> 'USERNAME',
		'password'=>'PASSWORD',
		'options'=>array( PDO::ATTR_AUTOCOMMIT => FALSE ),
		'pfx' => 'table_prefix_'
	);

	public function __construct( $dbi )
	{
		$this->dbh = self::connect( $dbi );
	}

	public static function connect( $dbi )
	{
		$dbi['options'][ PDO::ATTR_PERSISTENT ] = true;
		return new PDO( $dbi['dsn'], $dbi['user'], $dbi['password'], $dbi['options']);
	}

	private function hash( $s, $m = 'md5' )
	{
		return "$m:" . ($m != 'plain' ? hash( $m, $s ) : $s );
	}
}
?>
