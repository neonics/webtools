<?php
namespace template;

require_once 'html.php';

class Bootstrap {

	/**
	 * @param array $arr A [ $title => $content ] associative array.
	 * @param string $panelgroupclass Additional classes for the panel-group
	 * @param string $panelclass The panel-class to use (default: panel-default) and possible additional css classes.
	 */
	public static function accordion( $arr, $panelgroupclass = '', $panelclass = 'panel-default' ) {
		$ret = null;
		foreach ( $arr as $title => $content )
			$ret .= self::panel( self::collapse_link( $title, $content ), $panelclass );

		return <<<HTML
			<div class='panel-group $panelgroupclass'>
				$ret
			</div>
HTML;
	}

	/**
	 * @param array $label_data [ $panel_heading_content, $panel_body_content ]. Takes self::collapse_link()'s result.
	 * @param string $panelclass The panel class to use (default: 'panel-default') and possible additional css classes.
	 * @return string A panel.
	 */
	public static function panel( $label_data, $panelclass = "panel-default" )
	{
		if ( ! is_array( $label_data ) )
			throw new Exception( __METHOD__.": illegal call: first argument not array(\$label, \$data)" );

		return <<<HTML
			<div class='panel $panelclass'>
				<div class='panel-heading'>{$label_data[0]}</div>
				<div class='panel-body'>{$label_data[1]}</div>
			</div>
HTML;
	}


	/**
	 * @param string $label The link tabel.
	 * @param string $data The collapsable content.
	 * @param string $tag Tag to wrap content in - default 'div'.
	 * @param string $classes Additional CSS classes for $tag.
	 * @return array [$anchor_tag, $tag_wrapped_data]
	 */
	public static function collapse_link( $label, $data, $tag='div', $classes=null ) 
	{
		$id = html_id( 'cl' );

		return array( <<<HTML
			<a data-toggle='collapse' data-target='#$id'>$label<b class='caret'></b></a>
HTML
			, <<<HTML
			<$tag id='$id' class='collapse $classes'>
				 $data
			</$tag>
HTML
		);
	}

	/**
	 * @param string $label The link label.
	 * @param string $data The collapsable content.
	 * @param string $rc (optional) Additional content after the link tabel.
	 * @return array [ $collapse_link, $collapsed_row ]
	 */
	public static function collapse_row( $label, $data, $rc = null )
	{
		$id = html_id( 'tbl' );

		return array( <<<HTML
			<a data-toggle='collapse' data-target='#$id'>$label <b class='caret'></b></a> $rc
HTML
			, <<<HTML
			<tr id='$id' class='collapse'>
				<td colspan='99'>
				 $data
				</td>
			</tr>
HTML
		);
	}
}
