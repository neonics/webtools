<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"

	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:psp="http://neonics.com/2011/psp"
>

	<xsl:include href="layout.xsl"/>

	<xsl:template match="l:page">
		<xsl:variable name="themecss"><xsl:if test="@css"><xsl:value-of select="@css"/>/</xsl:if></xsl:variable>
  	<html>
			<xsl:comment>Software author: Kenney Westerhof / Neonics.com </xsl:comment>
   		<head>
     		<title><xsl:value-of select="title"/></title>
     		<link rel="stylesheet" type="text/css" href="{$psp:requestBaseURI}css/{$themecss}layout.css"/>
				<script type="text/javascript" src="{$psp:requestBaseURI}js/script.js"/>
				<script type="text/javascript" src="{$psp:requestBaseURI}js/dragdrop.js"/>
			</head>
    	<xsl:apply-templates select="l:body"/>
  	</html>
	</xsl:template>

	<xsl:template match="l:body">
    <body onload="InitDragDrop()">
			<xsl:apply-templates select="@*"/>
			<div class="main">
				<xsl:apply-templates select="l:header"/>
				<xsl:apply-templates select="l:content"/>
			</div>

			<xsl:apply-templates select="l:footer"/>
    </body>
	</xsl:template>

	<xsl:template match="l:content|l:header|l:banner">
		<div class="{local-name(.)}">
			<xsl:apply-templates/>
		</div>
	</xsl:template>

  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>


