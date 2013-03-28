<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fn="http://www.w3.org/2005/xpath-functions"

	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:pst="http://neonics.com/2011/psp/template"

	xmlns:php="http://php.net/xsl" 

	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:x="http://www.w3.org/1999/xhtml"

	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:xi="http://www.w3.org/2001/XInclude"

	xmlns:auth="http://neonics.com/2000/xsp/auth"
	xmlns:admin="http://neonics.com/2013/psp/admin"
	exclude-result-prefixes="admin"
>
	<xsl:param name="psp:requestURI" select="$requestURI"/>
	<xsl:param name="psp:requestBaseURI" select="$requestBaseURI"/>
	<xsl:param name="psp:requestDir" select="$requestDir"/>
	<xsl:param name="psp:requestFile" select="$requestFile"/>
	<xsl:param name="psp:requestQuery" select="$requestQuery"/>
	<xsl:param name="psp:theme" select="$theme"/>

	<xsl:template match="admin:site-overview">
		<xsl:copy>
			<xsl:copy-of select="php:function('admin_site_overview')"/>
		</xsl:copy>
	</xsl:template>

	<xsl:template match="admin:site-pages">
		<xsl:for-each select="php:function('admin_site_pages')">
			<xsl:apply-templates select="*" mode="site-pages"/>
		</xsl:for-each>
	</xsl:template>

	<xsl:template match="dir" mode="site-pages">
		<l:item slashpath="site/page/{@name}">DIR <xsl:value-of select="@name"/>/
			<l:menu>
				<xsl:apply-templates select="dir|file" mode="site-pages"/>
			</l:menu>
		</l:item>
	</xsl:template>

	<xsl:template match="file" mode="site-pages">
		<xsl:variable name="pu">
			<xsl:for-each select="ancestor::dir"><xsl:value-of select="@name"/>/</xsl:for-each>
		</xsl:variable>
		<l:item slashpath="site/page/{$pu}{@name}">FILE <xsl:value-of select="@name"/></l:item>
	</xsl:template>


	<xsl:template match="admin:site-page-edit">
		<style type="text/css">
			#preview { border: 1px dashed red; }

			.tabpanes {
				border: 1px solid black;
			}
			ul.tabs { margin: 0; padding: 0 0 0 10px;
			} 
			ul.tabs li { display: inline; cursor: pointer;
				background-color: blue;
				color: white;
				margin: 1px;
			};
			ul.toolbar li { display: inline; cursor: pointer; }

			li#edit_langsel { visibility: hidden; }
		</style>
		<script type="text/javascript" src="{$psp:requestBaseURI}js/xml.js"/>

		<ul class="toolbar">
			<li><a href="javascript:savepage()">Save</a></li>
		</ul>
		<div class="tabbox">
			<ul class="tabs">
				<li onclick="show3('preview');hide3('editpage');hide3('tree')">
					WYSIWYG
				</li>
				<li onclick="show3('editpage');hide3('preview');hide3('tree')">
					Source
				</li>
				<li onclick="show3('tree');hide3('editpage');hide3('preview')">
					Tree
				</li>

				<li id="edit_langsel">
					<a href="javascript:geteditdoc('en');">EN</a>
					|
					<a href="javascript:geteditdoc('nl');">NL</a>
				</li>
			</ul>
			<div class="tabpanes">
				<div id="preview" width="100%" contenteditable="true"/>
				<textarea id="editpage" cols="80" rows="25" style="display:none"/>
				<div id="tree" width="100%" style="display:none"/>
			</div>
		</div>

		<form id="uploadForm" method="post" enctype="application/x-www-form-urlencoded">
			<input type="hidden" name="action:admin:save"/>
			<textarea style="display:none" id="data" cols="40" name="admin:content"/>
		</form>


		<script type="text/javascript">
			var requestBaseURI = "<xsl:value-of select="$psp:requestBaseURI"/>";
			var pageurl = requestBaseURI + "content/<xsl:value-of select="@page"/>.xml";
			<xsl:text disable-output-escaping="yes">

			var doc;
			var editel;
			
			function geteditdoc(l) {
				if ( l == null ) l = 'en';

				doc = xmlRequest( pageurl  + "?cachebreaker=" + Math.random() );
				if ( doc == null ) alert( "Failed to load document '" + pageurl + "'" );
				else
				{
					var xsl = xmlRequest( requestBaseURI + "js/editpage.xsl" );

					var treetx = transform( doc, xsl );//.documentElement;
					document.getElementById('tree').innerHTML =  serialize( treetx );

					document.getElementById('editpage').innerHTML =  serialize( doc );
					var e = document.getElementById('preview');
					//if ( document.all ) e.innerHTML = serialize(doc);//doc.innerText;
					//else { oc = document.createRange(); oc.selectNodeContents( doc.firstChild ); e.innerHTML = oc.toString(); }

					var ce = doc.getElementsByTagName(//NS("http://neonics.com/2011/psp/template",
								'pst:content'
							).item(0);

					if ( ce.getElementsByTagName( 'slides' ) )
					{
						var slides = ce.getElementsByTagName( 'slide' ) 
						var si="";
						for ( var i = 0; i &lt; slides.length; i++)
						{
							if ( slides.item(i).getAttribute('xml:lang' ) == l )
							{
								ce=slides.item(i);
								show2('edit_langsel');
								break;
							}
						}
					}

					editel=ce;

					e.innerHTML = serialize( ce );

					e.contentEditable = true;
					e.focus();
				}
			}
			geteditdoc('en');

			</xsl:text>
		</script>
		<!--a href="javascript:geteditdoc()">Start Editing</a-->
		<script type="text/javascript">
			function savepage()
			{
				var cn = editel;//doc.getElementsByTagName( "pst:content" ).item(0);
				var newcn = document.getElementById( 'preview' ).childNodes[0];
				cn.parentNode.replaceChild( newcn, cn );

				document.getElementById( 'data' ).value = serialize( doc );

				document.getElementById( 'uploadForm' ).submit();
			}
		</script>
	</xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
