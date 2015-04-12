<?php
/**
 * @author Kenney Westerhof <kenney@neonics.com>
 */

namespace template;

class Menu
{
	private $doc;

	public function __construct( $request ) {

		// doing it this way will execute any <?php code :
		ob_start();
		include_once( \DirectoryResource::findFile( 'menu.xml', 'content' ) );
		$d = ob_get_clean();

		// finally evaluate any {$foo} expressions:
		extract( (array) $request );
		$d = eval("return <<<HTML\n$d\nHTML;\n");

		$this->content = \template\AuthFilter::filter( $d );
	}

	public function content()
	{
		return $this->content;
	}
}

