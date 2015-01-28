<?php
/**
 * Super-simple legacy templating system.
 *
 * To enable legacy support for PHP files (executing them as-is),
 * see .htaccess to let 'handle.php' handle them,
 * and add a file 'template.php' defining template_do() - see below.
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
if ( ! function_exists( 'template_do' ) ) {
	function template_do( $request, $legacyContent = null, $template = null )
	{
		if ( $template === null )
			$template = new Template();

		$template->main( $request, $legacyContent );
	}
}

class Template
{
	function main( $request, $legacyContent = null ) {
		echo <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{$request->requestBaseURI}css/style.css" rel="stylesheet">
  </head>
  <body>
		<header id="top" class="navbar navbar-static-top bs-docs-nav" role="banner">
			{$this->menu($request)}
		</header>
		<script src="{$request->requestBaseURI}js/menu.js"></script>

		<div class='alert alert-info dismissable'>
			note: define template.php in the PHP include path to override
		</div>

		<div class="container-fluid" id='content'>
			{$this->content($request)}
		</div>
  </body>
</html>
HTML;
	}


	function menu( $request )
	{
		ob_start();
		// doing it this way will execute any <?php code :
		include_once( DirectoryResource::findFile( 'menu.xml', 'content' ) );
		$d = ob_get_contents();
		ob_end_clean();
		// finally evaluate any {$foo} expressions:
		extract( (array) $request );
		return eval("return <<<HTML\n$d\nHTML;\n");
	}

	function content( $request )
	{
		if ( ! function_exists( 'template_content' ) )
		{
			debug( $this, "legacy mode - not a template" );
			return
			"<div class='alert alert-danger'>legacy mode - not a template</div>"
			. $this->legacyContent;
		}
		else
		{
			ob_start();
			$c = null;

			try
			{
				template_content( $request );
			}
			catch (Exception $e )
			{
				$c = "<pre class='alert alert-danger'>$e</pre>";
			}

			return ob_get_clean() . $c;
		}
	}
}

/**
 * The no-template. Use this for plain old php files that produce content that
 * should not be templated:
 *
 *  <?php
 *  echo "custom content";
 *  return array( 'template' => new NullTemplate() );
 *
 */
class NullTemplate{
	public function main($request, $content) {
		echo $content;
	}
}
