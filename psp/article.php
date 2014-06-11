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
		global $debug, $request;
		global $xmldb; // XXX ref

		psp_module( "db" );
		$xmldb->table( "articles", $this->ns );

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
			$content_fmt = psp_arg( "article:content:format", "text" );
			$aid = psp_arg( "article:id" );
			$status = $cmd == "publish" ? "published" :
				( $cmd=="save-draft"?"draft":"unknown" );

			if ( $debug > 1 )
			{
				debug('article', "Storing article, command=$cmd" );
				debug('article', "Id: $aid long ? ".(is_long($aid)?"Y":"N") );
				debug('article', "Title: $title" );
				debug('article', "Status: $status" );
				debug('article', "Content: $content" );
			}

			$old = $xmldb->get( "articles", $aid );

			if ( !isset( $old ) )
			{
				$old = $xmldb->newrow( 'articles' );
			}


			{
				$xmldb->set( $old, "@title", $title );
				$xmldb->set( $old, "@status", $status );
				$xmldb->set( $old, "content/@xml:lang", $request->requestLang );

				$str = str_replace( "\r","", trim( $content ) );

				switch ( $content_fmt )
				{
					case 'text':
						$str = "\n<p>".implode( "</p>\n<p>", explode( "\n\n", $str ) )."</p>\n";
						break;
					case 'xml':
						$str = "<DUMMY>".$str."</DUMMY>";
					case 'xml-with-wrapper':
						break;
				}

				debug('article', "DUMP Content:\n$str");

				$dd = new DOMDocument();
				$dd->loadXML( $str );

				$xmldb->set( $old, "content", $dd->documentElement->childNodes );
			}
			#else
			#{
		#		$xmldb->put( "articles", $this->newArticle( $title, $content ) );
	#		}

			$xmldb->store( "articles" );
		}
	}

	/******** Internal Utility **********/

	private function newArticle( $title = "", $content = "" )
	{
		/*
		global $xmldb, $request;

		$title = htmlspecialchars( $title );
		$ns = $this->ns;

		$template = <<<EOF
  <a:article xmlns:a="$ns" xmlns="http://www.w3.org/1999/xhtml" status="draft" title="$title">
		<a:content xml:lang='$request->requestLang'>$content</a:content>
	</a:article>

EOF;
		$dd = new DOMDocument();
		$dd->loadXML( $template );
		return $dd->documentElement;
		*/

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
		global $xmldb;
		return $xmldb->table( "articles" )->documentElement;
	}

	/**
	 * <article:article/>
	 */
	public function get( $aid = null, $newIfNotFound = true )
	{
		global $xmldb;

		$ret= $xmldb->get( "articles", gd( $aid, $this->articleId ) );

		return isset( $ret ) ? $ret : ( $newIfNotFound
			? $xmldb->table( 'articles' )->createElementNS( $this->ns, 'article' )
			: null );
	}

	public function getcontent( $val )
	{
		return $val;
	}

}

$article_class = "ArticleModule";
?>
