/**
 * Bar Chart render module for jQuery.
 *
 * Please see api/components/bar-chart.php for accompanying server-side code,
 * and it's documentation reference to js/color.js for HSV (angular) colors.
 *
 * usage:
 *
 *   	$(canvas_selector).bar_chart( {
 *			data:
 *			[
 *				{	r:			radius			// circle radius
 *				,	startAngle: radians			// beginning angle, 0..2*PI
 *				,	endAngle:	radians			// ending angle, 0..2*PI
 *				,	label:		display_name	// label for piece
 *				,	labelColor: color			// optional; defaults to red
 *				,	shiftx:		label_x_offset	// optional; defaults to 0
 *				,	shifty:		label_y_offset	// optional; defaults to 0
 *				,	colorIndex:	index_in_colors	// optional: defaults to current array index; -1 is rendered as black/white raster gradient
 *				,	color:		color			// optional; overrides colors[colorIndex]
 *				}
 *			,	...
 *			],
 *			colors: [ string_or_pattern ]		// as many as data.length; anything acceptable by ctx.fillStyle
 *		} );
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
!function($) {

	var mousepos = [-1,-1];

	$.fn.bar_chart = function(action_or_data, options) {
		console.log("!!!! bar_chart function",arguments, "(type: ", typeof action_or_data, ")");
	    return this.each(function() {

			if ( typeof(action_or_data) == 'string' )
				return $.fn.bar_chart[action_or_data].call( $(this), options );

			if ( typeof(action_or_data) != 'object' )
			{
				console.log("bar_chart: unknown type for first argument:", action_or_data);
				return;
			}

			var canvas = this;//document.getElementById('canvas');

			canvas.ctx		= canvas.getContext('2d');
			canvas.h		= canvas.height;
			canvas.w		= canvas.width;
			canvas.data		= action_or_data.data;
			canvas.colors	= action_or_data.colors;
			canvas.labels	= action_or_data.labels;
			canvas.options	= options;

			console.log("INITIALIZING BAR CHART", canvas, "size:", canvas.width, 'x', canvas.height );

			draw( canvas );

			/*
			var prevPiece = null;
			var r = canvas.data[0].r;	// XXX
			canvas.onmousemove = function(e) {
				pos = e.target.getBoundingClientRect();
				mousepos[0] = e.clientX - pos.left;
				mousepos[1] = e.clientY - pos.top;
				//console.log("mousemove", mousepos, canvas, arguments );

				// calculate hover over piece:
				var cx = canvas.w/2;
				var cy = canvas.h/2;

				// calc position relative to center
				var mx = mousepos[0] - cx;
				var my = mousepos[1] - cy;

				if ( mx * mx + my * my < r * r )	// test within circle
				{
					var angle =  -Math.atan2( mx, my ) + Math.PI/2;
					if ( angle < 0 ) angle += Math.PI * 2;
					for ( i in canvas.data )
						if ( canvas.data[i].startAngle < angle
						  && canvas.data[i].endAngle > angle
						)
						{
							if ( prevPiece != null )
								canvas.data[prevPiece].r = r;
							canvas.data[ prevPiece = i ].r = r * 1.2;
						}
					canvas.ctx.clearRect(0,0,canvas.w,canvas.h);
					draw( canvas );
				}
				else if ( prevPiece != null ) // mouseout clear
				{
					canvas.data[prevPiece].r = r;
					canvas.ctx.clearRect(0,0,canvas.w,canvas.h);
					draw( canvas );
				}
			};
			*/
		} );
	};


	/*

Bar Chart data:

canvas.data = [ bar, bar, bar, .... ]
bar = [ {label, quantity }, {..}, ... ]


	*/

	function draw( canvas )
	{
		switch ( (canvas.options || {}).type || 'simple' )
		{
			case 'simple': draw_simple( canvas ); break;
			case '2': draw_2( canvas ); break;
			default: console.log("bar_chart error: unknown draw type: " + canvas.options.type );
		}
	}


	function draw_2( canvas )
	{
		console.log( "draw_2", canvas.width, canvas.data.length );//canvas.data );

		var lw = 100;
		// canvas.labels.length  == canvas.colors.length
		var lh = canvas.height / canvas.labels.length;
		var bar_w = (canvas.width - lw) / canvas.data.length;

		var bars_total = [];
		var bars_max = 0;
		for ( var i in canvas.data )
		{
			var tmp_total = 0;
			for ( var j in canvas.data[i] )
				tmp_total += canvas.data[i][j].quantity_;

			bars_total[i] = tmp_total;
			bars_max = Math.max( bars_max, tmp_total );
		}


		for ( var i in canvas.data )//=0; i <  canvas.data.length; i ++ )
		{
	//		console.log( "draw 'month': ", i, "items: ", canvas.data[i].length, " bar_w: ", bar_w, "x:", bar_w * i, canvas.data[i][0] );
			//if ( i > 0 )

			// FIRST BAR: 0.25
			// SECOND BAR: 0.25
			// REST BARS: 0.5 / num(sub_bars)

			var bw0 = bar_w * 0.25;
			var bw1 = bar_w * 0.25/2;

			// normalized data: all bars 100% for bar-internal relativity
			draw_simple( canvas, canvas.data[i],
				{
					bar_width: bw0,
					x: bar_w * i,
					no_legend: true
				}
			);

			{
				var ctx = canvas.ctx;
				ctx.save();
				ctx.strokeStyle='black';
				ctx.strokeText( i, bar_w * i + bw0/2, canvas.height - 20 );
				ctx.restore();
			}

			// normalized across all bars: bar heights can be compared
			draw_simple( canvas, canvas.data[i],
				{
					quantity_field: 'quantity_',
					quantity_normal: bars_max,
					bar_width: bw1 - 1,
					x: bar_w * i + bw0 + 1,
					no_legend: true,
	//				log: i == 0
				}
			);


			// sub-bars split by type (not stacked as above)
			var bw2 = Math.min( bw1/2, (bar_w - ( bw0 + bw1 )) / canvas.data[i].length );
			console.log( "--- DRAW bw2=",bw2);
			for ( var j = 0; j < canvas.data[i].length; j ++)
				render_bar( canvas,
					0, 								// lo
					canvas.height * canvas.data[i][j].quantity_ / bars_max,	// hi // XXX vertical wrap (probably height - 0 in render_bar) (also with last draw above!!!)
					bar_w * i + bw0 + bw1 + j*bw2,		// startx
					bw2,							// bar width
					{
						colorIndex: canvas.data[i][j].colorIndex || j,
					}
				);
		}

		console.log("DRAW LEGEND: ", canvas.labels );
		var labels = [];
		for ( var i in canvas.labels )
			labels[i] = { label: canvas.labels[i] };

		draw_legend( canvas, labels, lw, lh );
	}



	function draw_simple( canvas, data, options )
	{
		if ( ! data ) data = canvas.data;
		if ( ! options) options = {};
		var bar_width = options.bar_width || 10;// || canvas.width / data.length;
		var startx = options.x || 0;


	//	canvas.ctx.clearRect(0,0,w,h);
		var prev = 0;
		for ( var i in data )
		{
			if ( options.log )
				console.log( "    ", i,
					" normal: ", options.quantity_normal,
					" qfield: ", options.quantity_field,
					"q: ", data[i][options.quantity_field||'quantity'],
					"VAL:",
					data[i][options.quantity_field || 'quantity'] / ( options.quantity_normal || 1 )
				);
				//console.log("   ",i,"  draw simple - bar-width: ", bar_width, "options: ", options, "data:",data );

			render_bar( canvas,
				canvas.height * prev,
				canvas.height * ( prev += (
					data[i][options.quantity_field || 'quantity'] / ( options.quantity_normal || 1 )
				) ),
				startx,
				bar_width,
				{
					colorIndex: data[i].colorIndex || i,
					label: data[i].label,
					color: data[i].color,
				}
			);
		}
		if ( ! options.no_legend )
			draw_legend( canvas, data );
	}

	function draw_legend( canvas, data, lw, lh )
	{
		lw = lw || 100;
		lh = lh || canvas.height / data.length;
		for ( var i in data )
			render_label( canvas,
				data[i].label,
				canvas.height - lh - i * lh + 2,
				{
					colorIndex: data[i].colorIndex || i,
					lw: lw,
					lh: lh * 0.7
				}
			);
	}

	function render_label( canvas, label, y, options )
	{
		var lh = options.lh || 10;
		var lw = options.lw || 100;
		var padding_left = options.padding_left || 0.9 * lw;
		var font_size = 12;

		var ctx = canvas.ctx;
		ctx.save();
		ctx.font = font_size + "px Arial";
		ctx.fillStyle = get_color( canvas, options );
		ctx.strokeStyle = "rgba(0,0,0,.8)";
		ctx.fillRect(   canvas.width - lw, y, lw-1, lh );
		ctx.strokeRect( canvas.width - lw, y, lw-1, lh );

		ctx.fillStyle = "rgba(255,255,255,.9)";
		ctx.strokeStyle = "rgba(0,0,0,.6)";
		ctx.lineWidth = 3;
		ctx.strokeText( label, canvas.width - padding_left, y + lh/2 + font_size/2);
		ctx.lineWidth = 1;
		ctx.fillText(   label, canvas.width - padding_left, y + lh/2 + font_size/2);
		ctx.restore();
	}


	/**
	 * @param options = { colorIndex:0..colors.length, labelColor:#f00, label:string }
	 */
	function render_bar( canvas, lo, hi, left, width, options )
	{
	//	console.log("render_bar", arguments );

		var ctx = canvas.ctx || canvas.getContext('2d');
		ctx.save();
		ctx.strokeStyle= options.color || 'rgba(0,0,0,.5)';//'#fff';//"#000";
		ctx.fillStyle = get_color( canvas, options );
		ctx.lineWidth=0.5;

		left+=1;
		width-=2;
		ctx.fillRect( left, canvas.height - hi, width, hi-lo );
		ctx.lineWidth=1.3;
		ctx.strokeRect( left, canvas.height - hi, width, hi-lo );
		ctx.restore();
	}

	function get_color( canvas, options )
	{
		if ( options.colorIndex == -1 && ! options.color )
		{
			console.log("COLORINDEX -1 USE GRAD:", options );
			var grad = canvas.ctx.createLinearGradient( 0, 0, canvas.width, canvas.height );
			for ( i = 0; i < canvas.width; i +=3 )
				grad.addColorStop( 1.0 * i / (canvas.width), i&1 ? "black" : "white" );
			return grad;
		}
		else
			return options.color || canvas.colors[ options.colorIndex ];	// required
	}

}(jQuery);
