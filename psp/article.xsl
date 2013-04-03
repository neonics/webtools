<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 

<?psp module="db"?>
<?psp module="auth"?>

<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:article="http://neonics.com/2011/article"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:auth="http://neonics.com/2000/xsp/auth"
	xmlns:db="http://neonics.com/2011/db/xml"
	xmlns:xhtml="http://www.w3.org/1999/xhtml"
	exclude-result-prefixes="article auth db xhtml"
>
	<xsl:param name="psp:requestBaseURI" select="$requestBaseURI"/>
	<xsl:param name="psp:slashmode" select="$slashmode"/>
	<xsl:param name="psp:requestPathURI" select="$requestPathURI"/>
	<xsl:param name="article:article" select="$article"/>
	<xsl:param name="lang" select="'en'"/>

	<xsl:strip-space elements="article:*"/>

	<!-- public -->

	<xsl:template match="article:menu">
	<!--
		<l:message type="debug">SLASHMODE: [<xsl:value-of select="$psp:slashmode"/>]</l:message>
		<l:message type="debug">base <xsl:value-of select="$psp:requestBaseURI"/></l:message>
		-->
		<l:menu class="vmenu {@class}">
			<?psp module="article"?>
			<auth:permission role="author">
				<auth:success>
					<l:item class="menutitle">Author</l:item>

					<xsl:choose>
						<xsl:when test="php:function('psp_isaction', 'article:edit')">

							<l:item class="subtitle">Draft</l:item>
							<xsl:choose>
								<xsl:when test="$psp:slashmode">
									<l:item href="save-draft">Save Draft</l:item>
									<l:item href="preview">Preview</l:item>
									<l:item href="post">Publish</l:item>
									<l:item href="..">Cancel</l:item>
								</xsl:when>
								<xsl:otherwise>
									<script type="text/javascript">
										function submitarticle(action)
										{
											var form = document.getElementById( 'articleedit' );
											var act = document.getElementById( 'articleaction' );
											act.name = 'action:article:' + action;
											form.submit();
										}
									</script>
								<!--
									<l:item action="article:save-draft">Save Draft</l:item>
									<l:item action="article:preview">Preview</l:item>
									<l:item action="article:post">Publish</l:item>
									<l:item href="{$psp:requestPathURI}">Cancel</l:item>
								-->
									<l:item href="javascript:submitarticle('save-draft')">Save Draft</l:item>
									<l:item href="javascript:submitarticle('preview')">Preview</l:item>
									<l:item href="javascript:submitarticle('post')">Publish</l:item>
									<l:item href="">Cancel</l:item>
								</xsl:otherwise>
							</xsl:choose>

						</xsl:when>
						<xsl:otherwise>

							<xsl:choose>
								<xsl:when test="$psp:slashmode">
									<l:item href="{$psp:requestBaseURI}0/edit">Compose new article</l:item>
								</xsl:when>
								<xsl:otherwise>
									<l:item action="article:edit" page="article">Compose new article</l:item>
								</xsl:otherwise>
							</xsl:choose>

						</xsl:otherwise>
					</xsl:choose>

				</auth:success>
			</auth:permission>


			<l:item class="menutitle">
				<xsl:choose>
					<!-- TODO FIXME -->
					<!--
					<xsl:when test="//l:translation[@name='article:articles']">
						<xsl:value-of select="//l:translations/l:translation[@name='article:articles' and @xml:lang=$lang]"/>
					</xsl:when>
					-->
					<xsl:when test="@menutitle">
						<xsl:value-of select="@menutitle"/>
					</xsl:when>
					<xsl:otherwise>Articles</xsl:otherwise>
				</xsl:choose>
			</l:item>
			<xsl:apply-templates select="php:function('article_index')" mode="index"/>

		</l:menu>
	</xsl:template>

	<xsl:template match="@*|*" mode="x">
		Tag: <xsl:value-of select="name(.)"/> = <xsl:value-of select="."/>
		<xsl:apply-templates select="@*|*" mode="x"/>
	</xsl:template>

	<xsl:template match="article:show">
		<xsl:choose>
			<xsl:when test="php:function('psp_isaction', 'article:edit')">
				<xsl:variable name="art" select="php:function('article_get', string($article:article))"/>

				<l:form method="post" id="articleedit" onsubmit="return updateFormContent();">
					<xsl:if test="$psp:slashmode">
						<xsl:attribute name="action">../<xsl:value-of select="article:article"/></xsl:attribute>
					</xsl:if>

					<l:input type="hidden" name="article:id" value="{$article:article}"/>
					<l:input type="hidden" name="action:article:post" value="" id="articleaction"/>

					<l:label for="t{generate-id()}">Title</l:label>
					<l:input id="t{generate-id()}" name="article:title" type="text"
						value="{$art/@title}" size="80"/><l:br/>

					<l:label style="vertical-align:top">Text</l:label>
					<l:input type="hidden" name="article:content:format" value="xml-with-wrapper"/>
					<l:input type="hidden" id="edit_content" name="article:content"/>

					<style type="text/css">
						#rich-edit { min-height: 2em; }
						#edit-toolbar { margin: .5em; }
						#edit-toolbar span { border: 1px solid #888; padding:.2em; }
					</style>

					<div id="edit-toolbar">
						<span
							onclick="applyStyle('bold');"
							onmousedown='event.preventDefault();'
							style="font-weight: bold;"
						>
							B
						</span>
					</div>


					<div id="rich-edit" contenteditable="true" >
						<xsl:apply-templates select="$art/article:content" mode="content"/>
					</div>

				<script type="text/javascript" src="{$requestBaseURI}js/xml.js"/>
				<script type="text/javascript">
					<xsl:text disable-output-escaping="yes">
					var editel = document.getElementById('rich-edit');

					function updateFormContent()
					{
						/*
						v = "";
						
						var cl = editel.childNodes;

						for ( var i = 0; i &lt; cl.length; i ++ )
							v += serialize( cl.item( i ) );

						document.getElementById( "edit_content" ).value = v;
						*/

						document.getElementById( "edit_content" ).value = serialize( editel );

						return true;
					}

					function applyStyle( cmd, value )
					{
						document.execCommand( cmd, false, value );
						editel.focus();
					}
					editel.focus();
					</xsl:text>
				</script>
				<l:input type="submit" name="action:article:post"/>
				</l:form>
			</xsl:when>

			<xsl:when test="string($article:article)!=''">
				<xsl:apply-templates select="php:function('article_get', string($article:article))" mode="show"/>
			</xsl:when>

			<xsl:when test="@id">
				<xsl:apply-templates select="php:function('article_get', string(@id))" mode="show"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates select="php:function('article_index')" mode="showfirst"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- done instead of copy-of so exclude-namespace-uri has effect -->
  <xsl:template match="article:content" priority="-2" mode="content">
     <xsl:apply-templates select="@*|node()" mode="content"/>
  </xsl:template>

  <xsl:template match="@*|node()|*" priority="-2" mode="content">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()" mode="content"/>
    </xsl:copy>
  </xsl:template>



	<xsl:template match="article:articles" mode="showfirst">
		<xsl:apply-templates select="php:function('article_get', string( article:article[not(article:content/@xml:lang) or article:content/@xml:lang=$lang][position()=1]/@db:id ))" mode="show"/>
	</xsl:template>


	<xsl:template match="article:link">
		<l:link page="article?article:id={@article:id}">
			<xsl:apply-templates/><!-- select="@*[not(name()='article:id')]|*"/>-->
		</l:link>
	</xsl:template>

	<!-- internals -->

	<!-- index/menu mode -->

	<xsl:template match="article:articles" mode="index">
		<xsl:apply-templates select="article:article[not(article:content/@xml:lang) or article:content/@xml:lang=$lang]" mode="index"/>
	</xsl:template>

	<xsl:template match="article:article" mode="index">
		<xsl:choose>
			<xsl:when test="$psp:slashmode">
				<l:item href="{$psp:requestBaseURI}{@db:id}"><xsl:value-of select="@title"/></l:item>
			</xsl:when>
			<xsl:otherwise>
				<l:item page="article?article:id={@db:id}&amp;l={$lang}">
					<xsl:if test="0=string-length(@title)"><i>no title</i></xsl:if>
					<xsl:value-of select="@title"/>
				</l:item>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- default mode -->

	<xsl:template match="article:articles|article:article"/>


	<xsl:template match="article:articles" mode="show">
		<xsl:apply-templates mode="show"/><!-- select="article:article"/>-->
	</xsl:template>

	<xsl:template match="article:article" mode="show">
		<l:link anchor="article{@db:id}"/>
		
		<xsl:if test="@title">
			<l:h1 class="article">
			<!--<xsl:value-of select="@db:id"/><xsl:text> </xsl:text>-->
			<xsl:choose>
				<xsl:when test="article:content/l:title">
					<xsl:apply-templates select="article:content/l:title[1]"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@title"/>
				</xsl:otherwise>
			</xsl:choose>
			<auth:permission role="author">
				<xsl:choose>
					<xsl:when test="$psp:slashmode">
						<l:link href="{$psp:requestBaseURI}{@db:id}/edit"><l:i>SM Edit</l:i></l:link>
					</xsl:when>
					<xsl:otherwise>
						<l:link action="article:edit" style="display:inline">
							<l:arg name="article:id" value="{@db:id}"/>
							<l:i style="padding-left: 1em; font-size: 80%;">(Edit)</l:i>
						</l:link>
					</xsl:otherwise>
				</xsl:choose>
			</auth:permission>

			</l:h1>
		</xsl:if>

<!--
		<auth:permission role="author">
			<l:h2 class="article">
				<xsl:choose>
					<xsl:when test="$psp:slashmode">
						<l:link href="{$psp:requestBaseURI}{@db:id}/edit">SM Edit</l:link>
					</xsl:when>
					<xsl:otherwise>
						<l:link action="article:edit">
							<l:arg name="article:id" value="{@db:id}"/>
							Edit
						</l:link>
					</xsl:otherwise>
				</xsl:choose>
			</l:h2>
		</auth:permission>
-->

		<l:div class="article">
			<xsl:apply-templates select="article:content"/>
		</l:div>
	</xsl:template>

	<xsl:template match="article:content">
		<div class="articlecontent">
		<xsl:apply-templates/>
		</div>
	</xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
