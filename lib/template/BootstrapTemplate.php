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

class BootstrapTemplate extends AbstractTemplate
{
	protected $themes = ['basic'];
	protected $theme = 'basic';
	protected $anim  = "anim";

	public $noticebar = null;

	public function __construct( $request = null ) {
		parent::__construct( $request );
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
			<link rel="icon" href="{$this->request->requestBaseURI}img/favicon.ico" type="image/x-icon"/>
HTML;
	}

	/** @Override */
	protected function themeCSS() {
		global $request;
    return <<<HTML
			<!-- Bootstrap -->
			<!--
			<link href="{$this->request->requestBaseURI}css/bootstrap.min.css" rel="stylesheet">
			-->
			<link href="{$this->request->requestBaseURI}css/3p/bootstrap.min.css" rel="stylesheet">

			<!-- structural requirements -->
			<link href="{$this->request->requestBaseURI}css/style.css" rel="stylesheet">

			<!-- styling: skin -->
			<link href="{$this->request->requestBaseURI}css/skin-{$this->theme()}.css" rel="stylesheet">

			<!-- -->
			<!--link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet"-->
			<link href="{$this->request->requestBaseURI}css/3p/font-awesome.min.css" rel="stylesheet">
HTML;
	}

	/** @Override */
	protected function themeJS() {
		return <<<HTML
    <script src="{$this->request->requestBaseURI}js/3p/jquery.min.js"></script>
    <script src="{$this->request->requestBaseURI}js/3p/bootstrap.min.js"></script>
		<script src='{$this->request->requestBaseURI}js/component.js'></script>
		<script src='{$this->request->requestBaseURI}js/noticebar.js'></script>
HTML;
	}

}

} // end namespace
