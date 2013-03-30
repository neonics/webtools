<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

# 'EXAMPLE' MUST be the same as the filename (sans .php)
$admin_class = "AdminModule";

class AdminModule extends AbstractModule
{
	public function __construct()
	{
		parent::__construct( 'admin', "http://neonics.com/2013/psp/admin" );
	}

	public function setParameters( $xslt )
	{
	//	if ( isset ( $_REQUEST["ARGUMENT"] ) )
	//		$xslt->setParameter( null, "EXAMPLE_SOMETHING", $_REQUEST["ARGUMENT"] );
	}

	private $templateId;

	/**
	 * called automatically
	 */
	function init()
	{
		global $db, $request, $_REQUEST; // XXX ref

		debug('admin', "slashpath: $request->slashpath");
		foreach ( $_REQUEST as $k => $v )
			debug('admin', "REQUEST: $k=$v");

		$pspm = ModuleManager::$modules['psp']['instance'];

		if ( $pspm->slashpath( 'site/page' ) )
		{
			$path = $pspm->slasharg( 'site/page', 0 );

			debug('admin', "slasharg: $path");

			if ( !auth_permission( 'editor' ) )
			{
				debug('admin', "No editor permission");
				return $this->message( 'No edit permissions', 'error' );
			}

			if ( isset( $_REQUEST['action:admin:save'] ) )
			{
				psp_module('db');
				$dbcontentdir = "$db->base/content";
				debug('admin', "saving page $path to "
					. "dbcontentdir=$dbcontentdir, file=".safeFile( "$path.xml" )
					. " complete path: "
					. "$dbcontentdir/".safeFile( "$path.xml" )
				);

				file_put_contents( "$dbcontentdir/".safeFile( "$path.xml" ),
					$_REQUEST['admin:content'] );

			}
		}


/*
		if ( isset( $_REQUEST["template:id"] ) && $_REQUEST["template:id"] != "" )
			$this->templateId = $_REQUEST["template:id"];

		$cmd;
*/
/*
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
*/
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
//		}
	}


	// public functions are exported to XSLT, and are expected to return
	// a DOM Node Set.
	public function site_overview()
	{
		$f = DirectoryResource::findFile( "menu.xml", "content" );
		$d = loadXML( $f );
		#$d->appendChild( $d->createElementNS("", 'div',"FOO!" ) );
		return $d->documentElement;
	}

	public function site_pages()
	{
		#$layoutNS = "http://www.neonics.com/xslt/layout/1.0";
		$d = new DOMDocument();
		$l = $d->createElement( "list" );

		$this->_dirtree( $l, "content" );

		$d->appendChild( $l );

		debug('admin', "Dirtree: " . $d->saveXML() );
		return $l;
	}

	private function _dirtree( $d, $path )
	{
		$list = scandir( $path );

		foreach ( $list as $f )
		{
			if ( $f == '.' || $f == '..' )
				continue;

			if ( is_dir( $path.'/'.$f ) )
			{
				$i = $d->ownerDocument->createElement( 'dir' );
				$i->setAttribute( "name", $f );

				$this->_dirtree( $i, $path . '/' . $f );
			}
			elseif ( is_file( $path.'/'.$f ) )
			{
				$i = $d->ownerDocument->createElement( 'file' );
				$i->setAttribute( "name", preg_replace( "@\.xml$@", "", $f ) );
			}
			else
			{
				$i = $d->ownerDocument->createElement( 'UNKNOWN', $f );
			}

			$d->appendChild( $i );
		}
	}


}


/*

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
*/


	/******** Internal Utility **********/
/*
	private function newTemplate( $file = "", $content = "" )
	{
		global $db;

		$title = htmlspecialchars( $file );

		$template = <<<EOF
  <template status="draft" title="$title">$content</template>
	</template>

EOF;
		return DOMDocument::loadXML( $template )->documentElement;
	}
*/

	/******* XSL functions **********/

	/**
	 * <template:templates>
	 *   <template:template/>
	 *   ...
	 * </template:templates>
	 */
/*
	public function index()
	{
		global $db;
		return $db->table( "templates" )->documentElement;
	}
*/

	/**
	 * <template:template/>
	 */
/*
	public function get( $aid, $newIfNotFound = true )
	{
		global $db;

		$ret= $db->get( "templates", $aid );

		return isset( $ret ) ? $ret : ( $newIfNotFound ? $this->newTemplate() : null );
	}
*/

?>
