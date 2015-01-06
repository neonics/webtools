/** Simple Color library */

/**
 * @param h : hue		 [0..360]
 * @param s : saturation [0..1]
 * @param v : value		 [0..1]
 */
function hsv2rgb(h,s,v)
{
	var m = ( h / 60.0 ) % 6,
		i = Math.floor(m),
		f = m - i,
		p = v * (1 - s),
		q = v * (1 - s * f),
		t = v * (1 - s * (1 - f)),
		r = [v, q, p, p, t, v][i],
		g = [t, v, v, q, p, p][i],
		b = [p, p, t, v, v, q][i];

	return [
		Math.round( r * 255 ),
		Math.round( g * 255 ),
		Math.round( b * 255 ),
		1
	];
}

function rgb2hex(rgb)
{
	var hex = [0,0,0];

	for ( var i = 0; i < 3; i ++ )
	{
		var tmp = rgb[i].toString(16);
		hex[i] = tmp.length == 1 ? '0' + tmp : tmp;
	}
	return '#' + hex.join("");
}
