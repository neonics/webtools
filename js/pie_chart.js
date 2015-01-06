/**
 * Pie Chart render module for jQuery.
 *
 * Please see api/components/pie-chart.php for accompanying server-side code,
 * and it's documentation reference to js/color.js for HSV (angular) colors.
 *
 * usage:
 *
 *   	$(canvas_selector).pie_chart( {
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
 *
 * The data is generally pre-processed from an SQL query yielding quantities,
 * normalized against the sum of the quantities, where each endAngle is the
 * startAngle of the next piece of pie.
 * It is possible to use different radii for each piece, but this is not recommended
 * except for UI effects.
 *
 * The colors array contains one color for each piece of pie. These are referenced
 * by the colorIndex, which defaults to the index into the data array. Use of the
 * separate colors array is optional when each piece declares a color attribute.
 *
 * XXX This code is a work-in-progress and may change. The following changes
 * are expected:
 * - removal of the colors option;
 * - removal of startAngle and endAngle, to be replaced by a 'quantity' attribute;
 *   angles will then be calculated as described above.
 * - extracting 'r' into a separate option, 'global; for all pieces;
 * - replacement of the shiftx/shifty offsets by a 'global' option specifying different
 *   label layout algorithms
 * The API will then likely become something like this:
 *
 *  $.fn.pie_chart( {
 *		r:				100,
 *		labelPlacement: 'smart',
 *		data: [
 *			[ float, label, color ]+
 *		]
 *  } );
 *
 * NOTE: the current API allows for empty spaces, different radii, and overlap,
 * whereas this latter change will always render pie-charts in a full circle.
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
!function($) {

	var mousepos = [-1,-1];

	$.fn.pie_chart = function(action_or_data, options) {
		console.log("!!!! pie_chart function",arguments, "(type: ", typeof action_or_data, ")");
	    return this.each(function() {

			if ( typeof(action_or_data) == 'string' )
				return $.fn.pie_chart[action_or_data].call( $(this), options );

			if ( typeof(action_or_data) != 'object' )
			{
				console.log("pie_chart: unknown type for first argument:", action_or_data);
				return;
			}

			var canvas = this;//document.getElementById('canvas');

			canvas.ctx		= canvas.getContext('2d');
			canvas.h		= canvas.height;
			canvas.w		= canvas.width;
			canvas.data		= action_or_data.data;
			canvas.colors	= action_or_data.colors;

			console.log("INITIALIZING PIE CHART", canvas, "size:", canvas.width, 'x', canvas.height );

			draw( canvas );

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
		} );
	};


	function draw( canvas )
	{
	//	canvas.ctx.clearRect(0,0,w,h);
		for ( var i in canvas.data )
		{
			render_angle( canvas,
				canvas.data[i].r,
				canvas.data[i].startAngle,
				canvas.data[i].endAngle,
				{
					colorIndex: canvas.data[i].colorIndex || i,
					label: canvas.data[i].label,
					label_: canvas.data[i].label_,
					labelColor: canvas.data[i].labelColor,
					shiftx: canvas.data[i].shiftx,
					shifty: canvas.data[i].shifty,
					color: canvas.data[i].color
				}
			);
		}
	}


	/**
	 * @param options = { colorIndex:0..colors.length, labelColor:#f00, label:string }
	 */
	function render_angle( canvas, r, startAngle, endAngle, options )
	{
		//console.log("render_angle", 'color=',options.color, 'label_=', options.label_, "args:", arguments );

		var ctx = canvas.ctx    = canvas.getContext('2d');
		ctx.fillStyle= options.color || '#000';
		ctx.strokeStyle= options.color || '#000';
		ctx.lineWidth=1;
		ctx.fillStyle='#0f0';
		ctx.strokeStyle="#000";

		var w = canvas.width;
		var h = canvas.height;


		ctx.strokeStyle='#000';
		ctx.fillStyle = options.color || canvas.colors[ options.colorIndex ];	// required
		ctx.save();
		if ( options.colorIndex == -1 && ! options.color )
		{
			var grad = ctx.createLinearGradient( 0, 0, w, h );
			for ( i = 0; i < r; i ++ )
				grad.addColorStop( 1.0 * i / r, i&1 ? "black" : "white" );
			ctx.fillStyle = grad;
		}
		ctx.beginPath();
		ctx.moveTo( w/2, h/2 ); // redundant?
		ctx.arc( w/2, h/2, r, startAngle, endAngle );
		ctx.closePath();
		ctx.stroke();
		ctx.fill();
		ctx.restore();

		var avgangle = startAngle + ( endAngle-startAngle )/2;
		var degAngle = avgangle * 180 / Math.PI;
		ctx.save();
		ctx.fillStyle=options.labelColor || '#f00';
		ctx.fillText(
			options.label,
			( tx = w/2 + 1.2*( ax = Math.cos( avgangle ) * r ) )
			-
			( degAngle <  90 || degAngle > 270
				? 0
				: ( options.label.length * 3
				  * (degAngle > 90 && degAngle < 270 ? 1:-1)
					)
			)
			,

			( ty = h/2 + 1.2*( ay = Math.sin( avgangle ) * r ) )
			+
			( degAngle < 180 || degAngle > 270 ? 4 : 0 )
		);

		ctx.strokeStyle = '#00f';
		ctx.moveTo( w/2 + ax , h/2 + ay );
		ctx.lineTo( tx, ty );
		ctx.stroke();
		ctx.restore();
	}

}(jQuery);
