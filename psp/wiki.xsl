<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:wiki="http://neonics.com/2011/wiki"
	xmlns:db="http://neonics.com/2011/db/xml"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	exclude-result-prefixes="wiki"
>

	<xsl:param name="base"/>

	<!-- **********  index *********** -->

	<!-- 'call' tag -->
	<xsl:template match="wiki:index">
		<l:div>WIKI INDEX</l:div>
		<xsl:apply-templates select="php:function('wiki_index')" mode="index"/>
	</xsl:template>

	<!-- output -->

	<xsl:template match="wiki:index" mode="index">
		<l:table class="wikiindex">
			<xsl:apply-templates select="wiki:article" mode="index"/>
		</l:table>
	</xsl:template>

	<xsl:template match="wiki:article" mode="index">
		<l:tr class="wikiarticle">
			<l:td>
			<!--
			<l:link page="wiki?action:wiki:show=&amp;wiki:title={@title}">
			-->
			<l:link slashpage="wiki/{@title}">
			'<xsl:value-of select="@title"/>'
			</l:link>
			</l:td>

			<l:td> <xsl:value-of select="@date"/> </l:td>
			<l:td> <xsl:value-of select="@status"/></l:td>
		</l:tr>
	</xsl:template>


	<!-- *********** display an article *********** -->

	<xsl:template match="wiki:show[@title]">
		<xsl:apply-templates select="php:function('wiki_get', string(@title))" mode="show"/>
	</xsl:template>

	<xsl:template match="wiki:show">
		<xsl:apply-templates select="php:function('wiki_get')" mode="show"/>
	</xsl:template>

	<xsl:template match="wiki:edit">
		<xsl:apply-templates select="php:function('wiki_get')" mode="edit"/>
	</xsl:template>


	<xsl:template match="db:result[@size=0]" mode="show">
		<l:p>
			Article does not exist.
		</l:p>
	</xsl:template>


	<xsl:template match="db:result" mode="show">
		<xsl:apply-templates/>
	</xsl:template>


	<xsl:template match="wiki:article" mode="show">
		<l:div class="wikiarticle">
			<l:h1 class="wikititle">
				<xsl:value-of select="@title"/>
			</l:h1>
			<l:div class="wikitext">
				<xsl:apply-templates select="wiki:xmltext"/>
			</l:div>
		</l:div>
	</xsl:template>


	<!-- *********** Edit form *********** -->


	<xsl:template match="wiki:article | db:result[@size=0]" mode="edit">
		<l:form method="post">
			<l:input type="hidden" name="wiki:article:id" value="{@id}"/>
			<l:label for="t{generate-id()}">Title</l:label>
			<l:input id="t{generate-id()}" name="wiki:article:title" type="text"
				value="{@title}" size="80"/><l:br/>

			<l:label style="vertical-align:top">Text</l:label>
			<l:textarea name="wiki:article:text" cols="80" rows="25">
				<xsl:apply-templates select="wiki:text" mode="edit"/>
			</l:textarea>

			<l:input type="submit" name="action:wiki:post"/>
		</l:form>
	</xsl:template>

	<xsl:template match="wiki:text" mode="edit">
		<xsl:apply-templates/>
	</xsl:template>



	<!-- **** -->

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
