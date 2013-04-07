<?xml version="1.0" encoding="UTF-8"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"

	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:pst="http://neonics.com/2011/psp/template"
	xmlns:auth="http://neonics.com/2000/xsp/auth"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:admin="http://neonics.com/2013/psp/admin"

	xmlns:xi="http://www.w3.org/2001/XInclude"
	xmlns:x="http://www.w3.org/1999/xhtml"
	xmlns="http://www.w3.org/1999/xhtml"

	exclude-result-prefixes="pst l psp auth admin xi x"
>
	<xsl:template match="l:menu">
		<table class="{@class}">
			<xsl:apply-templates/>
		</table>
	</xsl:template>

	<!-- inverse: -->

	<xsl:template match="x:table">
		<!--
		<table class="menu {@class}">
			<tr><th colspan="3">SITE MENU</th></tr>
			<xsl:apply-templates/>
		</table>
		-->
		<l:menu class="{@class}">
			<xsl:apply-templates/>
		</l:menu>
	</xsl:template>



	<xsl:template match="l:menu/l:item">
		<tr item="{position()}">
			<xsl:apply-templates/>
			<xsl:for-each select="@*">
				<td attr="{position()}">
					<input type="text" value="{name(.)}" item="name"/>
					<input type="text" value="{.}" item="value"/>
				</td>
			</xsl:for-each>

			<xsl:if test="@page">
			<td>
				<a href="page/{@page}">edit page</a>
			</td>
			</xsl:if>
		</tr>
	</xsl:template>

	<!-- inverse: -->

	<xsl:template match="x:tr[@item]">
		<l:item>
			<xsl:for-each select="x:td[@attr][not(@attr='lang')]">
				<xsl:attribute name="{x:input[@item='name']/@value}">
					<xsl:value-of select="x:input[@item='value']/@value"/>
				</xsl:attribute>
			</xsl:for-each>
			<xsl:apply-templates select="x:td[@attr='lang']"/><xsl:text>
	</xsl:text></l:item>
	</xsl:template>

	<xsl:template match="x:tr" priority="-1"/>


	<xsl:template match="l:item/l:label">
		<td attr="lang">
			<input type="text" size="2" value="{@xml:lang}"/>
			<input type="text" value="{.}"/>
		</td>
	</xsl:template>

	<!-- inverse: -->

	<xsl:template match="x:td[@attr='lang']"><xsl:text>
		</xsl:text><l:label xml:lang="{x:input[1]/@value}">
			<xsl:value-of select="x:input[2]/@value"/>
		</l:label>
	</xsl:template>


	<!-- identity -->

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
