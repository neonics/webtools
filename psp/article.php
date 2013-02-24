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

				$dd = new DOMDocument();
				$str= "<p><p>".
							str_replace("\n", "</p>\n<p>",
								str_replace("\r","", trim( $content ))
							)."</p></p>";

				echo "<br>CONTENT STRING<br>".str_replace( "<","&lt;",$str);

				$dd->loadXML( $str );

				$db->set( $old, "content", $dd->documentElement );

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
		$ns = $this->ns;

		$template = <<<EOF
  <a:article xmlns:a="$ns" status="draft" title="$title">
		<a:content>$content</a:content>
	</a:article>

EOF;
		$dd = new DOMDocument();
		$dd->loadXML( $template );
		return $dd->documentElement;

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

	public function getcontent( $val )
	{
		return $val;
	}

}

$article_class = "ArticleModule";
?>
