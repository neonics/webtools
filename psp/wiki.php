<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */
global $pspBaseDir;
require_once( "$pspBaseDir/lib/SQLDB.php" );



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
						'sql:pgsql' => 'serial not null primary key',
						'sql:mysql' => 'int not null auto_increment primary key',
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
						'sql:pgsql' => 'timestamptz',
						'sql:mysql' => 'datetime',
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
						'type' => 'xml',
						'xml' => 'element'
					)
				)
			)
		);

	}

	private function search( $titlePart )
	{
		$q = "SELECT title from ".$this->definition["tablename"]
			. " where title like :q" ;

		$sth = $this->dbh->prepare( $q );
		$sth->execute( array( ":q" => str_replace( "*", "%", $titlePart ) ) );

		$index = "";
		foreach ( $sth->fetchAll() as $a )
		{
			$index .= preg_replace( "/^(.*?)\/([^\/]+)$/", "[[\${0}|\${2}]]<br/>", $a[0] );
		}

		return $index;
	}


	/** WikiParser */
	public function getXmlText()
	{
		debug( 'sqldb', "[table] getText" );
		$text = $this->row[ 'text' ];

		$matches; $r = "/\[\[wiki:search\|(.*?)\]\]/";
		if ( preg_match( $r, $text, $matches) )
		{
			$index = $this->search( $matches[1] );
			#$index = "Search Results for: ". $matches[1];
			$text = preg_replace( $r, $index, $text );
		}

		$text = str_replace( "\r", "", $text );

		$text = preg_replace( "/(^|\n)== (.*?) ==\n/", "<h2>\${2}</h2>", $text );
		$text = preg_replace( "/(^|\n)=== (.*?) ===\n/", "<h3>\${2}</h3>", $text );
		$text = preg_replace( "/(^|\n)==== (.*?) ====\n/", "<h4>\${2}</h4>", $text );

		$text = preg_replace( "/&/", "&amp;", $text );

		$text = preg_replace( "/\[\[(.*?)\|(.*?)\]\]/", "<a href='\${1}'>\${2}</a>", $text );
		$text = preg_replace( "/\[\[(.*?)\|(.*?)\]\]/", "<a href='\${1}'>\${2}</a>", $text );
		$text = preg_replace( "/\[\[(.*?)\]\]/", "<a href='\${1}'>\${1}</a>", $text );

		$text = "<p>".preg_replace( "/\n\r?\n\r?/", "</p>\n\n<p>", $text )."</p>";

		return $text;
	}
}




class WikiModule extends AbstractModule
{
	private $db;

	private $dbi = array(
		'dsn'				=> "pgsql:host=localhost;dbname=fractalfountain",
		'user'			=> "fractalfountain",
		'password'	=> "fractalpass",
		'options'		=> array(),	# PDO::ATTR_AUTOCOMMIT => FALSE );
	);

	private $table;

	public function __construct()
	{
		parent::__construct( 'wiki', "http://neonics.com/2011/wiki" );
	}

	public function setParameters( $xslt )
	{
	}

	public function init()
	{
		global $request; // XXX ref
		global $dbi; // XXX handle.php config
		$this->dbi = $dbi;

		if ( ! isset( $dbi ) )
			return;

		$this->db = new SQLDB( $this->dbi );
		#new PDO( $this->dsn, $this->dbuser, $this->dbpass, $this->dboptions );

		$this->table = new WikiArticlesTable(
			$this->db->dbh,
			$this->ns,
			$this->name,
			'article',
			"wiki_articles"
		);

		$this->table->create( dirname( __FILE__ ).'/wiki.sql' );	


		// Action processing

		if ( $this->isAction( "delete" ) )
		{
			$t = psp_arg("wiki:title") ;
			debug( "Deleting title $t" );
			$item = $this->table->get( array( 'title' => $t ) );
			$item->delete();
		}
		if ( $this->isAction( "post" ) )
		{
			$id =  psp_arg( 'wiki:article:id' );
			debug( 'wiki', "POST, id=" . psp_arg('wiki:article:id'));

			$item;
			if ( isset( $id ) && ! empty( $id)  )
			{
				$item = $this->table->get( array( 'id' => $id ) );
			}
			else
			{
				$item = $this->table;
				$item->date = date( "Y-m-d H:i:s O" );
				$item->status = 'draft';
			}

			foreach ( $this->table->definition["columns"] as $cn => $cd )
			{
				$v = psp_arg( "wiki:article:$cn" );
				if ( isset( $v ) )
					$item->$cn = $v;
			}

			$item->store();
		}
	}

	public function initialized()
	{
		debug( 'wiki', 'initialized: ' .( isset( $this->dbi ) == true ));
		return isset( $this->dbi ) == true;
	}

	public function foo( $msg )
	{
		debug( 'wiki', "XSLT Message: $msg");
	}

	public function index()
	{
		if ( ! $this->initialized() ) return null;
		return $this->table->index();
	}

	public function get( $title = null )
	{
		return $this->table->getXML(
			array( 'title' => gd( $title, psp_arg( 'wiki:title' ) ) ) );
	}

	private function hash( $s, $m = 'md5' )
	{
		return "$m:" . ($m != 'plain' ? hash( $m, $s ) : $s );
	}

}

$wiki_class = "WikiModule";


?>
