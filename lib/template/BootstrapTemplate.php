<?php
/**
 * This is the default, base Template, using jQuery and bootstrap-css.
 * It is also a base class allowing for functional modules
 * to define optional module_init(), module_menu() and module_content() functions.
 */
namespace {

	/** Called by TemplateRequestHandler */
	function template_do( $request, $legacyContent = null, $template = null )
	{
		if ( $template === null )
			$request->template =
			$template = new \template\BootstrapTemplate();

		if ( ! is_array( $request->template_data ) )
		{
			error("BUG: request template data is not an array!" );
			fatal( fold_debug( $request ) );
		}

		// if we're here, psp module 'auth' has been loaded already.
		// XXX unless /amazon/!

		// FIXME: module also has _auth checking (on a menu level).

		if ( !empty( $request->template_data['role'] ) )
			if ( ! auth_role( $r = $request->template_data['role'] ) )
			{
				//throw new SecurityException( "You do not have the <b>$r</b> role required to access this page." );

				header( 'HTTP/1.0 401 Permission denied' );
				echo "<html><body data-style='background-color:red;color:white'>\n";
				echo "<h1>Permission Denied</h1>\n";
				echo "<p>You do not have the <b>$r</b> role required to access this page.</p>\n";
				echo "<p><a href='javascript:history.go(-1);'>Go back.</a> or <a href='/index.html'>go home</a></p>";
				echo "</body></html\n";

				return;
			}

		$template->main( $request, $legacyContent );
	}

}

namespace template {

class BootstrapTemplate
{
	protected $themes = ['basic'];
	protected $theme = 'basic';
	protected $anim  = "anim";

	public $noticebar = null;

	public function __construct() {
	}

	protected function theme() {
		if ( isset( $_SESSION['theme'] ) )
			$this->theme = $_SESSION['theme'];
		return $this->theme;
	}

	function manage_theme() {
		if ( isset( $_REQUEST['action:theme']  )
			&& isset( $_REQUEST['theme'] )
			&& in_array( $_REQUEST['theme'], $this->themes )
		)
			$_SESSION['theme'] = $_REQUEST['theme'];
		;

		return array( "
			<form method='post'>
				<label for='theme'>theme:</label>
				<input type='hidden' name='action:theme'>
				<select name='theme' onchange='this.form.submit()'>
		"
		.implode('', array_map( function($v){return "<option".($this->theme()==$v?" selected":"").">$v</option>";}, $this->themes ) )
		."
				</select>
			</form>
		",
		'manage-theme' );
	}


	protected function favicon() {
		global $request;
		return <<<HTML
			<link rel="icon" href="{$request->requestBaseURI}img/favicon.ico" type="image/x-icon"/>
HTML;
	}


	protected function themeCSS() {
		global $request;
    return <<<HTML
			<link href="{$request->requestBaseURI}css/skin-{$this->theme()}.css" rel="stylesheet">
HTML;
	}

	function main( $request, $legacyContent )
	{
		if ( !isset( $this->noticebar ) )
			$this->noticebar = new NoticeBar( $this );

		// allow to modify the header
		if ( function_exists( 'template_init' ) )
			$request->template_init = $this->call( 'template_init', $request );

		$menu = $this->menu( $request ); // modifies HEAD (styles)

		#echo "REQUEST: <pre>".print_r($request,1)."</pre>";
		echo <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
		{$this->favicon()}

    <!-- Bootstrap -->
		<!--
    <link href="{$request->requestBaseURI}css/bootstrap.min.css" rel="stylesheet">
		-->
    <link href="{$request->requestBaseURI}css/3p/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

		<!-- -->
		<!--link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet"-->
		<link href="{$request->requestBaseURI}css/3p/font-awesome.min.css" rel="stylesheet">

		<!-- -->

		<!-- structural requirements -->
    <link href="{$request->requestBaseURI}css/style.css" rel="stylesheet">

		<!-- styling: skin -->
		{$this->themeCSS()}

    <script src="{$request->requestBaseURI}js/3p/jquery.min.js"></script>
    <script src="{$request->requestBaseURI}js/3p/bootstrap.min.js"></script>

		<script src='/js/component.js'></script>
		<script src='/js/noticebar.js'></script>
  </head>
HTML;

	if ( ob_get_level() ) ob_flush();
	flush();

	$noticeBar = $this->noticebar->render();

	echo <<<HTML
  <body class='theme {$this->theme} {$this->anim}'>
		<!-- header has position relative for content flow; nested nav (navbar-default) has position fixed. -->
		<header id="top" class="theme {$this->theme}  navbar navbar-static-top  bs-docs-nav" role="banner">
			<div>
				<nav id="noticebar" class='navbar navbar-noticebar'>
					<div class='container-fluid'>
					{$noticeBar}
					</div>
				</nav>
			{$menu}
			</div>
		</header>
		<script src="{$request->requestBaseURI}js/menu.js"></script>
HTML;
	if ( ob_get_level() ) ob_flush();
	flush();

	echo <<<HTML
		<div class="theme {$this->theme}" id='content'><!-- class container-fluid -->
HTML;

	$this->content($request, $legacyContent);

echo <<<HTML
		</div>

  </body>
</html>
HTML;

		flush();
	}

	function menu( $request )
	{
		return (new \template\Menu($request))->content();
	}

	protected $request;
	function content( $request, $legacyContent )
	{
		$c = null;
		$this->request = $request;

		{
			$tmp = isset( $this->request->template_data ) && is_array($this->request->template_data) ?
				$this->request->template_data : null;
			$mods = gd( $tmp['template_module'], 'admin' );
			echo <<<HTML
				<div class='module $mods'>
HTML;
		}


		if ( ! function_exists( 'template_content' ) )
		{
			debug( $this, "legacy mode - not a template" );
			echo <<<HTML
				<div class='alert alert-danger'>legacy mode - not a template</div>
				$legacyContent;
HTML;
		}
		else
		{
			#$c = $this->call( 'template_content', $request );
			$this->call_streaming( 'template_content', $request );
		}

		echo <<<HTML
			</div><!-- module -->
HTML;
		#return $this->wrap_content( $c );
	}

	protected function call_streaming( $function_name, $request )
	{
		try
		{
			$function_name( $request );
		}
		catch (Exception $e)
		{
			echo "<pre class='alert alert-danger'>$e</pre>";
		}
	}

	protected function call( $function_name, $request )
	{
		$c = "";

		ob_start();

		try
		{
			$function_name( $request );
		}
		catch (Exception $e)
		{
			$c = "<pre class='alert alert-danger'>$e</pre>";
		}

		return $c = ob_get_clean() . $c;
	}


	
	protected function wrap_content( $c ) {
		$tmp = is_array($this->request->template_data) ?
			$this->request->template_data : null;
		$mods = gd( $tmp['template_module'], 'admin' );
		return <<<HTML
			<div class='module $mods'>$c</div>
HTML;
	}
}

}
