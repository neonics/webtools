<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

$project_class = "ProjectModule";

class ProjectModule extends AbstractModule
{

private $projectsDir = "content/projects";

private $projects;

	public function __construct()
	{
		global $pspNS;
		parent::__construct( 'project', "http://www.neonics.com/2000/project" );
	}

	public function setParameters( $xslt )
	{
		if ( isset ( $_REQUEST["project"] ) )
			$xslt->setParameter( null, "project", $_REQUEST["project"] );
	}

	public function init()
	{
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
	}

	function index()
	{
		return $this->projects;
	}

}
?>
