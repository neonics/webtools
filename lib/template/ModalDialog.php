<?php
namespace template;

class ModalDialog
{

	const BUTTON_REFRESH = 1;
	const BUTTON_SAVE = 2;

	static $top_button_definitions = [
		self::BUTTON_REFRESH => <<<HTML
			<button type="button" class="refresh" aria-label="Refresh"><span aria-hidden="true"><i class='fa fa-refresh'></i></span></button>
HTML
	, self::BUTTON_SAVE => <<<HTML
			<button type="button" class="save btn-primary" aria-label="Save"><span aria-hidden="true"><i class='fa fa-save'></i></span></button>
HTML
	];

	static $bottom_button_definitions = [
		self::BUTTON_SAVE => <<<HTML
			<button type="button" class="btn btn-primary">Save changes</button>
HTML
	];


	static function render( $id, $title, $button_flags = 0, $body = null ) {

		if ( ! $body )
			$body = " ... (dialog only shows after ajax complete)";

		$top_buttons = implode( '', array_map( function($f,$h) use ( $button_flags ) {
			return $button_flags & $f ? $h : null;
		}, array_keys( self::$top_button_definitions ), array_values( self::$top_button_definitions ) ) );

		$bottom_buttons = implode( '', array_map( function($f,$h) use ( $button_flags ) {
			return $button_flags & $f ? $h : null;
		}, array_keys( self::$bottom_button_definitions ), array_values( self::$bottom_button_definitions ) ) );

		echo <<<HTML

		<!-- Modal -->
		<div class="modal NO-fade" id="{$id}" tabindex="-1" role="dialog" aria-labelledby="{$id}Label" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="{$id}Label"> $title $top_buttons </h4>
					</div>
					<div class="modal-body">
						$body
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						$bottom_buttons
					</div>
				</div>
			</div>
		</div>

HTML;

	global $request;

	if ( ! self::$_js_ajaxify++ )
	echo <<<HTML
		<script type='text/javascript' src='{$request->requestBaseURI}js/ajaxify.js'></script>

HTML;
	}

	private static $_js_ajaxify = 0;

}
