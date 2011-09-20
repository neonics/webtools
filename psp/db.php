<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

$dbNS = "http://neonics.com/2011/db/xml";

class XMLDB {

	public $base;
	public $ns;

	public function __construct( $base, $ns )
	{
		#parent::__construct( 'db', $ns );
		$this->ns = $ns;
		$this->base = $base;
	}

	private $tables = Array();
	private $xpath = Array();
	private $nsToTable = Array();
	private $tableToNS = Array();

	private $locks = Array();

	public function __destruct()
	{
		# not needed but looks clean ;)
		foreach ( $this->locks as $table => $l )
		{
			$this->lock( $table, LOCK_UN );
			fclose( $l );
		}
	}

	private function lock( $table, $mode = LOCK_SH )
	{
		if ( !array_key_exists( $table, $this->locks ) )
			$this->locks[ $table ] = fopen( $this->base . "/.$table.lock", 'c' );

		if ( !flock( $this->locks[ $table ], $mode ) )
		{
			if ( $mode = LOCK_EX )
				die( "Cannot acquire database lock for table $table" );

			return false;
		}
		return true;
	}


	public function begin( $table )
	{
		$this->lock( $table, LOCK_EX );
	}

	public function rollback( $table )
	{
		// TODO: clear work buffer
		$this->lock( $table, LOCK_SH );
	}

	public function commit( $table )
	{
		// TODO: update table with work buffer
		$this->store( $table );
		$this->lock( $table, LOCK_SH );
	}






	/**
	 * The table will be cached, and created (but not stored) when
	 * it doesn't exist and $tableNS is specified.
	 */
	public function table( $table, $tableNS = null )
	{
		global $dbNS;

		if ( ! isset( $this->tables[ $table ] ) )
		{
			$this->lock( $table, LOCK_SH );

			$doc = new DOMDocument();
			$doc->preserveWhitespace=true;
			$this->tables[ $table ] = $doc;

			$tableFile = $this->base . "/$table.xml";
			if ( file_exists( $tableFile ) )
			{
				$doc->load( $tableFile );
			}
			else
			{
				if ( isset( $tableNS ) )
				{
					$doc->appendChild( $doc->createElementNS( $tableNS, $table ) );
					# so that the ns is in the root node, so that other
					# attribute ns functions on lower nodes actually work!
#					$doc->documentElement->setAttributeNS( $dbNS, "db:db" );
				}
				else
					die ("No such database table '$table'" );
			}

			$this->tableToNS[ $table ] = $doc->documentElement->namespaceURI;
			$this->nsToTable[ $this->tableToNS[ $table ] ] = $table;

			$xpath = new DOMXPath( $doc );
			$xpath->registerNamespace( "db", $dbNS );
			$xpath->registerNamespace( $table, $this->tableToNS[ $table ] );	
			$this->xpath[ $table ] = $xpath;

			#echo "<pre>".str_replace("<", "&lt;", $doc->saveXML() )."</pre>";
		}

		return $this->tables[ $table ];
	}

	public function listTable( $table )
	{
		if ( !isset( $table ) )
		{
			$doc = new DOMDocument();
			$doc->appendChild( $doc->createElementNS( $dbNS, 'databases' ) );

			foreach ( scandir( $self->base ) as $f )
			{
				if ( endsWith( '.xml', $f ) )
				{
					$t = $doc->appendChild( $doc->createElementNS( $dbNS, 'table' ) );
					$t->setAttribute( 'name', substr( $f, 0, strlen( $f ) - 4 ) );
				}
			}
		}
		else
		{
			return table( $table );
		}
	}

	public function store( $table )
	{
		debug( 'db',  "Storing table $table" );

#no worky		$this->tables[ $table]->formatOutput=true;

		$this->lock( $table, LOCK_EX );

		file_put_contents( $this->base . "/$table.xml",
			$this->tables[ $table ]->saveXML() );

		# i did all this in perl before....
		$this->lock( $table, LOCK_SH );

		#$this->tables[ $table ]->save( $this->base . "/$table.xml" );
	}

	public function get( $table, $id )
	{
		if ( isset( $id ) && !empty( $id ) )
		{
			$pos = $this->xpath[ $table ]->query( "//*[@db:id=$id]" );
			return $pos->item( 0 );
		}
		return null;
	}

	public function put( $table, $row )
	{
		global $dbNS;

		$t = $this->tables[ $table ];
		$row = $t->importNode( $row, true );
		debug( 'db',  str_replace( "<", "&lt;", $t->saveXML( $row ) ) );

		$id = $row->getAttributeNS( $dbNS, "id" );
		debug( 'db',  "ID: $id<br>" );

		if ( isset( $id ) && ! empty( $id ) )
		{
			$t->documentElement->replaceChild( $row, $this->get( $table, $id ) );
		}
		else
		{
			$row->setAttributeNS( $dbNS, "db:id", $this->newId( $table ) );

			$t->documentElement->appendChild( $t->createTextNode( "\n  " ) );
			$t->documentElement->appendChild( $row );
			$t->documentElement->appendChild( $t->createTextNode( "\n" ) );
				
		}
	}

	private function newId( $table )
	{
		global $dbNS;

		$tableNS = $this->tableToNS[ $table ];
		debug( 'db',  "TABLENS: $tableNS" );
		$template = <<<EOF
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:table="$tableNS"
	xmlns:db="$dbNS"
>
	<xsl:template match="/*">
		<xsl:for-each select="table:*/@db:id">
			<xsl:sort data-type="number" order="descending"/>
			<xsl:if test="position()=1">
			<xsl:value-of select="."/>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
EOF;

		$xslt = new XSLTProcessor();
		$xslt->importStylesheet( DOMDocument::loadXML( $template ) );
		$id = $xslt->transformToXML( $this->tables[ $table ] );
		$id = empty( $id ) ? 1 : $id+1;

		#$id = $this->xpath[ $table ]->evaluate( "count( /*/$table:* )" ) + 1;
		debug( 'db',  "DB: GENERATE-ID: $id" );
		return $id;
	}

	public function set( $row, $expr, $value )
	{
		$table = $this->nsToTable[ $row->namespaceURI ];#lookupNamespaceURI( null ) ];
		if ( !isset( $table ) )
			die( "Unknown NS: ".$row->namespaceURI." for ".$row->nodeName );

		if ( strstr( "./@", substr( $expr, 0, 1 ) ) === false )
			$expr = "./$expr";
		$expr = str_replace( "/", "/$table:", $expr );
		


		$nodelist = $this->xpath[$table]->query( $expr, $row );

		debug( 'db',  "SET(".$row->nodeName.", $expr, $value) nodes=".$nodelist->length );

		if ( $nodelist->length == 1 )
			$nodelist->item(0)->nodeValue = $value;
		else if ( $nodelist->length > 1 )
			die( "DB: not setting ".$nodelist->length." nodes; expression: $expr" );
		else
			die( "No XPATH results for $expr on ".$row->nodeName );
	}

	/**
	 * returns a new DOMDocument due to scope-destruction of nodes.
	 * https://bugs.php.net/bug.php?id=39593
	 */
	public function xpath( $table, $expr )
	{
		$nodelist = $this->xpath[ $table ]->query( $expr );

		// return DOMElement with children from $nodelist

		$doc = new DOMDocument();

		$t = $this->tables[ $table ];
		$ret = $t->createElementNS( $this->tableToNS[ $table ], $table );

		$doc->appendChild( $ret = $doc->importNode( $ret, true ) );

		foreach ( $nodelist as $node )
		{
			$ret->appendChild( $doc->importNode( $node, true ) );
		}

		return $doc;
	}

	public function query( $table, $expr )
	{
		$ret = $this->xpath[ $table ]->evaluate( $expr );

		return $ret;
	}
}

$db;

function db_init()
{
	global $db, $dbNS;

	$dbDir = DirectoryResource::findFile( "db" );
	debug( 'db',  "Initializing DB $dbDir" ); #fancy: ../localname()
	$db = new XMLDB( $dbDir, $dbNS );
}

?>
