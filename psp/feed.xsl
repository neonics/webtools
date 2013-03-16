<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:feed="http://neonics.com/2013/psp/feed"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:atom="http://www.w3.org/2005/Atom"
	exclude-result-prefixes="atom feed"
>
	<xsl:template match="feed:feed">
		<xsl:apply-templates select="php:function('feed_fetch', string(@href))"/>
	</xsl:template>

	<xsl:template match="atom:feed">
		<h1><xsl:value-of select="atom:title"/></h1>
		<h2 class="slogan2"><xsl:value-of select="atom:subtitle"/></h2>

		<xsl:apply-templates select="atom:entry"/>
	</xsl:template>

	<xsl:template match="atom:entry">
		<h2><xsl:value-of select="atom:title"/></h2>
		<div>
			<xsl:value-of select="atom:content" disable-output-escaping="yes"/>
		</div>
	</xsl:template>

  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
