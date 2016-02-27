<?php
/**
 * @param $fn a callable function, returning either a string or an array.
 *        The first value must be the string otherwise returned.
 *        The second value, if present, is printed after the row ends, and can be used to inject custom rows.
 *        The third value, if present, is an attribute map (i.e. [key=>value]), injected into the <td>.
 * @param $columns array( dbcol => false || '' || label )
 */
function html_table( & $result, $fn = null, $columns = null, $hdrfn = null, $opts = [] )
{
	$debug=false;

	if ( ! isset( $fn ) )
		$fn = function($k,$v,$row,$i=null) { return $v===null?"<i style='color:grey'>NULL</i>":$v; };

	$debug and print "<pre><b>columns:</b> ".print_r( $columns, true ). "</pre>";

	$rowmap = array();
	if ( isset( $columns ) )
	{
			if ( isset( $columns['*'] ) )
			{
				foreach ( $result[0] as $col=>$ignore )
				{
#					echo "<code>$col : $ignore -- </code>";
					$v = isset( $columns[ $col ] ) ? $columns[ $col ] : null;

					if ( $v !== false )
						$rowmap[ $col ] = $v === null ? $col : $v;
				}

#				foreach ( $columns as $i=>$col )
#					$rowmap[ $col ] = $col;
			}
			else
				foreach ( $columns as $k=>$l )
					if ( $l !== false )
						$rowmap[ $k ] = $l == '' ? $k : $l;
	}
	else // no columns - use all
		if ( count( $result ) )
			foreach ( $result[0] as $colname=>$value )
				$rowmap[ $colname ] = $colname;


	$table_id = gad( $opts, 'id' );

	echo <<<HTML
	<table class='table sql' id="$table_id">
		<thead>
			<tr>
HTML;
/*
		if ( isset( $columns ) )
		foreach ( $columns as $k=>$v )
		{
			if ( isset( $result[0][ $k ] ) )
				echo "<th>$v</th>";
			else
				#die ("unknown column: $k; columns: " .implode(',', array_keys($result[0]) ) );
				echo "<th style='color:red'>$k($v)</th>";
		}
		else
		//foreach ( $result[0] as $k=>$v ) echo "<th>A $k</th>";
		*/
		foreach ( $rowmap as $k=>$v ) echo "<th class='sql-col-$k'>",
			(is_callable($hdrfn) ? $hdrfn( $k, $v ) : "$v")."</th>";

	echo <<<HTML
			</tr>
		</thead>
		<tbody>
HTML;


	foreach ( $result as $i=> &$row )
	{
		echo "<tr>";
		$post = array();
		if ( isset( $columns ) )
		{
			foreach ( $rowmap as $k=>$l )
			{
				if ( $k== '*' )	# skip filter rule
					continue;

				$x = array_key_exists($k, $row)
					? $row[$k]
					: "<span style='color:red'>unknown column '$k' (label '$l')</span>";
				$x = $fn( $k, $x, $row, $i );

				$post[] = _echo_col( $k, $x );
			}
		}
		else
			foreach ( $row as $k=>$v )
			{
				$x = $fn($k,$v, $row, $i);
				$post[] = _echo_col( $k, $x );
			}
		echo "</tr>\n";

		// XXX check if at least one post value set
		foreach ( $post as $j=>$v )
			echo $v."\n";
			#"<tr><td colspan='99'>$v</td></tr>\n";
	}

	echo <<<HTML
		</tbody>
	</table>
HTML;

	return $result;
}



function _echo_col( $k, $x )
{
	$x = $x === null
		? "<i style='color:grey'>NULL</i>"
		: $x;
	$a = is_array( $x ) ? ( count($x) ? $x[0] : null ) : $x;
	$a = $a === null ? "<i style='color:grey'>NULL,/i>" : $a;
	$b = ! is_array( $x ) || count( $x ) <= 2 ? null :
		" " . implode(' ', array_map( function($X,$Y){return "$X=\"".htmlspecialchars($Y)."\"";}, array_keys( $x[2] ), array_values( $x[2] ) ) )
	;
	echo "<td class='sql-col-$k'$b>$a</td>";

	return is_array( $x ) && count($x)>1
		? $x[1]
		: null;
}


/**
 * @param $array associative array; keys are option values, values are labels
 * @param $selected selected key
 */
function html_options( $array, $selected = null, $show_none = true )
{
	return ( $show_none ? "<option value=''>".(is_string( $show_none ) ? $show_none : '-none-')."</option>" : "" )
	.	implode('',
			array_map
			(
				function($k, $v) use($selected) {
					return "<option value='".esc_attr($k)."'".($selected==$k?" selected":"").">$v</option>";
				},
				array_keys( $array ),
				array_values( $array )
			)
		);
}

function html_radio( $name, $array, $selected = null )
{
	$name=esc_attr($name);

	return implode('', array_map(
		function($k, $v) use($name, $selected) {
			$value = esc_attr( $k );
			$id = slug("radio-$name-$k");
			return "<input type='radio' id='$id' name='$name' value='$value'".($selected==$k?" checked":"")."/>
			<label for='$id'>$v</label>";
		},
		array_keys( $array ),
		array_values( $array )
	) );
}

function html_ul( $array, $function = null ) { return html_array( "ul", "li", $array, $function ) ; }

/** @param $array associative array */
function html_dl( $array ) {
	return html_tag( "dl", implode( "",
		array_map( function($k,$v) {
			return "<dt>$k</dt>"
				. ( is_array( $v )
					? implode( "", array_map( function($x) { return "<dd>$x</dd>\n"; }, $v ) )
					: "<dd>$v</dd>\n" )
				;
		}, array_keys($array), array_values($array) )
	) );
}

/**
 * This method turns an array into HTML, typically used for <ul><li/></ul>.
 *
 * @param string $container The tag name and possible attributes for the container element, for example "div class='foo'", 
 * @param string $element Likewise for the element tag wrapping each array entry.
 * @param array $array array of strings (calls html_array_simple) or array of arrays (calls html_array_complex).
 * @param callable $function (optional) when given, transforms the $array using this function. Signature: function($arrayitem, $element).
 * @return string A HTML representation of the array.
 */
function html_array( $container, $element, $array, $function = null )
{
	if ( ! count( $array ) ) return "";

	if ( ! is_array( $array ) || ( ! isset( $array[0] ) && ! array_key_exists( 0, $array ) ) )
		trigger_error( __FUNCTION__ . ": malformed array", E_USER_WARNING );

	if ( is_callable($function) )
		$array = array_map( function($v) use( $function, $array, $element ) { return $function( $v, $element );}, $array );

	return is_array( $array[0] )
		? html_array_complex( $container, $element, $array )
		: html_array_simple( $container, $element, $array )
	;
}



/**
 * Handles html_array calls where $array is an array of arrays. Each $array entry is
 * a 2 element array, the first element being the content, the second being an associative
 * array with element attributes, injected into the $element tag that wraps the entry.
 *
 * Example: <pre>html_array_complex( 'ul', 'li', [ ['foo'], ['bar', ['class' => 'test'] ] ] )</pre>
 * produces <xmp><ul><li>foo</li><li class='bar'>bar</li></ul></xmp>.
 *
 * @param string $container the container element to use.
 * @param string $element The element to wrap each array entry in.
 * @param array $array An array of [ $content, [ elAttrKey => elAttrValue ] ].
 * @see html_array.
 */
function html_array_complex( $container, $element, $array )
{
	if ( is_array( $element ) || is_array( $container )
		|| $element{0} < 'a' || $element{0} > 'z'
	)
	fatal( __FUNCTION__ . htmlentities(
		": illegal call; signature: (string, string, array<string> ); call: ("
		. gettype( $container )
		.','
		. gettype( $element )
		.','
		. gettype( $array )
		. (!is_array($array)?"":
			'<' .gettype( $array[0] ) .'>'
			)
	.")"
	) );

	$elementName = html_strip_attributes( $element );

	return "<$container>"
		. implode( '', array_map(
				function($v) use ($element, $elementName) {
					$attrs = count($v) <=1 ? "" :
						implode( " ", array_map(
							function($k,$v) { return "$k=\"".htmlspecialchars( (is_array($v)?implode(" ", $v):$v) )."\""; },
							array_keys( $v[1] ),
							array_values( $v[1] )
						) )
					;
					return "<$element $attrs>" . $v[0] . "</$elementName>";
				},
				$array
		) )
		. "</" . html_strip_attributes($container) . ">";

}

/**
 * Implementation of html_array taking an array of strings.
 *
 * Example: <pre>html_array_simple( 'ul', 'li', [ 'foo', 'bar' ] )</pre>
 * produces <xmp><ul><li>foo</li><li>bar</li></ul></xmp>
 *
 * @param string $container
 * @param string $element
 * @param array $array array of strings
 */
function html_array_simple( $container, $element, $array )
{
	if(is_array( $array[0] ) || is_array( $element ) || is_array( $container ) )
	fatal( __FUNCTION__ . htmlentities(
		": illegal call; signature: (string, string, array<string> ); call: ("
		. gettype( $container )
		.','
		. gettype( $element )
		.','
		. gettype( $array )
		. (!is_array($array)?"":
			'<' .gettype( $array[0] ) .'>'
			)
	.")"
	) );

	$elementName = html_strip_attributes( $element );

	return html_tag( $container, "<$element>" . implode("</$element><$elementName>", $array) . "</$elementName>" );
}

function html_tag( $name, $content )
{
	return "<$name>$content</".html_strip_attributes( $name ).">";
}

function html_strip_attributes( $el ) {
	return ( $pos = strpos( $el, ' ' ) ) === false ? $el : substr( $el, 0, $pos );
	//return preg_replace( '/ .*$/', null, $el );
}


function html_combine( $array ) {
	if ( empty( $array ) ) return null;
#	echo fold_debug(func_get_args(), "html_combine");
	if ( ! is_array( $array ) ) error( __FUNCTION__
		."(array): illegal call: (".gettype($array).")"
		. html_tag( 'pre', print_r(func_get_args(),1) . "\n" . stacktrace() ) );
	return ' ' .
	implode( "", array_map(
		function($k,$v) { return " $k=\"".htmlspecialchars( $v )."\""; },
		array_keys( $array ), array_values( $array )
	) ) . ' ';
}

define( 'HTML_SINGULAR_TAGS', "link" );
function html_open( $name, $attributes = null ) {
#	echo fold_debug( func_get_args() );
	$attributes = empty( $attributes ) ? "" : html_combine( $attributes );
#	echo fold_debug( $attributes );
	return in_array( $name, explode( " ", HTML_SINGULAR_TAGS ) )
	? "<$name$attributes/>"
	: "<$name$attributes>"
	;
}

function html_close( $name ) {
	return in_array( $name, explode( " ", HTML_SINGULAR_TAGS ) )
	? null
	: "</$name>";
}


function html_form_button_action( $action, $label = null, $hidden = null, $buttonClasses = 'btn btn-primary', $form_action = null, $form_attrs = 'class="form-button-action"' )
{
	$action_str = htmlspecialchars( $action );
	$label = gd( $label, $action );
	$form_action = is_null( $form_action ) ? null : " action='$form_action'";
	return <<<HTML
		<form method='post' $form_attrs $form_action>
			$hidden
			<button name='action:{$action_str}' class='$buttonClasses'>$label</button>
		</form>
HTML;
}


/** Generates an identifier that's usable in HTML, JS, and PHP. */
function html_id($prefix=null, $stackdepth=1) {
	if ( $prefix == null ) {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $stackdepth + 1 );
		$caller = array_shift( $trace );
		$prefix = basename( $caller['file'], '.php' );
	}

	return str_replace('-','_', $prefix ) . '_' . hash('md5', __FILE__ . $prefix . microtime(true) . rand() );
}
