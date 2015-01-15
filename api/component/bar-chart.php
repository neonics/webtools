<?php
/**
 * BAR Chart component.
 *
 * Implement:
 *
 *    function bar_chart_get_db() {
 *			return new PDO($dsn,$user,$pass,$attrs);
 *		}
 *
 *
 * REST API parameters:
 *
 * id:				query ID; an SQL string must be stored in $_SESSION under the
 *            given key before invoking this component.
 *
 * grouping:	the column name in the query resultset used for 1-level grouping,
 *						typically X in 'GROUP BY X'.
 *						More grouping columns may be present in the GROUP BY statement,
 *						but data will be aggregated using only the given grouping column.
 *
 *						Multi-level grouping (say, GROUP BY year, month) produces
 *						multidimensional pie-charts, which are not implemented here, but
 *						can be implemented by overriding the 'query_execute' method,
 *						in which case it should call renderPieChart itself multiple times,
 *						potentially using the same canvas_id, and return null so as to
 *						suppress the default render call.
 *
 * quantity:	the name of the summation column in the group-by. For example,
 *						in "SELECT SUM(amount) amount FROM mytable GROUP BY field",
 *						the proper value for this parameter is 'amount', and the proper
 *						value for the grouping parameter is 'field'.
 *
 * canvas-id:	the value of the DOM identifier id attribute to use when
 *						outputting the <canvas> element.
 *
 * canvas-size: the value of the width and height attributes of the outputed
 *						<canvas> element. Currently only square canvases are supported,
 *						since circles are round.
 *
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
require_once('Util.php');	// for gd()

if ( ! function_exists( 'process_resultset' ) ):

function process_resultset( $rows, $grouping, $quantity, $canvas_id, $canvas_size )
{

	ob_start();

	$totals = array();
	$totals_group = array();
	$grand_total = 0;

	echo <<<HTML
		<table class="table">
			<tr>
				<th>$grouping</th>
				<th>$quantity</th>
			</tr>
HTML;

	foreach ( $rows as $i=>$row )
	{
		$g = gd( $row[ $grouping ], null ); // TODO: multi-level

		isset( $totals[$g] ) or $totals[$g] = 0;

		$grand_total+= $row[$quantity];
		$totals[$g] += $row[$quantity];
	}

	foreach ( $totals as $g => $t )
		echo <<<HTML
			<tr>
				<td>$g</td>
				<td>$t</td>
			</tr>
HTML;
	?>
		</table>
	<?php

	$htmltable = ob_get_clean();

	#echo "<pre><b>$query_id</b>\n$query</pre>";

	#OK: flat: renderBarChart( $canvas_id, $canvas_size, $totals, $grand_total );
	renderBarChart2( $canvas_id, $canvas_size, $totals, $grand_total );
	echo $htmltable;

}

endif;




if ( substr( $_SERVER['REQUEST_URI'], 0, 5 ) == '/api/' )	// also included as lib
{
	// see accompanying ../../js/bar_chart.js

	if ( ! isset( $_SESSION ) )	// we need to fetch the query string from the session
		session_start();

	$args = array(); // flat
	$params = array(); // associative
	foreach ( array( 'id', 'grouping', 'quantity', 'canvas-id', 'canvas-size' ) as $param )
		if ( !isset( $_REQUEST[ $param ] ) || !strlen( trim( $_REQUEST[ $param ] ) ) )
		{
			header( "HTTP/1.1 400 Bad request - missing parameter '$param'" );
			return;
		}
		else
		{
			$params[ $param ] =
			$args[] = $_REQUEST[ $param ];
		}

	$rows = query_execute( $params['id'] );
	process_resultset( $rows, $params['grouping'], $params['quantity'], $params['canvas-id'], $params['canvas-size'] );
}


/**
 * @return array( $htmltable, $totals, $grand_total )
 */
function query_execute( $query_id )
{
	$query = gd( $_SESSION[ $query_id ], null );
	if ( $query == null )
	{
		error("no query associated with '$query_id'" );
		return;
	}

	echo "<pre>$query</pre>";

	$db = bar_chart_get_db();	// implemented in override

	gd( $_REQUEST['debug'], 0 ) and $TIME_START = microtime(true);
	$sth = $db->prepare( $query );
	$sth->execute();
	gd( $_REQUEST['debug'], 0 ) and $TIME_MIDDLE = microtime(true);

	$rows = $sth->fetchAll( PDO::FETCH_ASSOC );
	if ( gd( $_REQUEST['debug'], 0 ) ) {
		$TIME_FETCHED= microtime(true);
		printf( "<code><b>TIMING</b> <i>QUERY</i>: %.3f ms  <i>FETCH</i>: %.3f ms</code><br/>",
			($TIME_MIDDLE-$TIME_START)*1000,
			($TIME_FETCHED-$TIME_MIDDLE)*1000
		);
	}
	
	return $rows;
}


/**
 * Requires js/bar_chart.js and js/color.js to be included in the calling Document.
 *
 * @param $canvas_id
 * @param $canvas_size
 * @param $totals				array( name => number )
 * @param $grand_total	array_reduce( $totals, function($a,$b){return $a+$b;} )
 * @param $options			array(
 *												're-use': boolean,	// truthy: do not output <canvas id="$canvas_id">
 *											)
 */
function renderBarChart( $canvas_id, $canvas_size, $totals, $grand_total = null, $options = array() )
{
	$data = array();

	if ( $grand_total == null )
		$grand_total = array_reduce( array_values( $totals ), function($a,$b){return $a+$b;} );
	
	echo "<pre>GRAND TOTAL: $grand_total</pre>";

	$colorIndex=0;
	foreach ( $totals as $cat => $quantity )
	{
		$fillstyle = floor( 255 * $quantity / $grand_total );
		$fillstyle = sprintf( "#%02x%02x%02x", $fillstyle, $fillstyle, $fillstyle );

		$label = "$cat (" . $quantity . ")";

		$data[] = array(
			'quantity'	=> $quantity,
			'colorIndex'=> $cat == '' ? -1 : $colorIndex,
			'label'			=> preg_replace("@'@", '&apos;', $label ),
			'label_'		=> preg_replace("@'@", '&apos;', $cat ),
			'labelColor'=> gd($options['labelColor'], "red"),
			'color'     => gd( $options['name_is_color'], false ) ? strtolower( $cat ) : null,
		);
	}

	foreach ( $data as $i=>&$d )
		$d['quantity']/=$grand_total;

	$data = json_encode( $data );

	$num = count( array_values( $totals ) );
	$colors = implode(',', array_map( function($i) use ($num) {
		return "rgb2hex( hsv2rgb( ".(360*$i/$num).",1,1) )";
	}, range(0, $num) ) );


	if ( ! gd( $options['re-use'], false ) )
	echo <<<HTML
		<canvas id='$canvas_id' width='$canvas_size' height='$canvas_size'></canvas>
HTML;
	echo <<<HTML
		<script type="text/javascript">
			$( '#$canvas_id' ).bar_chart( {
				colors: [ $colors ],
				data  : $data
			} );
		</script>
HTML;
}

function renderBarChart2( $canvas_id, $canvas_size, $totals, $grand_total = null, $options = array() )
{
	$realdata = array();
	$unique = array();
	foreach ( $totals as $month => $bars )
	{
		$grand_total = array_reduce( array_values( $bars ), function($a,$b){return $a+$b;} );
		#$grand_total = 0;
		#foreach ( $bars as $i=>$d )
		#	$grand_total += $d;
		#echo "<pre>GRAND TOTAL: $grand_total ($grand_total_old)</pre>";

		$data = array();
		foreach ( $bars as $cat => $quantity )
		{
			$label = sprintf( "%s (%2.1f%%)", $cat, 100*$quantity/$grand_total);

			$colorIndex = 0;
			if ( isset( $unique[$cat] ) )
				$colorIndex = $unique[ $cat ];
			else
				$unique[ $cat ] = count( $unique );

			$data[] = array(
				'quantity'	=> $quantity / $grand_total,
				'quantity_'	=> $quantity,
				'colorIndex'=> $colorIndex,//$cat == '' ? -1 : $colorIndex,
				'label'			=> preg_replace("@'@", '&apos;', $label ),
	#			'labelColor'=> gd($options['labelColor'], "red"),
	#			'color'     => gd( $options['name_is_color'], false ) ? strtolower( $cat ) : null,
			);
		}
		# done above
		#foreach ( $data as $i=>&$d )
		#	$d['quantity']/=$grand_total;

		$realdata[ $month ] = $data;
	}

	ksort( $realdata, SORT_NUMERIC );
	$realdata = array_values ( $realdata ); // make sure data is array

	$data = json_encode( $realdata );

	$num = count( $unique );//count( array_values( $totals ) );
	$colors = implode(',', array_map( function($i) use ($num) {
		return "rgb2hex( hsv2rgb( ".(360*$i/$num).",1,1) )";
	}, range(0, $num) ) );

	$labels = json_encode( array_values( array_flip( $unique ) ) );

	$canvas_height = $canvas_size;
	$canvas_width = 3 * $canvas_height;

	if ( ! gd( $options['re-use'], false ) )
	echo <<<HTML
		<canvas id='$canvas_id' width='$canvas_width' height='$canvas_height'></canvas>
HTML;
	echo <<<HTML
		<script type="text/javascript">
			$( '#$canvas_id' ).bar_chart( {
				colors: [ $colors ],
				labels: $labels,
				data  : $data
			},
			{ type: '2'
			} );
		</script>
HTML;
}

