<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:feed="http://neonics.com/2013/psp/feed"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"

	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:gd="http://schemas.google.com/g/2005"

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

		<xsl:apply-templates select="php:function('feed_fetch', string( atom:link[@rel='replies'][@type='application/atom+xml']/@href) )" mode="comments"/>

		<xsl:apply-templates select="atom:link[@rel='replies'][@type='text/html']"/>

	</xsl:template>

	<xsl:template match="atom:link[@rel='replies'][@type='text/html']">
		<h4><a href="{@href}">Post comment</a></h4>
	</xsl:template>


	<xsl:template match="atom:feed" mode="comments">
		<h4><xsl:value-of select="atom:title"/></h4>
		<ul class="comments">
			<xsl:for-each select="atom:entry">
				<li>
					<xsl:apply-templates select="atom:author"/>
					<span class="date">
						<xsl:choose>
							<xsl:when test="gd:extendedProperty[@name='blogger.displayTime']">
								<xsl:value-of select="gd:extendedProperty[@name='blogger.displayTime']/@value"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="atom:updated"/>
							</xsl:otherwise>
						</xsl:choose>
					</span>
					<br/>
					<div class="content">
						<xsl:value-of select="atom:content" disable-output-escaping="yes"/>
					</div>
				</li>
			</xsl:for-each>
		</ul>
	</xsl:template>

	<xsl:template match="atom:author">
		<xsl:apply-templates select="gd:image"/>
		<a href="{atom:uri}" class="author"> <xsl:value-of select="atom:name"/> </a>
	</xsl:template>

	<xsl:template match="gd:image">
		<img src="{@src}" width="{@width}" height="{@height}"/>
	</xsl:template>

  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
