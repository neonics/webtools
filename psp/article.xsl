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
	exclude-result-prefixes="article auth"
>
	<xsl:param name="psp:requestBaseURI" select="$requestBaseURI"/>
	<xsl:param name="psp:slashmode" select="$slashmode"/>
	<xsl:param name="psp:requestPathURI" select="$requestPathURI"/>
	<xsl:param name="article:article" select="$article"/>

	<xsl:strip-space elements="article:*"/>

	<!-- public -->

	<xsl:template match="article:menu">
	<!--
		<l:message type="debug">SLASHMODE: [<xsl:value-of select="$psp:slashmode"/>]</l:message>
		<l:message type="debug">base <xsl:value-of select="$psp:requestBaseURI"/></l:message>
		-->
		<l:menu class="vmenu">
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
									<l:item action="action:article:save-draft">Save Draft</l:item>
									<l:item action="action:article:preview">Preview</l:item>
									<l:item action="action:article:post">Publish</l:item>
									<l:item href="{$psp:requestPathURI}">Cancel</l:item>
								</xsl:otherwise>
							</xsl:choose>

						</xsl:when>
						<xsl:otherwise>

							<xsl:choose>
								<xsl:when test="$psp:slashmode">
									<l:item href="{$psp:requestBaseURI}0/edit">Compose new article[1]</l:item>
								</xsl:when>
								<xsl:otherwise>
									<l:item action="article:edit">Compose new article [2]</l:item>
								</xsl:otherwise>
							</xsl:choose>

						</xsl:otherwise>
					</xsl:choose>

				</auth:success>
			</auth:permission>

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

				<l:form method="post">
					<xsl:if test="$psp:slashmode">
						<xsl:attribute name="action">../<xsl:value-of select="article:article"/></xsl:attribute>
					</xsl:if>
					<l:input type="hidden" name="article:id" value="{$article:article}"/>

					<l:label for="t{generate-id()}">Title</l:label>
					<l:input id="t{generate-id()}" name="article:title" type="text"
						value="{$art/@title}"/><l:br/>

					<l:textarea name="article:content">
						<xsl:value-of select="$art/article:content"/>
					</l:textarea>

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
				<xsl:apply-templates select="php:function('article_index')" mode="show"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="article:link">
		<l:link page="article?article:id={@article:id}">
			<xsl:apply-templates/><!-- select="@*[not(name()='article:id')]|*"/>-->
		</l:link>
	</xsl:template>

	<!-- internals -->

	<!-- index/menu mode -->

	<xsl:template match="article:articles" mode="index">
		<l:item class="menutitle">Articles</l:item>
		<xsl:apply-templates select="article:article" mode="index"/>
	</xsl:template>

	<xsl:template match="article:article" mode="index">
		<xsl:choose>
			<xsl:when test="$psp:slashmode">
				<l:item href="{$psp:requestBaseURI}{@db:id}"><xsl:value-of select="@title"/></l:item>
			</xsl:when>
			<xsl:otherwise>
				<l:item page="article?article:id={@db:id}"><xsl:value-of select="@title"/></l:item>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- default mode -->

	<xsl:template match="article:articles|article:article"/>


	<xsl:template match="article:articles" mode="show">
		<xsl:apply-templates/><!-- select="article:article"/>-->
	</xsl:template>

	<xsl:template match="article:article" mode="show">
		
		<l:link anchor="article{@db:id}"/>
		
			<!--
		<xsl:if test="@title">
		<l:h1 class="article">
			<xsl:value-of select="@db:id"/><xsl:text> </xsl:text>
			<xsl:choose>
				<xsl:when test="article:content/l:title">
					<xsl:apply-templates select="article:content/l:title[1]"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@title"/>
				</xsl:otherwise>
			</xsl:choose>
		</l:h1>
		</xsl:if>
				-->

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
