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
		global $xmldb, $request, $_REQUEST; // XXX ref
		global $requestBaseDir, $contentDir, $pspContentDir;

		debug('admin', "slashpath: $request->slashpath");
		foreach ( $_REQUEST as $k => $v )
			debug('admin', "REQUEST: $k=$v");

		$pspm = ModuleManager::$modules['psp']['instance'];

		$path = null;

		if ( $pspm->slashpath( 'site/page' ) )
			$path = $pspm->slasharg( 'site/page', 0 );
		elseif ( $pspm->slashpath( 'site/menu' ) )
			$path = 'menu';

		debug('admin', "slasharg: $path");

		if ( $path )
		{
			$path = safePath( $path );
			if ( ( $idx = strrpos( $path, '/' ) ) !== false )
			{
				$file = safeFile( substr( $path, $idx + 1 ) );
				$path = substr( $path, 0, $idx + 1 );
			}
			else
			{
				$file = $path;
				$path = "";
			}

			debug('admin', "path[ $path ] file[ $file ]");


			if ( !auth_permission( 'editor' ) )
			{
				debug('admin', "No editor permission");
				return $this->message( 'No edit permissions', 'error' );
			}

			if ( isset( $_REQUEST['action:admin:save'] ) )
			{
				psp_module('db');
				$dbcontentdir = "$xmldb->base/content";
				debug('admin', "saving page '$path' / '$file' to "
					. "dbcontentdir=$dbcontentdir, file=$file.xml"
					. " complete path: "
					. "$dbcontentdir/$path$file.xml"
				);

				if ( !is_dir( $dbcontentdir.'/'.$path ) )
				{
					$p = $requestBaseDir . '/'.gd( $contentDir, $pspContentDir ).'/'.$path;
					debug ('admin', "check path: ". $p );

					// check if the path exists in the normal content dir
					if ( is_dir( $p ) )
					{
						debug('admin', "notice: creating directory: $dbcontentdir/$path" );
						mkdir( $dbcontentdir.'/'.$path, 0755, true );
					}
					else
					{
						debug('admin', "denied attempt to create db path: $dbcontentdir/$path");
						return $this->message('Cannot create non-original db path: '.$path);

					}
				}

				file_put_contents( $dbcontentdir.'/'.$path.$file.'.xml',
					$_REQUEST['admin:content'] );

			}
		}
		else
		{
			if ( isset( $_REQUEST['action:admin:delete'] ) )
			{
				if ( !auth_permission( 'editor' ) )
				{
					debug('admin', "No editor permission");
					return $this->message( 'No edit permissions', 'error' );
				}

				if ( isset( $_REQUEST['admin:dbfile'] ) )
				{
					$dbf = $_REQUEST['admin:dbfile'];
					$dbf2= "$xmldb->base/".stripDoubleSlash(preg_replace("@\.\.@","",$dbf));
					if ( file_exists ( $dbf2 ) )
					{
						if ( file_exists( $dbf2.".deleted" ) )
							unlink ($dbf2.".deleted" );
						rename( $dbf2, $dbf2.".deleted" );
					}
					else
					{
						debug('admin', "admin:delete: file not found: " . $dbf2 . " (arg: $dbf)");
						return $this->message( 'File not found', 'error' );
					}
				}
				else
				{
					debug('admin', "admin:delete: no valid argument");
					return $this->message( 'Invalid argument', 'error' );
				}
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
			$xmldb->table( "templates", $this->ns );

			$templateDir = "$xmldb->base/content";
			if ( ! is_dir( $templateDir ) )
				mkdir( $templateDir ) or die("Cannot create template dir $templateDir");

			file_put_contents( "$templateDir/".safeFile( $file ), $content );
*/
/*
			$old = $xmldb->get( "templates", $aid );
			if ( isset( $old ) )
			{
				debug( "Replacing" );

				$xmldb->set( $old, "@file", $file );
				$xmldb->set( $old, "@status", $status );
				$xmldb->set( $old, "content", $content );
			}
			else
			{
				debug( "Appending" );
				$xmldb->put( "templates", $this->newTemplate( $file, $content ) );
			}

			debug( "STORING" );
			$xmldb->store( "templates" );
*/
//		}
	}


	// public functions are exported to XSLT, and are expected to return
	// a DOM Node Set.
	public function site_menu()
	{
		$f = DirectoryResource::findFile( "menu.xml", "content" );
		$d = loadXML( $f );
		#$d->appendChild( $d->createElementNS("", 'div',"FOO!" ) );
		return $d->documentElement;
	}

	public function site_pages( $bd = null )
	{
		#$layoutNS = "http://www.neonics.com/xslt/layout/1.0";
		$d = new DOMDocument();
		$l = $d->createElement( "list" );

		if ( $bd != null )
		{debug('psp', "site_pages got arg: [$bd]");
			$bd = stripDoubleSlash( "/". preg_replace( "@\.\.@", "", $bd ) );
			}
		else
			$bd = "";

			debug('psp', "dirring tree: content/[$bd]");

		$this->_dirtree( $l, "content".$bd );

		$d->appendChild( $l );

		debug('admin', "Dirtree: " . $d->saveXML() );
		return $l;
	}

	// for now this function assumes that the path root is content
	private function _dirtree( $d, $path )
	{
		global $requestBaseDir, $pspBaseDir;

		$relpath = substr( $path, strlen("content"));
		if ( strpos( $relpath, '/' ) === 0 )
			$relpath = substr( $relpath, 1 );

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
				$i->setAttribute( "path", $relpath.'/'.preg_replace( "@\.xml$@", "", $f ) );
				$i->setAttribute( "name-ext", $f );

				foreach ( DirectoryResource::findFiles( $path . '/' . $f ) as $f )
				{
					$l = $i->appendChild( $d->ownerDocument->createElement( 'alt' ) );
					$l->setAttribute('fullpath', $f );

					// strip. +1 to cut starting /
					$b;
					// sometimes psp is installed under requestbasedir, so check that first.
					if ( strpos( $f, $pspBaseDir ) === 0 )
					{
						$f = substr( $f, strlen( $pspBaseDir ) + 1 );
						$b = 'core';
						$l->setAttribute('baserelpath', $f );
					}
					elseif ( strpos( $f, $requestBaseDir ) === 0 )
					{
						$f = substr( $f, strlen( $requestBaseDir ) + 1 );
						$b = 'request';
						$l->setAttribute('baserelpath', $f );
					}

					else
					{
						debug('psp', "warn: file not in psp or request root: $f");
						$b = 'outside';
					}
					$w = null;
					if ( strpos( $f, "db/" ) === 0 )
					{
						$f = substr( $f, 3 );
						$w = 'db';
					}
					$t = substr( $f, 0, strpos( $f, '/' ) );

					$l->setAttribute( 'name', $f );
					$l->setAttribute( 'base', $b );
					$l->setAttribute( 'type', $t );
					$w != null and
					$l->setAttribute( 'where', $w );

				}
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
		global $xmldb;

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
		global $xmldb;
		return $xmldb->table( "templates" )->documentElement;
	}
*/

	/**
	 * <template:template/>
	 */
/*
	public function get( $aid, $newIfNotFound = true )
	{
		global $xmldb;

		$ret= $xmldb->get( "templates", $aid );

		return isset( $ret ) ? $ret : ( $newIfNotFound ? $this->newTemplate() : null );
	}
*/

?>
