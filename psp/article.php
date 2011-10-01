<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

class ArticleModule extends AbstractModule
{
	private $articleId;

	public function __construct()
	{
		parent::__construct( 'article', "http://neonics.com/2011/article" );
	}

	public function setParameters( $xslt )
	{
		# NOTE: this MUST be done as the XSL requires the variable
		# as it uses it in <xsl:param name="a:article" select="$article"/>
		# as that seems the only thing that works with namespaced vars..
		$xslt->setParameter( $this->ns, "article", $this->articleId );
	}


	/***** Public Interface *****/
	

	/**
	 *
	 */
	function init()
	{
		global $db; // XXX ref

		psp_module( "db" );
		$db->table( "articles", $this->ns );

#		foreach ($_REQUEST as $k=>$v) { echo "REQUEST $k => $v<br>"; }

		if ( isset( $_REQUEST["article:id"] ) && $_REQUEST["article:id"] != "" )
			$this->articleId = $_REQUEST["article:id"];

		$cmd;

		if ( isset( $_REQUEST["action:article:post"] ) )
		{
			$cmd = "publish";
		}
		else if ( isset( $_REQUEST["action:article:save-draft"] ) )
			$cmd = "save-draft";

		if ( isset( $cmd ) )
		{
			$title = psp_arg( "article:title" );
			$content = psp_arg( "article:content" );
			$aid = psp_arg( "article:id" );
			$status = $cmd == "publish" ? "published" : 
				( $cmd=="save-draft"?"draft":"unknown" );

			echo "Storing article, command=$cmd<br>";
			echo "Id: $aid long ? ".(is_long($aid)?"Y":"N")."<br>";
			echo "Title: $title<br>";
			echo "Status: $status<br>";
			echo "Content: $content<br>";

			$old = $db->get( "articles", $aid );

			if ( isset( $old ) )
			{
				echo "Replacing<br>";

				$db->set( $old, "@title", $title );
				$db->set( $old, "@status", $status );
				$db->set( $old, "content", $content );

				/*
				$new->setAttribute( "status", $status );

				$old->setAttribute( "title", $title );
				$old->getElementsByTagNameNS( $old->namespaceURI, "content" )
					->item(1)->nodeValue = $content;

				$db->put( "articles", $new, $aid );
				*/
			}
			else
			{
				echo "Appending<br>";
				$db->put( "articles", $this->newArticle( $title, $content ) );
			}

			echo "<br>STORING<br>";
			$db->store( "articles" );
		}
	}

	/******** Internal Utility **********/

	private function newArticle( $title = "", $content = "" )
	{
		global $db;

		$title = htmlspecialchars( $title );

		$template = <<<EOF
  <article status="draft" title="$title">
		<content>$content</content>
	</article>

EOF;
		return DOMDocument::loadXML( $template )->documentElement;

/*
		$a = $articles->createElementNS( $ns, "article" );
		$a->setAttribute( "title", $title );
		$a->setAttribute( "status", "draft" );
		$a->appendChild( $articles->createTextNode( "\n  " ) );
		$a->appendChild( $articles->createElementNS( $ns, "content", $content ) );
		$a->appendChild( $articles->createTextNode( "\n" ) );

		return $a;
*/
	}


	/******* XSL functions **********/

	/**
	 * <article:articles>
	 *   <article:article/>
	 *   ...
	 * </article:articles>
	 */
	public function index()
	{
		global $db;
		return $db->table( "articles" )->documentElement;
	}

	/**
	 * <article:article/>
	 */
	public function get( $aid = null, $newIfNotFound = true )
	{
		global $db;

		$ret= $db->get( "articles", gd( $aid, $this->articleId ) );

		return isset( $ret ) ? $ret : ( $newIfNotFound ? $this->newArticle() : null );
	}

}

$article_class = "ArticleModule";
?>
