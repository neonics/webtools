<?php
namespace template;

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

