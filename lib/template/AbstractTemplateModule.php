<?php
namespace template;

abstract class AbstractTemplateModule {

	/**
	 * Override/initialize this in subclasses. 
	 */
	public $config = [];

	/**
	 * @return string Label for module mmenu
	 */
	public function module_menu( $request ) {
		if ( isset( $this->config['pages'] ) )
			echo self::render_pages( $this->config['pages'] );
		echo $request->module_data->menu;
		return isset( $this->config['template_module_menu_label'] )
			? __( $this->config['template_module_menu_label'] )
			: get_class($this);
	}

	/**
	 * @param array $pages format: [ 'pagename' => [ 'label' => '...', ], 'anotherpage' => [..] ];
	 */
	protected static function render_pages( $pages ) {
		return implode( '', array_map( function( $name, $data ) {
			return html_tag( [ 'li', 
				array_merge(
					isset( $data['permission'] ) ? [ 'data-auth-permission' => $data['permission'] ] : [],
					isset( $data['role']       ) ? [ 'data-auth-role'       => $data['role']       ] : [],
					isset( $data['views']			 ) ? [ 'class' => 'dropdown' ] : []
				)
			],
			isset( $data['hide'] ) && $data['hide'] ? [] :
			[
				html_tag( [ 'a', [ 'href' => $name ] ], [
					isset( $data['icon'] ) ? html_icon( $data['icon'] ) : null,
					isset( $data['label'] ) ? __( $data['label'] ) : $name,
					isset( $data['labelsuffix'] ) ? $data['labelsuffix'] : null,
					isset( $data['badge'] ) && $data['badge'] ? ' ' . html_tag( "span class='badge badge-primary'", $data['badge'] ) : null,
				] ),
				isset( $data['views'] )
					? html_tag( [ 'ul', [ 'class' => 'dropdown-menu' ] ],
						self::render_pages(
							assoc_array( array_map(function($vname,$vdata) use($name) { return [ "$name?view=$vname", $vdata ]; }, array_keys($data['views']), array_values($data['views']) ))
						)
					)
					: null
			] );
		},
			array_keys( $pages ),
			array_values( $pages )
		) );
	}
}
