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

function html_array( $container, $element, $array, $function = null )
{
	if ( ! count( $array ) ) return "";

	if ( ! isset( $array[0] ) )
	{
		warn( "html_array: malformed array" . fold_debug( $array ) . html_tag( 'pre', stacktrace() ) );
		return "";
	}

	if ( is_callable($function) )
		$array = array_map( function($v) use( $function, $array, $element ) { return $function( $v, $element );}, $array );

#	if ( ! count( $array ) ) {
#		warn( "html_array:
#	}

	return is_array( $array[0] )
		? html_array_complex( $container, $element, $array )
		: html_array_simple( $container, $element, $array )
	;
}

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

	return "<$container>"
		. implode( '', array_map(
				function($v) use ($element) {
					$attrs = count($v) <=1 ? "" :
						implode( " ", array_map(
							function($k,$v) { return "$k=\"".htmlspecialchars( (is_array($v)?implode(" ", $v):$v) )."\""; },
							array_keys( $v[1] ),
							array_values( $v[1] )
						) )
					;
					return "<$element $attrs>" . $v[0] . "</$element>";
				},
				$array
		) )
		. "</$container>";

}

function html_array_simple( $container, $element, $array )
#{	is_array( $array[0] ) || is_array( $element ) || is_array( $container ) and debug_backtrace();
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

	return $element{0} >= 'a' && $element{0} <= 'z'
	? "<$container><$element>" . implode("</$element><$element>", $array) . "</$element></$container>"
	: "<$container>$element" . implode($element, $array) . "$element</$container>"	// no enters because of javascript
	;
}

function html_tag( $name, $content, $attributes = null )
{
#	if ( ! is_string( $content ) ) xchg( $content, $attributes );
#	$attributes = html_combine( $attributes );
	return "<$name>$content</$name>";
}

function html_combine( $array ) {
#	echo fold_debug(func_get_args(), "html_combine");
	if ( ! is_array( $array ) ) error( __FUNCTION__
		."(array): illegal call: (".gettype($array).")"
		. html_tag( 'pre', print_r(func_get_args(),1) . "\n" . stacktrace() ) );
	return
	implode( "", array_map(
		function($k,$v) { return " $k=\"".htmlspecialchars( $v )."\""; },
		array_keys( $array ), array_values( $array )
	) );
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


function html_form_button_action( $action, $label = null, $hidden = null, $buttonClasses = 'btn btn-primary', $form_action = null, $form_attrs = null )
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
