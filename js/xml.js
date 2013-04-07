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
		return validate( xdoc, url );
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

function validateXML(xml, src = null)
{
//	<parsererror xmlns="http://www.mozilla.org/newlayout/xml/parsererror.xml"
	if ( xml.documentElement.localName == 'parsererror' )
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


function transform( xml, xsl )
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
			xsltProcessor.importStylesheet( xsl );
			//return xsltProcessor.transformToFragment( xml, document );
			return xsltProcessor.transformToDocument( xml, document );
		}

		// code for IE
		if ( typeof(xml.transformNode) != "undefined" )
		{
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
			xslproc.transform();
			return parse( xslproc.output );
		}


	}
	catch ( e )
	{
		alert("transform(): Error: " + e + "\n" + e.stack );
	}
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

