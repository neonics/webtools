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
