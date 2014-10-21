var widgets = {

	/** widgets.shade( $('.shadow') */

	shade: function( shade )
	{
		var $ = jQuery;

		var mousex, mousey;

		function updatepos(event) {
			mousex = event.clientX;//.pageX;
			mousey = event.clientY;//.pageY;
		}

		function update(event)
		{
			var sx = document.documentElement.scrollLeft || document.body.scrollLeft;
			var sy = document.documentElement.scrollTop  || document.body.scrollTop;
			//console.log('move', this, arguments, "X ", sx, mousex, "Y ", sy, mousey);

			shade.each(
			function(idx, oel) {

			el = $(oel);
						var elx = oel.offsetLeft;// + oel.scrollLeft;
						var ely = oel.offsetTop ;// + oel.scrollTop;

						var mx = mousex + sx;
						var my = mousey + sy;


			el.attr('style',
			a= 'box-shadow: '
				+ -(mx - ( elx + oel.clientWidth /2) )/10 + 'px '
				+ -(my - ( ely + oel.clientHeight/2) )/10 + 'px '
				+ '4px ' // blur
				+ '3px ' // shadow size
				+ 'rgba(0,0,0,.4);'//, 0px 0px 20px 5px rgba(0,0,200,.7);'

			// .action {
			//box-shadow: .5em .5em 4px 3px rgba(0,0,0,.4), // preserve
			//                0px 0px 20px 5px rgba(0,0,200,.7);
			//}
			//+ Math.sqrt(x*x+y*y) + 'px rgba(0,0,0,.3);'
			);
			}
			);
		}

		$(document).on('mousemove', function(e){updatepos(e);update();} );
		$(window).on('scroll', function(e){update();} );
	}
};
