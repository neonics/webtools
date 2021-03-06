// http://rafael.adm.br/css_browser_selector/
function css_browser_selector(u){var ua=u.toLowerCase(),is=function(t){return ua.indexOf(t)>-1},g='gecko',w='webkit',s='safari',o='opera',m='mobile',h=document.documentElement,b=[(!(/opera|webtv/i.test(ua))&&/msie\s(\d)/.test(ua))?('ie ie'+RegExp.$1):is('firefox/2')?g+' ff2':is('firefox/3.5')?g+' ff3 ff3_5':is('firefox/3.6')?g+' ff3 ff3_6':is('firefox/3')?g+' ff3':is('gecko/')?g:is('opera')?o+(/version\/(\d+)/.test(ua)?' '+o+RegExp.$1:(/opera(\s|\/)(\d+)/.test(ua)?' '+o+RegExp.$2:'')):is('konqueror')?'konqueror':is('blackberry')?m+' blackberry':is('android')?m+' android':is('chrome')?w+' chrome':is('iron')?w+' iron':is('applewebkit/')?w+' '+s+(/version\/(\d+)/.test(ua)?' '+s+RegExp.$1:''):is('mozilla/')?g:'',is('j2me')?m+' j2me':is('iphone')?m+' iphone':is('ipod')?m+' ipod':is('ipad')?m+' ipad':is('mac')?'mac':is('darwin')?'mac':is('webtv')?'webtv':is('win')?'win'+(is('windows nt 6.0')?' vista':''):is('freebsd')?'freebsd':(is('x11')||is('linux'))?'linux':'','js']; c = b.join(' '); h.className += ' '+c; return c;}; css_browser_selector(navigator.userAgent);


function toggle(name)
{
	if ( visible( name ) )
		hide(name);
	else
		show(name);
}

function visible(name)
{
	return document.getElementById(name).style.display != 'none';
}

function hide(name)
{
	document.getElementById(name).style.display='none';
	document.getElementById(name).style.visibility='hidden';
}

function show(name)
{
	document.getElementById(name).style.display='block';
	// some places still use visibility - to keep layout..
	document.getElementById(name).style.visibility='visible';
}


var lang;// = 'en';

function setLang(l)
{
	if ( l != null )
		lang=l;
	// todo: update links in menu
}

function hide2(name)
{
	var s = document.getElementById(name).style;
	s.visibility='hidden';
	s.position='absolute';
}

function show2(name, ap)
{
	var s = document.getElementById(name).style;
	s.visibility='visible';
	s.position='';
}

function hide3(name)
{
	var s = document.getElementById(name).style;
	s.visibility='hidden';
	s.display='none';
}

function show3(name, ap)
{
	var s = document.getElementById(name).style;
	s.visibility='visible';
	s.display='block';
}

function toggle3( name )
{
	var s = document.getElementById(name).style;
	if ( s.display == 'none' ) s.display = 'block';
	else if ( s.display == 'block' || s.display == '' ) s.display = 'none';
}


function gup( name )
{
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results == null )
		return null;
	else
		return results[1];
}

setLang( gup('l') );
