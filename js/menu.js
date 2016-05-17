/**
 * Given a DOMElement, finds the best matching descendent <a> tag
 * and adds * the 'active' CSS class to all its <li> ancestors.
 *
 * @param menu DOMElement containing menu. Typically an <ul>.
 */
function menuActive( menu )
{
	var verbose = 0,
	    striphtml = true;	// whether to strip .html from URLs; may affect matching.

	if ( verbose ) console.log('menu:',menu, 'url', document.URL );

	if ( menu == undefined ) {console.log("menu undefined");return;}
    var links = menu.getElementsByTagName( 'a' );
    var bestmatch=undefined;
    var bmdepth=10000;

	var docurl = opt_striphtml( document.URL, striphtml );

    for ( var i=0; i < links.length; i ++ )
    {
		var curItem = links.item(i);
		if ( ! curItem || ! curItem.href || ! curItem.href.length ) continue;

		var itemUrl = opt_striphtml( curItem.href, striphtml );

        var depth = 0;
        for ( var n = curItem; n.parentNode != menu; n=n.parentNode )
            depth++;

		if ( verbose > 1 )
			console.log("checking ", links.item(i).href, " (depth ", depth + ")" );

        if ( curItem.href == docurl
            // submenus may refer to root pages / have more accurate match
            // but this doesn't mean they should override
            && depth < bmdepth )
        {
			if ( verbose ) console.log(" -- match!" );
            bestmatch = links[i];
			bmdepth = depth; // unused with current menu structure
//            break;
        }
		else
		{
			var re = new RegExp("^" + (""+itemUrl).replace('?', '\\?').replace( '.html', '(\\.html)?') + '([#\\?\\$].*)?$' );
			if ( re.test( docurl ) )
			{
				if ( verbose ) console.log(' +- prefix match', re, curItem, itemUrl.length, 
					(bestmatch !== undefined ? "bestmatch" + bestmatch + (""+bestmatch).length:1000)
				); 
				if ( (""+links[i]).length > (bestmatch !== undefined ? (""+bestmatch).length:0) )
				{
					bestmatch = links.item(i);
					if ( verbose ) console.log(" |  +- bestmatch now ", bestmatch);
				}
			}
			else
				if ( verbose > 1 ) console.log('no match for', curItem, itemUrl, re, re.test(docurl) );
		}
    }

	for ( var cur = bestmatch; cur !=undefined && cur != document.documentElement; cur = cur.parentNode )
		if ( cur.nodeName == 'LI' )
			cur.classList.add( 'active' );
	
	function opt_striphtml( url, striphtml ) { return striphtml ? url.replace( '.html', '' ) : url }
}

menuActive( document.getElementById( 'main-menu' ) );
