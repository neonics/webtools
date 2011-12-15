var _startX = 0; // mouse starting positions
var _startY = 0;
var _offsetX = 0; // current element offset
var _offsetY = 0; 
var _dragElement; // needs to be passed from OnMouseDown to OnMouseMove 
var _oldZIndex = 0; // we temporarily increase the z-index during drag 
var _debug = $('debug'); // makes life easier

// this is simply a shortcut for the eyes and fingers
function $(id) { return document.getElementById(id); }

InitDragDrop(); function InitDragDrop() {
	 document.onmousedown = OnMouseDown;
	 document.onmouseup = OnMouseUp;
}

function dragDebug( msg )
{
	//return;
	if ( !_debug ) _debug = $('debug');
	if ( _debug ) _debug.innerHTML = msg;
	//alert("DEBUG: " + msg );
}

function hasClass( target, cls )
{
	cssClasses = target.className.split(' ');
	return cssClasses.indexOf( cls ) >= 0;
}

function OnMouseDown(e) {
	// IE is retarded and doesn't pass the event object
	if (e == null) e = window.event;
	// IE uses srcElement, others use target
	var target = e.target != null ? e.target : e.srcElement;

	var path = target;

	drag = hasClass( target, 'drag' );
	while ( ! drag && target.parentNode
		&& ! target.parentNode.nodeName == 'textarea'
		&& ! target.parentNode.nodeName == 'input'
	)
	{
		target = target.parentNode;
		path += target + " . " + path;
		drag = hasClass( target, 'drag' );
	}

	dragDebug( ( drag ? 'draggable element clicked' : 'NON-draggable element clicked' ) + ": " + target  + " (" + path + " )");

	// for IE, left click == 1
	// for Firefox, left click == 0
	if ((e.button == 1 && window.event != null || e.button == 0) && drag )
	{
		// grab the mouse position
		_startX = e.clientX; _startY = e.clientY;
		// grab the clicked element's position

		if ( target.style.left )
		{
			_offsetX = ExtractNumber(target.style.left);
			_offsetY = ExtractNumber(target.style.top);
		}
		else if ( hasClass( target, 'left' ) )
		{
			_offsetX = 0;
			_offsetY = 0;
		}
		else
		{
			_offsetX = target.offsetLeft;
			_offsetY = target.offsetTop;
		}
/*
		if ( target.style.left )
		{
			_offsetX = target.offsetLeft + ExtractNumber(target.style.left);
			_offsetY = target.offsetTop + ExtractNumber(target.style.top);
		}
*/

		// bring the clicked element to the front while it is being dragged
		_oldZIndex = target.style.zIndex; target.style.zIndex = 10000;
		// we need to access the element in OnMouseMove
		_dragElement = target;
		// tell our code to start moving the element with the mouse
		document.onmousemove = OnMouseMove;
		// cancel out any text selections
		document.body.focus();
		// prevent text selection in IE
		document.onselectstart = function () { return false; };
		// prevent IE from trying to drag an image
		target.ondragstart = function() { return false; };
		// prevent text selection (except IE)
		return false;
	}
}

function OnMouseMove(e) {
 if (e == null) var e = window.event;
 // this is the actual "drag code" 
_dragElement.style.left = (_offsetX + e.clientX - _startX) + 'px';
_dragElement.style.top = (_offsetY + e.clientY - _startY) + 'px';
//dragDebug( '(' + _dragElement.style.left + ', ' + _dragElement.style.top + ')' );
 }


function OnMouseUp(e) {
 if (_dragElement != null)
 {
	 _dragElement.style.zIndex = _oldZIndex;
	 // we're done with these events until the next OnMouseDown
	 document.onmousemove = null;
	 document.onselectstart = null;
	 _dragElement.ondragstart = null;
	 // this is how we know we're not dragging
	 _dragElement = null;
	 dragDebug( 'mouse up' );
 }
}


function ExtractNumber(value) {
 var n = value.indexOf('px') > 0
 ? parseInt( value.substring(0, value.indexOf('px') ) )
 : parseInt(value);

 return n == null || isNaN(n) ? 0 : n;
}

