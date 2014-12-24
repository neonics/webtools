<?php
/**
 * Autocomplete Component
 *
 * USAGE:
 *
 *   Define
 *
 *     function autocomplete_query( $q )
 *
 *   returning an already executed PDOStatement
 *   and delegate execution to this file (via require/include).
 *
 *   Next, annotate an input tag with
 *
 *      data-component="autocomplete".
 *   
 *
 * TODO: support multiple input fields per form for the same type.
 # - add data-autocomplete-type (sku etc)
 # - generate id on component init (component.js)
 # - pass on component id to this component
 #   - if given, do not re-install
 #   - used to locate proper input field/popup box.
 */
require_once( "Util.php");

$tmp_id = "autocomplete_".rand();

if ( null !==
	$q = gd( $_REQUEST['q'], null ) )
{
	$q = trim( $q );
	if ( strlen( $q ) )
	{
		if ( function_exists( 'autocomplete_query' ) )
		{
			$sth = autocomplete_query( $q );
			if ( $sth->rowCount() )
			{
				echo <<<HTML
				<style type='text/css' scoped='scoped'>
					ul { list-style: inside none; margin: 0; cursor: pointer; }
					/*li:hover,   // TODO: javascript handler to select (or use select pulldown?) */
					li.active { background-color: rgba( 128,250,128, 1 ); }
					li { padding-left: .5em; }
				</style>
HTML;

				echo "<ul>";
				#echo "<li><b>sql=$sql; args= ". implode(", ", $args )."; res=" . $res . "; results=" . $sth->rowCount() . "</b></li>";

				while ( false !== $row = $sth->fetch() )
					echo "<li>" . $row[0] . "</li>";
				echo "</ul>";
			}
		}
		else
		{
			error( "function 'autocomplete_query' not defined!" );
		}
	}
	return;
}
?>

<script type='text/javascript' id='<?php echo $tmp_id; ?>'>
{
	var tmp_id = '<?php echo $tmp_id; ?>';
	var here = $( '#' + tmp_id );

	console.log("CLOSEST", here.closest('form') );

	if ( here.closest('form').find('input[data-autocomplete]').length )
	{
		console.log("autocomplete handler already installed");
		here.remove();	// we don't want the DOM to continually grow
	}
	else
	{
		console.log("installing autocomplete handler");
		//here.parent().data( 'autocomplete', 'installed' ); // we need actual attribute
		here.parent().attr( 'data-autocomplete', 'installed' ).attr('autocomplete','off');
		here.parent().parent().append( "<div id='" + tmp_id + "_box' class='autocomplete-suggestions' style='display:none;padding:0;margin:0;'></div>" );
		here.parent().parent().append( "<span id='" + tmp_id + "_suggest' class='autocomplete-suggestion'></span>" );

		var box_id = tmp_id + '_box';
		var box = $( '#' + box_id );
		var suggest_id = tmp_id + '_suggest';
		var suggest = $( '#' + suggest_id );

		// hack for now - keep styles local (.autocomplete-suggestions)
		box.css({
			position: 'relative',
			boxShadow: '1px 2px 3px 4px rgba(128,190,128,.5)',
			width: undefined !== here.parent().width() ? here.parent().width() + 5 : '200px',
			minWidth: '100px',
			left: 0,//here.parent().position().left,
			'top': 10,//here.parent().height(),
	//		padding: '1px',
			boxSizing: 'border-box',
		});

		console.log("input coords:", here.parent(), here.parent().position() );

		suggest.css({
			position: 'absolute',
			left: here.parent().position().left,
			top: here.parent().position().top,
			color: 'rgba(0,0,0,.5)',
			padding: '3px', // inclusive border etc..
		});
		here.parent().css({
			background: 'transparent'
		});

		var selIndex = null;
		var prevValue = null;

		// using keypress for keyboard autorepeat
		here.parent().keypress( function(event) {
			console.log( "KEYCODE", event.keyCode, arguments, this);

			var numLi = box.find('li').length;

			var prev = here.parent().data('prev-val');

			switch ( event.keyCode )
			{
				case 38: // up
					if ( prev )	// restore val before tab to continue up/down
					{
						here.parent().val( prev );
						here.parent().data( 'prev-val', null );
					}

					selIndex = selIndex === null ? numLi-1 : --selIndex < 0 ? numLi-1 : selIndex; // % numLi; //Math.max( --selIndex, numLi );
					console.log('selindex UP', selIndex);
					box.find('li').removeClass('active');

					suggest.html( 
						box.find('li:nth-of-type(' + (selIndex+1)+ ')').addClass('active')
						.text()
					);
					return false; // don't adjust cursor
				case 40: // down
					if ( prev )	// restore val before tab to continue up/down
					{
						here.parent().val( prev );
						here.parent().data( 'prev-val', null );
					}
					selIndex = selIndex == null ? 0 : ++selIndex >= numLi ? 0 : selIndex;//(selIndex+1) % numLi; //Math.min( ++selIndex, numLi );
					console.log('selindex DN', selIndex);
					box.find('li').removeClass('active');
					suggest.html( 
						box.find('li:nth-of-type(' + (selIndex+1)+ ')').addClass('active')
						.text()
					);
					return false; // don't adjust cursor (NOP in this case)

				case 9: // tab
					here.parent().data( 'prev-val', here.parent().val() ); // preserve for when up/down again
					here.parent().val( box.find("li:nth-of-type("+(selIndex+1)+")").text() );
					return false; // don't lose focus


				case 13: // enter
					here.parent().val( box.find("li:nth-of-type("+(selIndex+1)+")").text() );
					return;
			}
		} );

		// need separate handler because input value not yet updated
		here.parent().keyup( function(event) {
			if ( event.keyCode == 9	// keep the list when element accepted using tab
				|| event.keyCode == 13 // no ajax on enter (form submit)
			)
				return;

			var v = here.parent().val();
			if ( v == prevValue )
			{
				console.log( "value unchanged:", v);
				return;
			}

			prevValue = v;
			console.log("query for:", v );

			$.ajax({
				type:"POST",//GET
				url:"<?php echo $_SERVER['REQUEST_URI']; ?>",//"/api/component/autocomplete",
				error:function(jqXHR,status,error){console.log("error",status,error)},
				success:function(data,status,jqXHR) {
					console.log( "success", arguments );
					box.html( data );
					box.css( {display: data.length ? 'block' : 'none'} );
					selIndex = null;
					suggest.html('');
				},
				data:{ q: v }
			})

		} );
	}
}
</script>
