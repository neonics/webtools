<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fn="http://www.w3.org/2005/xpath-functions"

	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:pst="http://neonics.com/2011/psp/template"
	xmlns:p="http://neonics.com/2013/psp/products"

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

	<xsl:template match="admin:site">
		<script type="text/javascript" src="{$psp:requestBaseURI}js/xml.js"/>
		<script type="text/javascript">
			var requestBaseURI = "<xsl:value-of select="$psp:requestBaseURI"/>";
		</script>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="admin:site-menu">
		<form id="uploadForm" method="post" enctype="application/x-www-form-urlencoded">
			<input type="hidden" name="action:admin:save"/>
			<textarea style="display:none" id="data" cols="40" name="admin:content"/>
		</form>
		<ul class="toolbar">
			<li><a href="javascript:savemenu()">Save</a></li>
		</ul>
		<div id="edit-menu"/>

		<script type="text/javascript">
			<xsl:text disable-output-escaping="yes">
			{
				var menuel = $( 'edit-menu' );
				var menudoc = xmlRequest( requestBaseURI + "content/menu.xml"  
					+ "?cachebreaker=" + Math.random() );
				// bidirectional transformation
				var xsl_editmenu =  xmlRequest( requestBaseURI + "js/edit/menu.xsl" );

				menuel.appendChild( transform( menudoc, xsl_editmenu ).documentElement );

				function savemenu()
				{
					// update dom value attribute with js value property
					var inputs = menuel.getElementsByTagName( 'input' );
					for ( var i = 0; i &lt; inputs.length; i ++ )
					{
						var v = inputs.item( i );
						v.setAttribute( 'value', v.value );
					}

					var doc = transform( menuel.childNodes[0], xsl_editmenu );

					document.getElementById( 'data' ).value = serialize( doc );
					document.getElementById( 'uploadForm' ).submit();
				}
			}
			</xsl:text>
		</script>

		<!--
		<xsl:copy>
			<xsl:attribute name="id">edit-menu</xsl:attribute>
			<xsl:copy-of select="php:function('admin_site_menu')"/>
		</xsl:copy>
		-->
	</xsl:template>


	<!-- directory structure, files -->
	<xsl:template match="admin:site-pages">
		<xsl:if test="@advanced">
			<span>
			[
				<a href="javascript:toggle3('site-pages-advanced');toggle3('site-pages-basic');">
				Toggle Advanced</a>
			]
			</span>
		</xsl:if>

		DIR: <l:link slashpath="site/pages">content</l:link>/<xsl:if test="@dir!='false'">
			<xsl:value-of select="@dir"/>
		</xsl:if>

		<div id="site-pages-basic">
			<xsl:call-template name="admin:site-pages"/>
		</div>

		<xsl:if test="@advanced">
			<div id="site-pages-advanced" style="display:none">
				<xsl:call-template name="admin:site-pages-advanced"/>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- basic -->
	<xsl:template name="admin:site-pages">
		<table class="dir">
			<tr>
				<th>name</th>
				<th>versions</th>
			</tr>

			<xsl:choose>
				<xsl:when test="@dir != 'false'">
					<xsl:apply-templates select="php:function('admin_site_pages', string(@dir))" mode="site-pages"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="php:function('admin_site_pages')" mode="site-pages"/>
				</xsl:otherwise>
			</xsl:choose>

		</table>
	</xsl:template>
	
	<xsl:template match="dir" mode="site-pages">
		<tr>
			<td><l:link slashpath="site/pages/{@name}"><xsl:value-of select="@name"/>/</l:link></td>
		</tr>
	</xsl:template>

	<xsl:template match="file" mode="site-pages">
		<tr>
			<td><l:link slashpath="site/page/{@path}"><xsl:value-of select="@name-ext"/></l:link></td>
			<xsl:apply-templates select="alt" mode="site-pages"/>
		</tr>
	</xsl:template>

	<!-- should not match -->
	<xsl:template match="alt[@base='request']" mode="site-pages">
		<td style="border-left: 1px solid black">
			<xsl:value-of select="@where"/>(?)
		</td>
	</xsl:template>

	<xsl:template match="alt[@base='request'][@where='db']" mode="site-pages">
		<td style="border-left: 1px solid black">
			<xsl:value-of select="@where"/>
			<l:link style="color: red" action="admin:delete"
				title="When a page is broken, use this to delete the copy in db. Note that all changes from the original will be lost!">
				<l:arg name="admin:dbfile" value="{@name}"/>
				[X]
			</l:link>
		</td>
	</xsl:template>

	<xsl:template match="alt[@base='request'][not(@where)]" mode="site-pages">
		<td style="border-left: 1px solid black">original</td>
	</xsl:template>

	<xsl:template match="alt[@base='core']" mode="site-pages"/>


	<!-- advanced -->
	<xsl:template name="admin:site-pages-advanced">
		<table border="1" class="dir">
			<tr style="background-color:#eee">
				<th rowspan="2">F/D</th>
				<th rowspan="2">name</th>

				<th>name</th>
				<th>type</th>
				<th>base</th>
				<th>where</th>
				<th>baserelpath</th>
			</tr>
			<tr style="background-color:#eee">
				<th colspan="5"> fullpath </th>
			</tr>

			<xsl:choose>
				<xsl:when test="@dir != 'false'">
					<xsl:apply-templates select="php:function('admin_site_pages', string(@dir))" mode="site-pages-advanced"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="php:function('admin_site_pages')" mode="site-pages-advanced"/>
				</xsl:otherwise>
			</xsl:choose>
		</table>
	</xsl:template>

	<xsl:template match="dir" mode="site-pages-advanced">
		<tr>
			<td>DIR</td>
			<td><l:link slashpath="site/pages/{@name}"><xsl:value-of select="@name"/>/</l:link></td>
		</tr>
	</xsl:template>

	<xsl:template match="file" mode="site-pages-advanced">
		<xsl:variable name="rowspan" select="2*count(alt)"/>
		<tr>
			<td rowspan="{$rowspan+1}">FILE</td>
			<td rowspan="{$rowspan+1}"><l:link slashpath="site/page/{@name}"><xsl:value-of select="@name-ext"/></l:link></td>
		</tr>
		<xsl:apply-templates select="alt" mode="site-pages-advanced"/>
	</xsl:template>

	<xsl:template match="alt" mode="site-pages-advanced">
		<tr class="base-{@base}">
		<td><xsl:value-of select="@name"/></td>
		<td><xsl:value-of select="@type"/></td>
		<td><xsl:value-of select="@base"/></td>
		<td><xsl:value-of select="@where"/></td>
		<td><xsl:value-of select="@baserelpath"/></td>
		<xsl:if test="@where='db'">
		<td>revert</td>
		</xsl:if>
		<xsl:if test="@base='request' and ../alt[@base='core']">
		<td>=override</td>
		</xsl:if>
		</tr>
		<tr class="base-{@base} path">
		<td colspan="5"><xsl:value-of select="@fullpath"/></td>
		</tr>
	</xsl:template>

	<!-- end advanced -->

<!--
	<xsl:template match="admin:site-pages">
		<l:menu class="site-pages">
			<xsl:for-each select="php:function('admin_site_pages')">
				<xsl:apply-templates select="*" mode="site-pages"/>
			</xsl:for-each>
		</l:menu>
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
		<l:item slashpath="site/page/{$pu}{@name}">FILE <xsl:value-of select="@name-ext"/>
			<xsl:for-each select="alt">
			[ALT: <xsl:value-of select="@loc"/>]
			</xsl:for-each>
		</l:item>
	</xsl:template>
-->

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
			li#edit_langsel a { color: white; }
		</style>

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
			var pageurl = requestBaseURI + "content/<xsl:value-of select="@page"/>.xml";
			<xsl:text disable-output-escaping="yes">

			var doc;
			var editel;	// element in doc that is edited
			
			function geteditdoc(l) {
				if ( l == null ) l = 'en';

				doc = xmlRequest( pageurl  + "?cachebreaker=" + Math.random() );
				if ( doc == null ) alert( "Failed to load document '" + pageurl + "'" );
				else
				{
					var xsl = xmlRequest( requestBaseURI + "js/editpage.xsl" );

					// other views
					var treetx = transform( doc, xsl );//.documentElement;
					document.getElementById('tree').innerHTML =  serialize( treetx );
					document.getElementById('editpage').innerHTML =  serialize( doc );


					// the rich text editor
					var e = document.getElementById('preview');
					//if ( document.all ) e.innerHTML = serialize(doc);//doc.innerText;
					//else { oc = document.createRange(); oc.selectNodeContents( doc.firstChild ); e.innerHTML = oc.toString(); }


					// determine which element of doc to edit

					var ce = doc.getElementsByTagName(//NS("http://neonics.com/2011/psp/template",
								'pst:content'
							).item(0);

					if ( ce != null )
					{
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
					}
					else
					{
						// try product
						var contents = doc.getElementsByTagName( 'p:content' );
						if ( contents != null )
							for ( var i = 0; i &lt; contents.length; i++ )
							{
								if ( contents.item(i).getAttribute('xml:lang') == l )
								{
									ce = contents.item(i);
									show2('edit_langsel');
									break;
								}
						}
					}

					// ce may be null

					editel=ce;

					//e.innerHTML = serialize( ce );
					//no innerhtml as it screws with script tags getting nested

					e.innerHTML = null; 
					e.appendChild( ce.cloneNode(true) );

					// enable editor
					e.contentEditable = true;
					e.focus();
				}
			}


			function savepage()
			{
				var cn = editel;

				var newcn = document.getElementById( 'preview' ).childNodes[0];
				cn.parentNode.replaceChild( newcn, cn ); // update orig doc

//				alert("Submitting: \n"+serialize(doc));

				document.getElementById( 'data' ).value = serialize( doc );

				document.getElementById( 'uploadForm' ).submit();
			}


			geteditdoc('en');

			</xsl:text>
		</script>
		<!--a href="javascript:geteditdoc()">Start Editing</a-->
	</xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
