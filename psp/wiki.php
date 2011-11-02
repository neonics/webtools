<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */
global $pspBaseDir;
require_once( "$pspBaseDir/lib/SQLDB.php" );

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

	public function index()
	{
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
