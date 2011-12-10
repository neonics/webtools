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
>
<!--	!!! exclude-result-prefixes="pst"-->

	<xsl:param name="psp:requestURI" select="$requestURI"
		xmlns:psp="http://neonics.com/2011/psp"
	/>
	<xsl:param name="psp:requestBaseURI" select="$requestBaseURI"
		xmlns:psp="http://neonics.com/2011/psp"
	/>
	<xsl:param name="psp:requestDir" select="$requestDir"
		xmlns:psp="http://neonics.com/2011/psp"
	/>
	<xsl:param name="psp:requestFile" select="$requestFile"
		xmlns:psp="http://neonics.com/2011/psp"
	/>
	<xsl:param name="psp:requestQuery" select="$requestQuery"
		xmlns:psp="http://neonics.com/2011/psp"
	/>

	<xsl:template match="pst:template">
		<l:page>
			<l:body>
				<l:header>
					<div class="logo">
						<img src="{$psp:requestBaseURI}img/neonics.png"/><br/>
						<span>
							Conscious Computing
						</span>
					</div>
					<l:menu class="mainmenu">
						<l:item page="index">Home</l:item>
						<l:item page="install">Install</l:item>
						<l:item page="db">Database</l:item>
						<l:item page="auth">Authentication</l:item>
						<l:item page="issues">Issues</l:item>
						<l:item page="template">Templates</l:item>
						<l:item page="wiki">Wiki</l:item>
						<l:item page="article">Articles</l:item>
					</l:menu>
				</l:header>
				<l:box align="left">
					<xsl:call-template name="auth:menu"/>
				</l:box>
				<l:messagebox/>

				<xsl:apply-templates select="pst:menu"/>

				<l:messagebox/>

				<l:box align="left">
					<l:messagebox type="debug"/>
				</l:box>

				<xsl:choose>
					<xsl:when test="pst:content">
					<xsl:apply-templates select="pst:content"/>
					</xsl:when>
					<xsl:otherwise>
						<l:content>
						</l:content>
					</xsl:otherwise>
				</xsl:choose>

				<xsl:apply-templates select="pst:edit"/>
			</l:body>

			<xsl:apply-templates select="php:function('psp_messages')"/>
		</l:page>
	</xsl:template>

	<xsl:template name="auth:menu">
		<xi:include href="authmenu.xml"/>
	</xsl:template>

	<xsl:template match="pst:body">
		<l:div style="color: red">ERROR: There is no such thing as pst:body</l:div>
	</xsl:template>

	<xsl:template match="pst:content">
		<l:content>
			<xsl:apply-templates select="@*" mode="copy"/>
			<xsl:apply-templates/>
		</l:content>
	</xsl:template>

	<xsl:template match="pst:menu">
		<l:box align="left">
			<xsl:comment> PST MENU </xsl:comment>
			<xsl:apply-templates/>
		</l:box>
	</xsl:template>


	<!-- The Edit Box -->



	<xsl:template match="pst:edit">
		<xsl:call-template name="pst-edit"/>
	</xsl:template>

	<xsl:template name="pst-edit">
		<l:edit class="drag">
			<pre id="debug" style="display: none"/>

			<div id="navtree" style="border: 1px solid green"/>
			<script type="text/javascript" src="js/xml.js"/>
			<script type="text/javascript">
				debug = document.getElementById('debug');

				contentFile = "<xsl:value-of select="concat($psp:requestDir,$psp:requestFile)"/>";

				rb = "<xsl:value-of select="$psp:requestBaseURI"/>";

				xml = xmlRequest( rb + 'content/' + contentFile );

				addidxsl = xmlRequest( rb + 'js/addid.xsl' );
				removeidxsl = xmlRequest( rb + 'js/removeid.xsl' );
				contentDoc = transform( xml, addidxsl );

				xsl = xmlRequest( rb + 'js/edit.xsl' );
				res = transform( contentDoc, xsl ).documentElement;

				document.getElementById( 'navtree' ).appendChild( res );

				function getContentNode( id ) {
					return contentDoc.getElementById( id );
				}
			</script>

			<form>
				<textarea cols="70" id="editor1" name="editor1" rows="60">
				</textarea>
			</form>

			<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="ckeditor/_samples/sample.js"></script>

			<style type="text/css">
				.fold { display: inline; width: 2em; cursor: se-resize; }
				.node:after { content ' (' attr(id) ')'; }

				.node { color: blue; font-size: 10pt;}
				dl.node dd { color: black; margin-left: 1em;}
				dl.node dt { cursor: pointer; }
				.visible { visibility: visible; display: block; }
				.hidden { visibility: hidden; display: none; }

				div#edit {
					position: absolute;
					top: 20px;
					left: 100px;
					border: 8px solid green;
					background-color: #efe;
					width: 80%;
					z-index: 100;
				}
			</style>

			<script type="text/javascript">
				<xsl:text disable-output-escaping="yes">
					function isClass( el, name )
					{
						return ( el.className.split(' ').indexOf( name ) >= 0);
					}

					function addClass( el, name )
					{
						el.className += ' '+ name;
					}

					function removeClass( el, name )
					{
						var b="";
						l = el.className.split(' ');
						for(a in l)
						{
							if ( l[a] != name )
								b += ' ' + l[a];
						}
						el.className = b;
					}


				function toggle( id )
				{
					el = document.getElementById( 'child' + id );
					f = document.getElementById( 'fold' + id ); 

					if ( isClass( el, 'hidden' ) )
					{
						removeClass( el, 'hidden' );
						addClass( el, 'visible' );
						f.innerHTML='-';
					}
					else
					{
						removeClass( el, 'visible' );
						addClass( el, 'hidden' );
						f.innerHTML='+';
					}
				}

				function isEditable( q )
				{
					editable = q.getAttribute( 'editable' );
					if ( editable &amp;&amp; editable == 'yes' )
						return true;

					if ( editables != null )
					{
						debug.innerHTML += "check " + q.localname + ":" + q.namespaceURI+"\n";
						if ( editables.indexOf( q.localName + ':' + q.namespaceURI ) >=0 )
							return true;
					}
					else
						debug.innerHTML += "WARN: no editables\n";

					return false;
				}


				function toggleedit( elId, elname )
				{
					q = getContentNode( elId );
					if ( q == null )
					{
						debug.innerHTML += 'Element ' + elname + ' id=' + elId + ' not found.\n';
						return;
					}

					if ( ! isEditable( q ) )
					{
						debug.innerHTML += 'Element ' + elname + 'id=' + elId + ' not editable';
						return;
					}

					debug.innerHTML += 'Editing ' + elname + ' id=' + elId + ' \n';

					var editor = CKEDITOR.instances.editor1;

					if ( editingId != -1 )
					{
						setDOM( editingId, editor.getData() );
					}

					editingId = elId;
					editor.setData( makeData( q ) );
				}
				
				function makeData( q ) 
				{
					var data = "";

					for ( q = q.firstChild; q != null; q = q.nextSibling )
						data += serialize( q );
					return data;
				}


					function setDOM( elId, str )
					{
						var li = getContentNode( elId );

						while ( li.hasChildNodes() )
							li.removeChild( li.lastChild );

						str = str == null ? "" : str;

						<![CDATA[
						str=str.replace( /&nbsp;/g, "&#160;" );
						str=str.replace( /&amp;/g, "&#38;" );
						str=str.replace( /\xa0/g, "" );

						str = "<?xml version='1.0'?>\n<doc>" + str + "</doc>";
						]]>

						var p = parse( str ).documentElement;

						//newli = li.cloneNode( false );

						//li.parentNode.replaceChild( newli, li );

						for ( var k = 0; k &lt; p.childNodes.length; k++)
						{
							li.appendChild( p.childNodes[k].cloneNode( true ) );
						}
					}

				</xsl:text>
			</script>

			<script type="text/javascript">
				<xsl:text disable-output-escaping="yes">
				var MainDocument = document;

				var editingId=-1;

				CKEDITOR.config.extraPlugins = 'xmllayout,save2';

				CKEDITOR.plugins.add( 'save2', 
					{
						init : function( editor )
						{
							var cmd = editor.addCommand( 'save2',
								{
									modes : { wysiwyg:1, source:1 },

									exec : function( editor )
									{
										if ( editingId != -1 )
										{
											setDOM( editingId, editor.getData() );
										}

										data = document.getElementById('data');
										data.appendChild( document.createTextNode(
											serialize( transform( contentDoc, removeidxsl ) )
										) );

										document.getElementById('uploadForm').submit();
									}
								}
							);

							cmd.modes = { wysiwyg : 1 };//!!( editor.element.$.form ) };

							editor.ui.addButton( 'Save2',
								{
									label : editor.lang.save,
									command : 'save2',
									className : 'cke_button_save'
								}
							);
						}
						
					}
				);
				</xsl:text>

				CKEDITOR.replace( 'editor1',
					{
						fullPage : false,
						enterMode : CKEDITOR.ENTER_BR,

						on :
						{
							instanceReady: function(ev)
							{
								this.dataProcessor.writer.setRules( 'p',
									{
										indent: false,
										breakBeforeOpen: true,
										breakAfterOpen: false,
										breakBeforeClose: false,
										breakAfterClose: false
									}
								);
								this.dataProcessor.writer.setRules( 'li',
									{
										indent: false,
										breakBeforeOpen: true,
										breakAfterOpen: false,
										breakBeforeClose: false,
										breakAfterClose: false
									}
								);

							}
						},
						toolbar : [
							['Source', 'Save2'], // 'Preview'],
							['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
							['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
							['Styles', 'Format'],
							['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript'],
							['NumberedList', 'BulletedList'],
							['Link', 'Unlink'],
							['Image', 'Table', 'SpecialChar'],
							['TextColor', 'BGColor']
						]
					}
				);
			</script>

		<form id="uploadForm" method="post" action="{$psp:requestBaseURI}template.html">
			<input type="hidden" name="action:template:post"/>
			<input type="text" name="template:referer" value="{$psp:requestURI}{$psp:requestQuery}"/>
			<input type="hidden" name="template:file" value="{$psp:requestDir}{$psp:requestFile}"/>
			<textarea style="display:none" id="data" cols="40" name="template:content"/>
		</form>

		</l:edit>
	</xsl:template>


  <xsl:template match="@*|node()" priority="-2" mode="copy">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()" mode="copy"/>
    </xsl:copy>
  </xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
