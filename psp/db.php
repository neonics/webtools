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
				$doc->xinclude();

				debug('db', "DUMP $table\n".$doc->saveXML());
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
			$xpath->registerNamespace( "xml", "http://www.w3.org/XML/1998/namespace" );
			$xpath->registerNamespace( "", "http://www.w3.org/1999/xhtml" );
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

		debug( 'db', "table DUMP: " . $this->tables[$table]->saveXML());

#no worky		$this->tables[ $table]->formatOutput=true;

		$this->lock( $table, LOCK_EX );


		# loop and make sure every row has an ID
		$rowname = $this->xpath[ $table ]->query( "//db:meta/@row" )->item(0)->nodeValue;
		foreach ( $this->xpath[ $table ]->query("//$table:$rowname") as $c )
		{
			if ( ! $c->hasAttributeNS( $this->ns, 'id' ) )
				$c->setAttributeNS( $this->ns, 'db:id', $this->newId( $table ) );
		}

		$this->store_blobs( $table );
		#debug( 'db', "table DUMP after store_blobs: " . $this->tables[$table]->saveXML());

		file_put_contents( $this->base . "/$table.xml",
			$this->tables[ $table ]->saveXML() );

		# i did all this in perl before....
		$this->lock( $table, LOCK_SH );

		#$this->tables[ $table ]->save( $this->base . "/$table.xml" );
	}

	private function store_blobs( $table )
	{
		# check for blob fields.
		$blobcols = $this->xpath[ $table ]->query( "//db:meta/db:column[@type='blob']" );
		foreach ( $blobcols as $c )
		{
			debug('db', "BLOB column: ". $c->nodeName.'; '.$c->getAttribute('name') );

			foreach ( $this->xpath[ $table ]->query("//$table:".$c->getAttribute('name'))
				as $c )
			{
				if ( $c->hasChildNodes() )
				{
					$blobref = trim( $c->getAttributeNS($this->ns,'blob') );
					$rowid = $c->parentNode->getAttributeNS( $this->ns, 'id' );

					if ( $blobref == null || strlen( $blobref ) == 0 )
					{
						$blobref = $table.'/'.$rowid.'_'.preg_replace('/^.*?:/','',$c->nodeName).".xml";
						$c->setAttributeNS( $this->ns, 'blob', $blobref );
					}

					debug('db', "column: " . $c->nodeName ." blobref=$blobref");
					debug('db', "blob file: ". $this->base . "/".$blobref );

					$tmp = new DOMDocument();
					$tmp->preserveWhitespace=true;
					$tmp->appendChild( $tmp->importNode( $c, true ) )->setAttributeNS(
						$this->ns, "db:id", $rowid );

					removeChildren( $c );

					file_put_contents( $this->base . '/' . $blobref, $tmp->saveXML() );
				}
			}
		}
	}

	public function get( $table, $id )
	{
		if ( isset( $id ) && !empty( $id ) )
		{
			$row = $this->xpath[ $table ]->query( "//*[@db:id=$id]" )->item(0);
			$this->load_blobs( $table, $row );
			return $row;
		}
		return null;
	}

	private function load_blobs( $table, $row )
	{
		global $debug;

		# should happen at most once
		$debug and
		debug('db', "load blobs for $table row ".$row->getAttributeNS( $this->ns, "id" ) );

		$blobcols = $this->xpath[ $table ]->query( "//db:meta/db:column[@type='blob']" );

		foreach ( $blobcols as $c )
		{
			foreach ( $this->xpath[ $table ]->query(
					"//*[@db:id=" . $row->getAttributeNS( $this->ns, 'id' ) . "]/$table:"
					.$c->getAttribute('name')
				)
				as $c
			)
			{
					$blobref = $c->getAttributeNS($this->ns,'blob');
					$rowid = $c->parentNode->getAttributeNS( $this->ns, 'id' );

					if ( $blobref == null || trim(strlen($blobref))==0)
						$blobref = $table.'/'.$rowid.'_'.preg_replace('/^.*?:/','',$c->nodeName).".xml";

					debug('db', "loading blob ".$this->base . '/' . $blobref );

					$blobfile = $this->base . '/' . $blobref;
					if ( file_exists( $blobfile ) )
					{
						$blob = loadXML( $blobfile );
						# TODO: sanity check @db:id match
						$blob = $c->ownerDocument->importNode( $blob->documentElement, true );
						# NOTE! $blob is now DOMElement, no longer DOMDocument.

						if ( $debug > 1 )
						{
							foreach ($c->attributes as $a)
								debug('db',"OLD ".$a->nodeName."=".$a->nodeValue);

							foreach ($blob->attributes as $a)
								debug('db',"NEW ".$a->nodeName."=".$a->nodeValue);
						}

						foreach ($c->attributes as $a)
						{
							debug('db', "  check $a->namespaceURI:$a->nodeName=$a->nodeValue");
							if ( ! $blob->hasAttributeNS( $a->namespaceURI, $a->nodeName ) )
							{
								debug('db', "  set $a->nodeName on blob");
								# appendchild ends the loop without error...
								$blob->setAttributeNS( $a->namespaceURI, $a->nodeName, $a->nodeValue);
							}
						}

						$c->parentNode->replaceChild( $blob, $c );
						#$blob->setAttributeNS( $this->ns, "blob", $blobref );
					}

			}
		}
	}

	public function newrow( $table )
	{
		global $dbNS;
		$t = $this->tables[ $table ];
		$tns = $this->tableToNS[ $table ];

		debug('db', "newrow(): $table xmlns=$tns");

		$meta = $this->xpath[ $table ]->query( "//db:meta" );
		if ( $meta->length != 1 )
			die ("Missing metadata for table $table");
		$meta = $meta->item(0);

		$row = $t->createElementNS( $tns, $meta->getAttribute( 'row' ) );
		$row->appendChild( $t->createTextNode("\n    ") );

		foreach ( $meta->getElementsByTagNameNS( $dbNS, 'column' ) as $c )
		{
			$vt = $c->getAttribute( 'xml' );

			switch ( $vt )
			{
				case 'attribute':
					$row->setAttribute( $c->getAttribute( 'name' ), null );
					break;

				case 'element':
					debug('db',"newrow: create element ".$c->getAttribute('name')." ns=$tns");
					$row->appendChild(
						$e = $t->createElementNS( $tns, $c->getAttribute( 'name' ) )
					);
					$row->appendChild( $t->createTextNode("\n    ") );

					foreach ( $c->getElementsByTagNameNS( $this->ns, 'attribute' ) as $a )
						$e->setAttribute( $a->getAttribute( 'name' ), null );
					break;

				default:
					die ("Metadata corruption: invalid value for $table/db:meta/db:column/@xml: $vt");
			}
		}

		$dd = new DOMDocument();
		$dd->appendChild( $dd->importNode( $row->cloneNode(true) ) );
		debug('db', "newrow: " . $dd->saveXML() );

		$t->documentElement->appendChild( $row );
		$t->documentElement->appendChild( $t->createTextNode("\n\n  ") );

		return $row;
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
		global $dbNS, $debug;

		$tableNS = $this->tableToNS[ $table ];
		debug( 'db',  "TABLENS: $tableNS" );
		$template = <<<EOF
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:table="$tableNS"
	xmlns:db="$dbNS"
>
	<xsl:output method="text"/>
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

		#debug('db', "newID template:\n".$template);
		$xslt = new XSLTProcessor();
		$dd = new DOMDocument();
		$dd->loadXML( $template );
		$xslt->importStylesheet( $dd );
		$id = $xslt->transformToXML( $this->tables[ $table ] );
		$id = empty( $id ) ? 1 : $id+1;

		#$id = $this->xpath[ $table ]->evaluate( "count( /*/$table:* )" ) + 1;
		#$debug and
		debug( 'db',  "DB: GENERATE-ID: $id" );
		return $id;
	}

	public function set( $row, $expr, $value )
	{
		global $debug;

		$table = $this->nsToTable[ $row->namespaceURI ];#lookupNamespaceURI( null ) ];
		if ( !isset( $table ) )
			die( "Unknown NS: ".$row->namespaceURI." for ".$row->nodeName );

		if ( strstr( "./@", substr( $expr, 0, 1 ) ) === false )
			$expr = "./$expr";
		$expr = preg_replace( ":/(?!@):", "/$table:", $expr );

		$nodelist = $this->xpath[$table]->query( $expr, $row );

		$debug and
		debug( 'db',  "SET(".$row->nodeName.", $expr, (".gettype($value).") ".(is_string($value)?$value:"")." nodes=".$nodelist->length );

		if ( $nodelist->length == 1 )
		{
			$col = $nodelist->item(0);

			if ( is_string( $value ) )
				$col->nodeValue = $value;
			else if ( is_object( $value ) )
			{
				removeChildren( $col );

				if ( $value instanceof DOMElement )
				{
					$col->appendChild( $row->ownerDocument->importNode($value, true) );
				}
				elseif ( $value instanceof DOMNodeList )
				{
					foreach ( $value as $cn )
					{
						$col->appendChild( $row->ownerDocument->importNode($cn, true) );
					}
				}
			}
			else die("DB: invalid value type: ".gettype($value));

			# filter the doc
			{
				debug('db', "DUMP table:\n".$this->tables[$table]->saveXML());

				$xsl = new DOMDocument();
				$xsl->loadXML( <<<FOO
<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="@*|node()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()" />
		</xsl:copy>
	</xsl:template>
</xsl:stylesheet>
FOO
				);
				$xslt = new XSLTProcessor();
				$xslt->importStylesheet( $xsl );
				$dd = $xslt->transformToDoc( $this->tables[$table] );

				debug('db', "DUMP table after transform:\n".$dd->saveXML());
			}

		}
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
	global $db, $dbNS, $pspBaseDir;

	$dbDir = DirectoryResource::findFile( "db" );

	if ( !isset( $dbDir) || empty( $dbDir ) )
	{
		die("XML Database not found.");
	}
	debug( 'db',  "Initializing DB $dbDir" ); #fancy: ../localname()
	$db = new XMLDB( $dbDir, $dbNS );
}

?>
