<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="l psp php"
>
	<xsl:param name="psp:requestBaseURL" select='$requestBaseURL'/>

	<xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes"/>

	<xsl:template match="l:menu">
		<xsl:apply-templates select="l:item|l:menu"/>
	</xsl:template>

	<xsl:template match="l:item">
		<url>
			<loc><xsl:value-of select="$psp:requestBaseURL"/><xsl:choose>
				<xsl:when test="@page"><xsl:value-of select="@page"/>.html</xsl:when>
				<xsl:when test="@slashpage"><xsl:value-of select="@slashpage"/></xsl:when>
			</xsl:choose></loc>
			<lastmod>
				<xsl:value-of select="php:function('psp_lastmodfilestr', concat(@page,'.xml'), 'content' )"/>
			</lastmod>
		</url>
	</xsl:template>


  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
