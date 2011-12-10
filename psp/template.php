<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

class TemplateModule extends AbstractModule
{
	private $templateId;

	public function __construct()
	{
		parent::__construct( 'template', "http://neonics.com/2011/psp/template" );
	}

	public function setParameters( $xslt )
	{
		# NOTE: this MUST be done as the XSL requires the variable
		# as it uses it in <xsl:param name="a:template" select="$template"/>
		# as that seems the only thing that works with namespaced vars..
	#	$xslt->setParameter( $this->ns, "template", $this->templateId );
	}


	/***** Public Interface *****/
	

	/**
	 *
	 */
	function init()
	{
		global $db, $request; // XXX ref

		if ( isset( $_REQUEST["template:id"] ) && $_REQUEST["template:id"] != "" )
			$this->templateId = $_REQUEST["template:id"];

		$cmd;

		if ( isset( $_REQUEST["action:template:post"] ) )
		{
			$cmd = "publish";
		}
		else if ( isset( $_REQUEST["action:template:save-draft"] ) )
			$cmd = "save-draft";

		if ( isset( $cmd ) )
		{
			$content = psp_arg( "template:content" );
			$file = psp_arg( "template:file" );
			$aid = psp_arg( "template:id" );

			$status = $cmd == "publish" ? "published" : 
				( $cmd=="save-draft"?"draft":"unknown" );

			debug( "Storing template, command=$cmd" );
			debug( "Id: $aid long ? ".(is_long($aid)?"Y":"N")."" );
			debug( "file: $file" );
			debug( "Status: $status" );
			//debug( "Content: $content" );

			// XXX TODO security checks - relative paths, file overwrite etc..
			// TODO: versioning [draft, commit/publish]
			// TODO: separate to specific content module - provides DBResource

			psp_module( "db" );
			$db->table( "templates", $this->ns );

			$templateDir = "$db->base/content";
			if ( ! is_dir( $templateDir ) )
				mkdir( $templateDir ) or die("Cannot create template dir $templateDir");

			file_put_contents( "$templateDir/".safeFile( $file ), $content );
/*
			$old = $db->get( "templates", $aid );
			if ( isset( $old ) )
			{
				debug( "Replacing" );

				$db->set( $old, "@file", $file );
				$db->set( $old, "@status", $status );
				$db->set( $old, "content", $content );
			}
			else
			{
				debug( "Appending" );
				$db->put( "templates", $this->newTemplate( $file, $content ) );
			}

			debug( "STORING" );
			$db->store( "templates" );
*/
		}
	}

	public function content()
	{
		global $request; // XXX ref

		if ( !auth_permission( 'editor' ) )
		{
			debug("No editor permission");
			return $this->message( 'No edit permissions', 'error' );
		}

		$this->message('got edit perms', 'debug');
		
		#if ( $this->isAction( "edit" ) )
		{
			return loadXML( $request->in )->documentElement;//->documentElement;
		}
	}


	/******** Internal Utility **********/

	private function newTemplate( $file = "", $content = "" )
	{
		global $db;

		$title = htmlspecialchars( $file );

		$template = <<<EOF
  <template status="draft" title="$title">$content</template>
	</template>

EOF;
		return DOMDocument::loadXML( $template )->documentElement;

/*
		$a = $templates->createElementNS( $ns, "template" );
		$a->setAttribute( "title", $title );
		$a->setAttribute( "status", "draft" );
		$a->appendChild( $templates->createTextNode( "\n  " ) );
		$a->appendChild( $templates->createElementNS( $ns, "content", $content ) );
		$a->appendChild( $templates->createTextNode( "\n" ) );

		return $a;
*/
	}


	/******* XSL functions **********/

	/**
	 * <template:templates>
	 *   <template:template/>
	 *   ...
	 * </template:templates>
	 */
	public function index()
	{
		global $db;
		return $db->table( "templates" )->documentElement;
	}

	/**
	 * <template:template/>
	 */
	public function get( $aid, $newIfNotFound = true )
	{
		global $db;

		$ret= $db->get( "templates", $aid );

		return isset( $ret ) ? $ret : ( $newIfNotFound ? $this->newTemplate() : null );
	}

}

$template_class = "TemplateModule";
?>
