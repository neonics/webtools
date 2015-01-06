<?php
/**
 * PIE Chart component.
 *
 * Implement:
 *
 *    function pie_chart_get_db() {
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

	renderPieChart( $canvas_id, $canvas_size, $totals, $grand_total );
	echo $htmltable;

}

endif;




if ( substr( $_SERVER['REQUEST_URI'], 0, 5 ) == '/api/' )	// also included as lib
{
	// see accompanying ../../js/pie_chart.js

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

	$db = pie_chart_get_db();	// implemented in override

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
 * Requires js/pie_chart.js and js/color.js to be included in the calling Document.
 *
 * @param $canvas_id
 * @param $canvas_size
 * @param $totals				array( name => number )
 * @param $grand_total	array_reduce( $totals, function($a,$b){return $a+$b;} )
 * @param $options			array(
 *												're-use': boolean,	// truthy: do not output <canvas id="$canvas_id">
 *											)
 */
function renderPieChart( $canvas_id, $canvas_size, $totals, $grand_total = null, $options = array() )
{
	$data = array();

	if ( $grand_total == null )
		$grand_total = array_reduce( array_values( $totals ), function($a,$b){return $a+$b;} );

	$angle = 0;
	$r = $canvas_size / 2 * 0.5;
	$colorIndex=0;
	foreach ( $totals as $cat => $quantity )
	{
		$angleprev = $angle;
		$angle +=  pi() * 2 * $quantity / $grand_total;
		$fillstyle = floor( 255 * $quantity / $grand_total );
		$fillstyle = sprintf( "#%02x%02x%02x", $fillstyle, $fillstyle, $fillstyle );

		$avgangle = $angleprev + ( $angle-$angleprev )/2;
		$label = "$cat (" . $quantity . ")";

		$degAngle = $avgangle * 180 / pi();
		# XXX FIXME unsigned mod:
		while ( $degAngle < 0 ) $degAngle += 360;
		while ( $degAngle > 360 ) $degAngle -= 360;

		$data[] = array(
			'r'					=> $r,
			'startAngle'=> $angleprev,
			'endAngle'	=> $angle,
			'colorIndex'=> $cat == '' ? -1 : $colorIndex,
			'label'			=> preg_replace("@'@", '&apos;', $label ),
			'label_'		=> preg_replace("@'@", '&apos;', $cat ),
			'labelColor'=> gd($options['labelColor'], "red"),
			'shiftx'		=> - $degAngle <  90 || $degAngle > 270 ? 0 :( strlen( $label ) * 3 * ($degAngle > 90 && $degAngle < 270 ? 1:-1) ),
			'shifty'		=> + $degAngle < 180 || $degAngle > 270 ? 4 : 0,
			'color'     => gd( $options['name_is_color'], false ) ? strtolower( $cat ) : null,
		);
	}

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
			$( '#$canvas_id' ).pie_chart( { colors: [ $colors ], data  : $data } );
		</script>
HTML;
}

