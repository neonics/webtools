<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:admin="http://neonics.com/2013/psp/admin"
	exclude-result-prefixes="admin psp"
>
	<xsl:param name="base"/>

	<xsl:template match="admin:site-overview">
		<xsl:apply-templates select="l:menu" mode="admin-site"/>
	</xsl:template>

	<xsl:template match="l:menu" mode="admin-site">
		<table class="menu {@class}">
			<tr><th colspan="3">SITE MENU</th></tr>
			<xsl:apply-templates mode="admin-site"/>
		</table>
	</xsl:template>

	<xsl:template match="l:menu/l:item" mode="admin-site">
		<tr>
			<xsl:apply-templates mode="admin-site"/>
			<td>
				<xsl:for-each select="@*">
					<input type="text" value="{name(.)}"/>
					<input type="text" value="{.}"/>
				</xsl:for-each>
			</td>

			<xsl:if test="@page">
			<td>
				<a href="page/{@page}">edit page</a>
			</td>
			</xsl:if>
		</tr>
	</xsl:template>

	<xsl:template match="l:item/l:label" mode="admin-site">
		<td>
			<input type="text" size="2" value="{@xml:lang}"/>
		</td>
		<td>
			<input type="text" value="{.}"/>
		</td>
	</xsl:template>

</xsl:stylesheet>
