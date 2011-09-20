function xmlRequest( url )
{
	if ( window.XMLHttpRequest )
	{
		xhttp=new XMLHttpRequest();
	}
	else
	{
		xhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xhttp.open("GET", url, false);
	xhttp.send("");
	ret = xhttp.responseXML;

	//alert( "Retrieved '" + url + "':\n" + ret );
	return ret;
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
	if ( window.DOMParser )
	{
		return new DOMParser().parseFromString( xml, "text/xml" );
	}
	else if ( window.ActiveXObject )
	{
		var xmlDoc = new ActiveXObject( // "MSXML2.DOMDocument.3.0" );//
			"Microsoft.XMLDOM" );
		xmlDoc.async=false;
		xmlDoc.loadXML( xml );
		return xmlDoc.documentElement;
	}
	else
	{
		alert( "Cannot find XML Parser");
	}
}


function transform( xml, xsl )
{
	try
	{
		// code for IE
		if (window.ActiveXObject)
		{
			return xml.transformNode( xsl );
		//	document.getElementById("example").innerHTML=ex;
		}
		// code for Mozilla, Firefox, Opera, etc.
		else if ( document.implementation && document.implementation.createDocument )
		{
			// if there's some weird error uncomment this, mozilla may 
			// enter some xml error code
			xsltProcessor = new XSLTProcessor();
			xsltProcessor.importStylesheet( xsl );
			//return xsltProcessor.transformToFragment( xml, document );
			return xsltProcessor.transformToDocument( xml, document );
		}
	}
	catch ( e )
	{
		alert("Error: " + e + "\n" + serialize( xsl ) );
	}
}
