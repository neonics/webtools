<?xml version="1.0" encoding="UTF-8"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:pst="http://neonics.com/2011/psp/template"
	exclude-result-prefixes="pst"

	xmlns:layout="http://www.neonics.com/xslt/layout/1.0"
	xmlns:xi="http://www.w3.org/2001/XInclude"

	xmlns:auth="http://neonics.com/2000/xsp/auth"

	xmlns:l="http://www.w3.org/1999/xhtml"

>
<!--
	xmlns:xml="http://www.w3.org/1998/XML/namespace"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:x="http://www.w3.org/1999/xhtml"
	-->

	<xsl:template match="/">
		<dl class="node">
			<style type="text/css">dl{padding-left: 1em}</style>
			<xsl:apply-templates select="node()|processing-instruction()"/>
		</dl>
	</xsl:template>

	<xsl:template match="node()">
		<dl class="node" style="padding-left: 1em">
			<dt>
				<xsl:value-of select="name(.)"/>
			</dt>
			<dd>
				<xsl:apply-templates select="@*|node()|text()"/><!--@*|*"/>-->
			</dd>
		</dl>
	</xsl:template>

	<xsl:template match="text()">
		<div><xsl:value-of select="."/></div>
	</xsl:template>

	<xsl:template match="processing-instruction()">
			<dt><xsl:value-of select="name(.)"/></dt>
			<dd><xsl:value-of select="."/></dd>
	</xsl:template>

	<xsl:template match="@*">
		<dl>
			<dt style="display:inline">
				@<xsl:value-of select="name(.)"/>
			</dt>
			<dd style="display:inline">
				<xsl:value-of select="."/>
			</dd>
		</dl>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
