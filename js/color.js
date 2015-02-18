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
		var tmp = Math.min( 255, Math.max( 0, rgb[i] ) ).toString(16);
		hex[i] = tmp.length == 1 ? '0' + tmp : tmp;
	}
	return '#' + hex.join("");
}


// http://www.easyrgb.com/index.php?X=MATH&H=01#text1
function xyz2rgb(xyz){

var X = xyz[0]/100;  //X from 0 to  95.047      (Observer = 2°, Illuminant = D65)
var Y = xyz[1]/100;  //Y from 0 to 100.000
var Z = xyz[2]/100;  //Z from 0 to 108.883

var R = X *  3.2406 + Y * -1.5372 + Z * -0.4986
var G = X * -0.9689 + Y *  1.8758 + Z *  0.0415
var B = X *  0.0557 + Y * -0.2040 + Z *  1.0570

if ( R > 0.0031308 ) R = 1.055 * Math.pow( R, 1 / 2.4 ) - 0.055
else                 R = 12.92 * R
if ( G > 0.0031308 ) G = 1.055 * Math.pow( G, 1 / 2.4 ) - 0.055
else                 G = 12.92 * G
if ( B > 0.0031308 ) B = 1.055 * Math.pow( B, 1 / 2.4 ) - 0.055
else                 B = 12.92 * B

	return [
		Math.round( R * 255 ),
		Math.round( G * 255 ),
		Math.round( B * 255 ),
		1
	];
}

// http://www.easyrgb.com/index.php?X=MATH&H=02#text2
function rgb2xyz(rgb)
{
	var R = rgb[0];
	var G = rgb[1];
	var B = rgb[2];

	R = ( R / 255 )        //R from 0 to 255
	G = ( G / 255 )        //G from 0 to 255
	B = ( B / 255 )        //B from 0 to 255

	if ( R > 0.04045 ) R = Math.pow( ( ( R + 0.055 ) / 1.055 ), 2.4 )
	else               R = R / 12.92
	if ( G > 0.04045 ) G = Math.pow( ( ( G + 0.055 ) / 1.055 ), 2.4 )
	else               G = G / 12.92
	if ( B > 0.04045 ) B = Math.pow( ( ( B + 0.055 ) / 1.055 ), 2.4 )
	else               B = B / 12.92

	R = R * 100
	G = G * 100
	B = B * 100

	//Observer. = 2°, Illuminant = D65
	return [
		X = R * 0.4124 + G * 0.3576 + B * 0.1805,
		Y = R * 0.2126 + G * 0.7152 + B * 0.0722,
		Z = R * 0.0193 + G * 0.1192 + B * 0.9505,
		1
	]
}

// http://www.easyrgb.com/index.php?X=MATH&H=17#text17
function cieluv2xyz(cieluv)
{
	var cie_L = cieluv[0];	// 0..100
	var cie_u = cieluv[1];
	var cie_v = cieluv[2];


	var Y = ( cie_L + 16 ) / 116
	var X;
	var Z;

//console.log( cieluv, cie_L, cie_u, cie_v, Y );

	var y3 = Math.pow( Y, 3 );
	if ( y3 > 0.008856 ) Y = y3
	else                 Y = ( Y - 16 / 116 ) / 7.787

	var ref_X =  95.047      //Observer= 2°, Illuminant= D65
	var ref_Y = 100.000
	var ref_Z = 108.883

	var ref_U = ( 4 * ref_X ) / ( ref_X + ( 15 * ref_Y ) + ( 3 * ref_Z ) )
	var ref_V = ( 9 * ref_Y ) / ( ref_X + ( 15 * ref_Y ) + ( 3 * ref_Z ) )

	//console.log( "ref_Y", ref_Y, "ref_Z", ref_Z," ==> ref_U", ref_U, "ref_V", ref_V );

	var U = cie_u / ( 13 * cie_L ) + ref_U; //console.log( cie_u, "/ (13 *", cie_L, ") + ", ref_U, " => ", U );
	var V = cie_v / ( 13 * cie_L ) + ref_V

	//console.log( " == ", Y,"--", "cie_L", cie_L, "cie_u", cie_u,"cie_v", cie_v, "U",  U, "V", V );

	return [
		Y = Y * 100,
		X = - ( 9 * Y * U ) / ( ( U - 4 ) * V  - U * V ),
		Z =   ( 9 * Y - ( 15 * V * Y ) - ( V * X ) ) / ( 3 * V ),
		1
	]


// http://en.wikipedia.org/wiki/CIELUV says:
// u'= u / (13*L) + u'n		// u'n, v'n are u,v chromaticity coords of white point
// v'= v / (13*L) + v'n
// Y = L <= 8
//   ? Yn * L * (3/29)^3
//   : Yn * ( (L+16)/116 )^3
// X = Y * 9u' / 4v'
// Z = Y * ( 12 - 3u' - 20v' ) / 4v'

}

// http://www.easyrgb.com/index.php?X=MATH&H=16#text16
function xyz2cieluv(xyz)
{
	var X = xyz[0];
	var Y = xyz[1];
	var Z = xyz[2];

	var U = ( 4 * X ) / ( X + ( 15 * Y ) + ( 3 * Z ) )
	var V = ( 9 * Y ) / ( X + ( 15 * Y ) + ( 3 * Z ) )

	Y = Y / 100
	if ( Y > 0.008856 ) Y = Math.pow( Y, ( 1/3 ) )
	else                    Y = ( 7.787 * Y ) + ( 16 / 116 )

	var ref_X =  95.047        //Observer= 2°, Illuminant= D65
	var ref_Y = 100.000
	var ref_Z = 108.883

	var ref_U = ( 4 * ref_X ) / ( ref_X + ( 15 * ref_Y ) + ( 3 * ref_Z ) )
	var ref_V = ( 9 * ref_Y ) / ( ref_X + ( 15 * ref_Y ) + ( 3 * ref_Z ) )

	return [
		cie_L = ( 116 * Y ) - 16,
		cie_u = 13 * cie_L * ( U - ref_U ),
		cie_v = 13 * cie_L * ( V - ref_V ),
		1
	]
}

// http://www.easyrgb.com/index.php?X=MATH&H=19#text19
function hsl2rgb(H,S,L)
{
	if ( S == 0 )                       //HSL from 0 to 1
	{
		R = L * 255                      //RGB results from 0 to 255
		G = L * 255
		B = L * 255
	}
	else
	{
		if ( L < 0.5 ) var_2 = L * ( 1 + S )
		else           var_2 = ( L + S ) - ( S * L )

		var_1 = 2 * L - var_2

		R = 255 * _hue2rgb( var_1, var_2, H + ( 1 / 3 ) )
		G = 255 * _hue2rgb( var_1, var_2, H )
		B = 255 * _hue2rgb( var_1, var_2, H - ( 1 / 3 ) )
	}

	function _hue2rgb( v1, v2, vH )             //Function Hue_2_RGB
	{
		if ( vH < 0 ) vH += 1
		if ( vH > 1 ) vH -= 1
		if ( ( 6 * vH ) < 1 ) return ( v1 + ( v2 - v1 ) * 6 * vH )
		if ( ( 2 * vH ) < 1 ) return ( v2 )
		if ( ( 3 * vH ) < 2 ) return ( v1 + ( v2 - v1 ) * ( ( 2 / 3 ) - vH ) * 6 )
		return ( v1 )
	}
}

// http://www.easyrgb.com/index.php?X=MATH&H=18#text18
function rgb2hsl(R,G,B)
{
	var_R = ( R / 255 )                     //RGB from 0 to 255
	var_G = ( G / 255 )
	var_B = ( B / 255 )

	var_Min = min( var_R, var_G, var_B )    //Min. value of RGB
	var_Max = max( var_R, var_G, var_B )    //Max. value of RGB
	del_Max = var_Max - var_Min             //Delta RGB value

	L = ( var_Max + var_Min ) / 2

	if ( del_Max == 0 )                     //This is a gray, no chroma...
	{
		H = 0                                //HSL results from 0 to 1
		S = 0
	}
	else                                    //Chromatic data...
	{
		if ( L < 0.5 ) S = del_Max / ( var_Max + var_Min )
		else           S = del_Max / ( 2 - var_Max - var_Min )

		del_R = ( ( ( var_Max - var_R ) / 6 ) + ( del_Max / 2 ) ) / del_Max
		del_G = ( ( ( var_Max - var_G ) / 6 ) + ( del_Max / 2 ) ) / del_Max
		del_B = ( ( ( var_Max - var_B ) / 6 ) + ( del_Max / 2 ) ) / del_Max

		if      ( var_R == var_Max ) H = del_B - del_G
		else if ( var_G == var_Max ) H = ( 1 / 3 ) + del_R - del_B
		else if ( var_B == var_Max ) H = ( 2 / 3 ) + del_G - del_R

		if ( H < 0 ) H += 1
		if ( H > 1 ) H -= 1
	}
}

/* (tabstop:4)
XYZ (Tristimulus) Reference values of a perfect reflecting diffuser

		Observer 		   2° (CIE 1931)		   10° (CIE 1964)
	Illuminant 			  X2	Y2	   Z2		  X10	Y10	  Z10 
	A (Incandescent)	109.850 100  35.585 	111.144 100	 35.200
	C					 98.074	100 118.232		 97.285 100 116.145 
	D50					 96.422	100  82.521		 96.720 100  81.427 
	D55					 95.682	100  92.149		 95.799 100  90.926 
	D65 (Daylight)		 95.047	100 108.883		 94.811 100 107.304 
	D75					 94.972	100 122.638		 94.416 100 120.641 
	F2 (Fluorescent)	 99.187	100  67.395		103.280 100  69.026 
	F7					 95.044	100 108.755		 95.792 100 107.687 
	F11					100.966	100  64.370		103.866 100  65.627
*/

// http://www.easyrgb.com/index.php?X=MATH&H=08#text8
function cielab2xyz(cielab)
{
	var cie_L = cielab[0];
	var cie_a = cielab[1];
	var cie_b = cielab[2];

	var Y = ( cie_L + 16 ) / 116
	var X = cie_a / 500 + Y
	var Z = Y - cie_b / 200

	var X3=Math.pow(X,3);	// for efficiency
	var Y3=Math.pow(Y,3);
	var Z3=Math.pow(Z,3);

	if ( Y3 > 0.008856 ) Y = Y3
	else                 Y = ( Y - 16 / 116 ) / 7.787
	if ( X3 > 0.008856 ) X = X3
	else                 X = ( X - 16 / 116 ) / 7.787
	if ( Z3 > 0.008856 ) Z = Z3
	else                 Z = ( Z - 16 / 116 ) / 7.787

	var ref_X =  95.047     // Observer= 2°, Illuminant= D65
	var ref_Y = 100.000
	var ref_Z = 108.883

	return [
		X = ref_X * X,
		Y = ref_Y * Y,
		Z = ref_Z * Z,
		1
	]
}

// http://www.easyrgb.com/index.php?X=MATH&H=07#text7
function xyz2cielab(xyz)
{
	var X = xyz[0];
	var Y = xyz[1];
	var Z = xyz[2];

	X = X / ref_X          //ref_X =  95.047   Observer= 2°, Illuminant= D65
	Y = Y / ref_Y          //ref_Y = 100.000
	Z = Z / ref_Z          //ref_Z = 108.883

	if ( X > 0.008856 ) X = Math.pow( X, ( 1/3 ) )
	else                X = ( 7.787 * X ) + ( 16 / 116 )
	if ( Y > 0.008856 ) Y = Math.pow( Y, ( 1/3 ) )
	else                Y = ( 7.787 * Y ) + ( 16 / 116 )
	if ( Z > 0.008856 ) Z = Math.pow( Z, ( 1/3 ) )
	else                Z = ( 7.787 * Z ) + ( 16 / 116 )

	return [
		cie_L = ( 116 * Y ) - 16,
		cie_a = 500 * ( X - Y ),
		cie_b = 200 * ( Y - Z ),
		1
	]
}


function angularColor( angle, a, b )
{
}
