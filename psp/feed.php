<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

$feed_class = "FeedModule";

class FeedModule extends AbstractModule
{

	public function __construct()
	{
debug('feed', "INSTANTIATING FEED!\n\n");
		parent::__construct( 'feed', "http://neonics.com/2013/psp/feed" );
	}

	public function setParameters( $xslt )
	{
	//	if ( isset ( $_REQUEST["project"] ) )
		//	$xslt->setParameter( null, "project", $_REQUEST["project"] );
	}


	public function init()
	{
	/*
		$list = scandir( $this->projectsDir );

		$doc = new DOMDocument();

		$doc->appendChild(
			$doc->createElementNS( $this->ns, "project:projects" )
		);

		foreach ($list as $f)
		{
			if ( strpos( $f, "." ) === FALSE )
			{
				$projFile = "$this->projectsDir/$f/project.xml";

				if ( file_exists( $projFile ) )
				{
					$pdoc = new DOMDocument();
					$pdoc->load( $projFile );
					$pdoc->documentElement->setAttribute( "uri", "projects/$f/index" );

					$doc->documentElement->appendChild(
						$doc->importNode( $pdoc->documentElement, true ) );
				}
			}
		}

		$this->projects = $doc->documentElement;
		*/
	}

	public function fetch( $url )
	{
		global $requestBaseDir, $debug;

		$cacheDir = $requestBaseDir . "/cache/feed";

		is_dir( $cacheDir ) or mkdir( $cacheDir, 0755, true )
		or die( "feed: Failed to create $cacheDir" );

		$cacheFile = $cacheDir . "/" . preg_replace( '@/|:@', '_', $url );

		$d = new DOMDocument();

		if ( file_exists( $cacheFile )
			&& time() - filemtime( $cacheFile ) < 24*3600
			&& !psp_arg( "psp:nocache" )
		)
		{
			if ( $debug ) debug( 'feed', "cache hit for $url" );
			$d->load( $cacheFile );
		}
		else
		{
			$d->load( $url );
			clearstatcache(); // just to be safe
			if ( $debug ) debug( 'feed', "caching $url" );
			file_put_contents( $cacheFile, $d->saveXML() );
		}

		return $d->documentElement;
	}

}
?>
