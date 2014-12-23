/**
 * Simple Plot library.
 *
 * usage:
 *
 *   var allplotdata = { KEY: [ [string,float] ] };
 *
 * At current this library expects the string to be a date (x-axis),
 * and supports multiple KEYs (currencies).
 *
 * @author kenney@neonics.com
 */
!function($) {

	var Plot = function(element,options) {
		this.options = options;
		console.log("Plot constructor");
	};

	$.fn.plot = function(options) {
		console.log("plot function", options, typeof options.selComp);
	    return this.each(function() {
			var $this = $(this);
			var currency = options.currency;	// dataset key
			var selComp = $(options.selComp)[0];	// mouseover element to select different dataset
				// the $(..)[0] hopefully converts jQuery results and elements alike
	          //, data = $this.data('component')
	          //, options = typeof option == 'object' && option

			  	if ( selComp )
				{
					console.log("display currency", currency, selComp, selComp.parentElement);
					for ( var child =0; child < selComp.parentElement.children.length; child++ )
						selComp.parentElement.children[child].style.backgroundColor='transparent';
					selComp.style.backgroundColor='red';
				}

				draw(currency, options );
		} );
	};

	$.fn.plot.Constructor = Plot;

	var canvas = document.getElementById('canvas');
	canvas.style.position = 'relative'; // for offset
	canvas.height = 400; // XXX parameterizable
	canvas.width = Math.round( canvas.parentNode.clientWidth * 0.8 );
	var ctx = canvas.getContext('2d');
	var h = canvas.height;
	var w = canvas.width;

	var margin = 20;
	var offs = margin/2; // x offset

	allplotdata.stats = [];

	var global_extremes = allplotdata.global_extremes == undefined ? true : allplotdata.global_extremes;

	// normalize plotdata
	for ( var c in allplotdata )
	{
		if ( c == 'stats' ) continue;
		var min = 1<<30;
		var max = 0;
		for ( var i in allplotdata[c] )
		{
			min = Math.min( min, allplotdata[c][i][1] );
			max = Math.max( max, allplotdata[c][i][1] );

			allplotdata[c][i][2] = allplotdata[c][i][1]; // backup orig
		}
		console.log( "extremes for ", c, ": min=", min, " max=", max );
		if ( ! global_extremes )
			for ( var i in allplotdata[c] )
				allplotdata[c][i][1] = min + ( allplotdata[c][i][1] - min ) / (max-min) * h;

		allplotdata.stats[c] = { min:min, max:max };
	}

	var globmin=1000000, globmax=-1000000;
	if ( global_extremes )
	{
		for ( var c in allplotdata )
		{
			if ( c == 'stats' ) continue;
			console.log("min/max for ", c );
			globmin = Math.min( allplotdata.stats[c].min, globmin );
			globmax = Math.max( allplotdata.stats[c].max, globmax );
		}
		for ( var c in allplotdata )
			for ( var i in allplotdata[c] )
				allplotdata[c][i][1] = /*globmin +*/ ( allplotdata[c][i][1] - globmin ) / (globmax-globmin) * h;

		// normalize globmin/max:
		console.log("global min: ", globmin, "max: ", globmax );
		globmin *= h/(globmax - globmin);
		console.log("global min: ", globmin, "max: ", globmax );
	}

	var current_currency = null;
	var current_options  = {};

	var mousepos = [-1,-1];
	canvas.onmousemove = function(e) {
		pos = e.target.getBoundingClientRect();
		mousepos[0] = e.clientX - pos.left;
		mousepos[1] = e.clientY - pos.top;
		draw( current_currency );
	};

	function draw( currency, options )
	{
		//options = options || current_options || {};
		options = options || {};
		current_currency = currency;
		current_options  = options;
		var plotdata = allplotdata[currency];
		console.log("draw(",currency,")");
		if (plotdata === undefined )
			return;
		if ( options.clear != false )
		ctx.clearRect(0,0,w,h);

		ctx.fillStyle= options.color || '#000';
		ctx.strokeStyle= options.color || '#000';
		ctx.lineWidth=1;

		ctx.fillText(currency, 0, 10 + options.title_y );
		ctx.fillText(currency, w-50, h-10);

		var hscale = (w-offs) / plotdata.length;
		ctx.beginPath(); // resets - don't close!
		ctx.moveTo(offs,  h-plotdata[0][1]);

		for ( var i in plotdata )
			ctx.lineTo( offs + hscale*i, h-plotdata[i][1] );
		ctx.stroke();

		var stats = allplotdata.stats[currency];

		// mouse-y currency value, left
		ctx.fillStyle='rgba(255,0,255,0.9)';
		ctx.fillText( 
			(stats.min + (stats.max - stats.min) * ( h - mousepos[1] ) / h) ,
			0, mousepos[1] +  ( mousepos[1]+10>h ? -2 : +10 )
		);


		var p = Math.round(( mousepos[0] - offs )/hscale);
		if ( p >=0 && p < plotdata.length )
		{
			ctx.fillText( plotdata[p][0], mousepos[0] + ( mousepos[0]+60>w?-60:0), 10); // date

			// mark graph and show exact value
			ctx.beginPath();
			ctx.arc( mousepos[0], h-plotdata[p][1], 5, 0, Math.PI * 2 );
			ctx.strokeStyle='#f00';
			ctx.stroke();
			ctx.fillStyle='#f00';
			ctx.fillText( plotdata[p][2], mousepos[0]+(mousepos[0]+40>w?-50:+10), h-plotdata[p][1]-5 );

			// mark the date-region

			ctx.fillStyle="rgba(0,255,0,0.3)";
			ctx.fillRect( offs + (p-0.5)*hscale, 0, hscale, h );
		}
			

		// draw mouse cross
		ctx.strokeStyle='rgba(255,0,255,0.9)';
		ctx.lineWidth=0.5;

		ctx.beginPath();
		ctx.moveTo( mousepos[0], 0 );
		ctx.lineTo( mousepos[0], h );
		ctx.stroke();

		ctx.beginPath();
		ctx.moveTo( 0, mousepos[1] );
		ctx.lineTo( w, mousepos[1] );
		ctx.stroke();

		// draw zero y 
		if ( global_extremes && globmin < 0 )
		{
			ctx.strokeStyle ='rgba(128,128,128,0.5)';
			ctx.lieWidth = 0.5;
			ctx.beginPath();
			ctx.moveTo( 0, h+globmin );
			ctx.lineTo( w, h+globmin );
			ctx.stroke();
		}

	};
}($);
