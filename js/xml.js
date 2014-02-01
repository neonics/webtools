/** @author Kenney Westerhof <kenney@neonics.com> */

function $(id) { return document.getElementById( id ); }

function xmlRequest( url )
{
	if ( window.ActiveXObject )
	{
		//xhttp=new ActiveXObject("Microsoft.XMLHTTP");
		//xhttp.open("GET", url, false);
		// must be set after open call; makes transformNode method
		// available.
		//xhttp.responseType = 'msxml-document';

		// the below makes the xdoc.tranformNode available.

		var xdoc = new ActiveXObject("MSXML2.DOMDocument");
		xdoc.async=false;
		xdoc.load( url );
		return validateXML( xdoc, url );
	}
	else if ( window.XMLHttpRequest )
	{
		// this is also available in IE, but results in the doc not
		// having the transformNode method.
		xhttp=new XMLHttpRequest();
		xhttp.open("GET", url, false);
		xhttp.send("");
		return validateXML( xhttp.responseXML, url );
	}
	else alert("Incompatible browser");
}

function validateXML(xml, src)
{
//	<parsererror xmlns="http://www.mozilla.org/newlayout/xml/parsererror.xml"
	if ( xml != null && xml.documentElement.localName == 'parsererror' )
		alert("xml: " + (src==null?"":"(source: " + src + ")\n") + 
			serialize( xml.documentElement )
		);

	return xml;
}


function serialize( xml )
{
	return xml == null
		? null 
		: xml.xml == null
			? new XMLSerializer().serializeToString( xml )
			: xml.xml;
}


function parse( xml )
{
	if ( window.DOMParser ) // FF & IE
	{
		return new DOMParser().parseFromString( xml, "text/xml" );
	}
	else if ( window.ActiveXObject ) // IE
	{
		var xmlDoc = new ActiveXObject( // "MSXML2.DOMDocument.3.0" );//
			"Microsoft.XMLDOM" );
		xmlDoc.async=false;
		xmlDoc.loadXML( xml );
		return xmlDoc;//.documentElement;
	}
	else
		alert( "Cannot find XML Parser");
}


function transform( xml, xsl, params )
{
	try
	{
		if ( typeof(xml) == 'undefined') throw new Exception("transform: xml null");
		if ( typeof(xsl) == 'undefined') throw new Exception("transform: xsl null");

		// code for Mozilla, Firefox, Opera, etc.
		//if ( document.implementation && document.implementation.createDocument )
		if ( typeof(XSLTProcessor) != "undefined" )
		{
			// if there's some weird error uncomment this, mozilla may 
			// enter some xml error code
			xsltProcessor = new XSLTProcessor();

			// Code for chrome: xsl:import/xsl:include don't work
			// see https://code.google.com/p/chromium/issues/detail?id=8441
			// (no solution there though)
			if (window.chrome)
				xsl = xslResolveIncludes( xsl );

			xsltProcessor.importStylesheet( xsl );

			if ( params != null )
				for ( var p in params )
					xsltProcessor.setParameter( null, p, params[p] );

			return xsltProcessor.transformToDocument( xml, document );
		}

		// code for IE
		if ( typeof(xml.transformNode) != "undefined" )
		{
			// no params..
			var ret = xml.transformNode( xsl );
			// result is a string, so return doc
			return parse( ret );
		}

		// code for IE
		if (window.ActiveXObject)
		{
			// IE 9+
			var xslt = new ActiveXObject( "Msxml2.XSLTemplate" );
			var xmldoc = new ActiveXObject( "Msxml2.DOMDocument" );
			var xsldoc = new ActiveXObject( "Msxml2.FreeThreadedDOMDocument" );
			xmldoc.loadXML( xml.xml );
			xsldoc.loadXML( xsl.xml );
			xslt.stylesheet = xsldoc;

			var xslproc = xslt.createProcessor();
			xslproc.input = xmldoc;
			if ( params != null )
				for ( var p in params )
					xsltProcessor.setParameter( null, p, params[p] );
			xslproc.transform();
			return parse( xslproc.output );
		}


	}
	catch ( e )
	{
		alert("transform(): Error: " + e
			+ "\nXML: " + xml+"\nXSL: " + xsl+"\n" + e.stack
			+ "\n\n" + serialize( xml ) 
			+ "\n\n" + serialize( xsl ) );
	}
}


// Chrome hack
// remove xsl:import/xsl:include tags, fetch them, and
// insert them into the original xsl.
function xslResolveImportsORIG( xsl )
{
	var toinclude = [];
	// find any imports
	var xslns = "http://www.w3.org/1999/XSL/Transform";
	var imports = xsl.getElementsByTagNameNS( xslns, "import" ); // NodeList
	var includes = xsl.getElementsByTagNameNS( xslns, "include" ); // NodeList

	if ( imports.length > 0 || includes.length > 0 )
	{
		// remove the nodes in reverse, otherwise the nodelist will become invalid
		for ( var i = imports.length -1; i >= 0; i-- )
		{
			toinclude.push(
				xslResolveImports( 
					imports.item(i).parentNode.removeChild( imports.item(i) )
				)
			);
			// 
		}

		for ( var i = includes.length -1; i >= 0; i-- )
		{
			//alert("remove include node " + includes.item(i));

			var d = xmlRequest( xsl.documentURI + "/../"
						+ includes.item(i).parentNode.removeChild( includes.item(i) )
						.getAttribute('href')
					);

			var rec = xslResolveImports( d );
			for ( var r in rec )
				xslResolveImports( r );
			toinclude.push( d );
		}

		alert("ok: " + toinclude);//serialize( xsl ));
	}
	return toinclude;
}

// Chrome hack
// remove xsl:import/xsl:include tags, fetch them, and
// insert them into the original xsl.
function xslResolveIncludes( xsl )
{
	var xslns = "http://www.w3.org/1999/XSL/Transform";
	var includes = xsl.getElementsByTagNameNS( xslns, "include" ); // NodeList

	for ( var i = includes.length -1; i >= 0; i-- )
	{
		var n = includes.item(i);

		var d = xmlRequest(
			xsl.documentURI + "/../" + n.getAttribute('href')
		);

		replaceNode( n, d.documentElement.childNodes );
	}
	return xsl;
}

function getElementById( xml, id )
{
	return xml.evaluate( "//*[@id='"+id+"']",
		xml,
		null,
		//function() {'http://www.w3.org/XML/1998/namespace';},
		XPathResult.ORDERED_NODE_ITERATOR_TYPE,
		//XPathResult.FIRST_ORDERED_NODE_TYPE,
		null)
		//.singleNodeValue;
		.iterateNext();
}

function replaceNode( orig, replacement )
{
	var tmp;
	//if (typeof replacement == "
	if ( replacement instanceof NodeList)
	{
		var frag = document.createDocumentFragment();
		for ( var i = 0; i < replacement.length; i ++ )
			frag.appendChild( document.importNode( replacement.item(i), true ) );
		replacement = frag;
	}
	orig.parentNode.replaceChild( tmp=document.importNode( replacement, true ), orig );
	return tmp;
}

function appendNode( par, child )
{
	var tmp;
	par.appendChild( tmp=document.importNode( par, true ), par );
	return tmp;

}

function insertAfter(referenceNode, newNode) {
    return referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}
