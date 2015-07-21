<?php
namespace template;

require_once 'action.php';

abstract class View {
	protected $state;

	public $enabled = true;
	public $active = false;	// view should set this to true if it detects an action it handles
	public $actionActive = false;
	public $id;
	public $navlabel;
	public $navbadge = null;
	public $navbadge_type = "default"; // badge-*

	public function __construct( &$state, $id, $navlabel ) {
		$this->state = $state;
		$this->id = $id;
		$this->navlabel = $navlabel;
	}

	public function render() {
		ob_start();
		$this->actionActive = $this->isViewAction(); // can be overriden for cross-tab actions
		try {
			$this->_render();
		} catch ( Exception $e ) {
			error( get_class( $this ), $e );
		}
		return ob_get_clean();
	}

	protected function isViewAction()
	{
		foreach ( getActions() as $a => $data )
		{
			if ( strpos( $data[0], "$this->id-" ) === 0 )
				return true;
			}
		return false;
	}

	protected abstract function _render();
}

class HTMLView extends \template\View {
	private $data = array();
	private static $_view_id = 0;

	public function __construct( &$state, $id, $label, $html ) {
		if ( ! $id ) $id = __CLASS__ . "_id_" . ++ self::$_view_id;
		parent::__construct( $state, $id, $label );
		$this->data = $html;
	}

	protected function _render() {
        $tmp = $this->data;
		if ( is_callable( $tmp ) )
        /*
            gettype($this->data)=='function'
		|| ( gettype($this->data)=='object' && get_class( $this->data ) == 'Closure' )
		|| ( gettype( $this->data ) == 'string' && function_exists( $this->data ) )
		*/
        {  $tmp(); }
		else echo $this->data;
	}
}




class TabRenderer {
	var $state;
	var $components = array();

	var $navClasses = 'nav nav-pills';

	public function __construct( &$state ) {
		$this->state = $state;
		$state->infotab = null;
	}

	public function add( $component ) {
		$this->components[] = $component;
	}

	public function addAll( $array ) {
		$this->components = array_merge( $this->components, $array );
	}


	function render()
	{
		// aggregate output
		$taboutput = array();
		foreach ( $this->components as $tab )
		{
			$taboutput[] = $tab->render();
		}

		// find active tab
		$cssClasses = array();
		$haveActive = false;

		// HACK - allow tab request param to auto-select tab
		// might want to use url hash, except for nested tabs...
		if ( $atl = gad( $_REQUEST, 'tab' ) ) {
			if ( !is_array( $atl ) ) // nested tabs: multiple params
				$atl = [ $atl ];

			foreach ( $atl as $at )
				foreach ( $this->components as $i => $tab )
					if ( $tab->id == $at ) {
						$haveActive = $i;
						$tab->active = true;
					}
		}


		foreach ( $this->components as $i => $tab )
			if ( $tab->active ) {
				$haveActive = $i; break;
			}

		foreach ( $this->components as $i => $tab )
			if ( $tab->actionActive ) {
	 			$haveActive = $i; break;
			}

		if ( ! $haveActive )
			$haveActive = 0;

		foreach ( $this->components as $i => $tab )
			$cssClasses[] = $haveActive === $i ? 'active': ( $tab->enabled ? '' : 'disabled' );


		$tabnav = "";
		$tabcontent = "";

		foreach ( $this->components as $i => $tab )
		{
			$b = $tab->navbadge ? "<span class='badge badge-$tab->navbadge_type'>$tab->navbadge</span>" : "";
			$tabnav .= <<<HTML
				<li class='{$cssClasses[$i]}'><a href="#$tab->id" role='tab' data-toggle='tab'>$tab->navlabel $b</a></li>
HTML;
			$tabcontent .= <<<HTML
				<div class='tab-pane {$cssClasses[$i]}' id="$tab->id">{$taboutput[$i]}</div>
HTML;
		}

		echo <<<HTML
			<ul class='{$this->navClasses}' role='tablist'>
				$tabnav
				{$this->state->infotab}
			</ul>
			<div class='tab-content' style='margin: 0; border: 0'>
				$tabcontent
			</div>
HTML;
	}
}

