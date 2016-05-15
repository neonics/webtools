<?php
namespace template;

use Menu; // no effect? see function menu()

abstract class AbstractTemplate
{
	var $request;

	public function __construct( $req = null ) {
		global $request;
		$this->request = $req === null ? $request : $req;
	}

	protected function head_meta() {
		return <<<HTML
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
HTML;
	}


	protected function favicon() {
		global $request;
		return <<<HTML
			<link rel="icon" href="{$this->request->requestBaseURI}img/favicon.ico" type="image/x-icon"/>
HTML;
	}


	protected function themeCSS() {
		global $request;
    return <<<HTML
		<!--link rel='stylesheet' href='{$this->request->requestBaseURI}css/TODO.min.css'/-->
HTML;
	}

	protected function themeJS() {
		return <<<HTML
		<!--script src='TODO'></script-->
HTML;
	}

	protected function menuJS() {
		return <<<HTML
		<script defer src="{$this->request->requestBaseURI}js/menu.js"></script>
HTML;
	}

	protected function html_attrs() {
		return "lang='en'";
	}

	function menu()
	{
		return ( new \template\Menu( $this->request ) )->content();
	}


	var $scripts = [];

	/**
	 * @return string
	 */
	protected function scripts( $request ) {
		return implode( "\n", array_map( function($_) {
			return "<script type='text/javascript' src='$request->requestBaseURI$_'></script>";
		}, $this->scripts
		) );
	}

	function main( $request, $legacyContent )
	{
		if ( !isset( $this->noticebar ) )
			$this->noticebar = new NoticeBar( $this );

		// allow to modify the header
		if ( function_exists( 'template_init' ) )
			$request->template_init = $this->call( 'template_init', $request );

		$this->head();

		$menu = $this->menu();
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
		{$this->menuJS()}
HTML;
	if ( ob_get_level() ) ob_flush();
	flush();

	echo <<<HTML
		<div class="theme {$this->theme}" id='content'><!-- class container-fluid -->
HTML;

	$this->content($request, $legacyContent);

echo <<<HTML
		</div>
HTML;

		echo $this->scripts( $request );

echo <<<HTML
  </body>
</html>
HTML;

		flush();
	}


	function content( $request, $legacyContent )
	{
		$c = null;
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


	function head()
	{
		#echo "REQUEST: <pre>".print_r($request,1)."</pre>";
		echo <<<HTML
<!DOCTYPE html>
<html {$this->html_attrs()}>
  <head>
		{$this->head_meta()}
		{$this->favicon()}

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

		{$this->themeCSS()}
		{$this->themeJS()}
  </head>
HTML;

		if ( ob_get_level() ) ob_flush();
		flush();
	}


	protected function call_streaming( $function_name, $request )
	{
		try
		{
			$function_name( $request );
		}
		catch (Exception $e)
		{
			echo "<pre class='alert alert-danger'><b>Exception</b>: ".$e->getMessage()."\nCaught at ".__METHOD__."</pre>";
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
			$c = "<pre class='alert alert-danger'><b>Exception</b>: ".$e->getMessage()."\nCaught at ".__METHOD__."</pre>";
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
