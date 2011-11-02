<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->
<!-- This will transform the input namespace html-similar elements to
	the proper HTML namespace -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	exclude-result-prefixes="l"
>

	<xsl:template match="l:p">
		<xsl:variable name="style">
			<xsl:if test="@align">text-align: <xsl:value-of select="@align"/>;</xsl:if>
			<xsl:if test="@whitespace">whitespace: <xsl:value-of select="@whitespace"/>;</xsl:if>
		</xsl:variable>

		<p style="{$style}">
			<xsl:for-each select="@*">
				<xsl:choose>
					<xsl:when test="name(.)='style'">
						<xsl:value-of select="$style"/><xsl:value-of select="."/>
					</xsl:when>
					<xsl:when test="@align | @whitespace"/>
					<xsl:otherwise>
						<xsl:copy/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<xsl:apply-templates select="node()"/>
		</p>
	</xsl:template>

	<xsl:template match="l:small">
		<small>
			<xsl:apply-templates select="node()|@*"/>
		</small>
	</xsl:template>

	<xsl:template match="l:a">
		<a>
			<xsl:apply-templates select="node()|@*"/>
		</a>
	</xsl:template>

	<xsl:template match="l:b">
		<b>
			<xsl:apply-templates select="node()|@*"/>
		</b>
	</xsl:template>

	<xsl:template match="l:i">
		<i>
			<xsl:apply-templates select="node()|@*"/>
		</i>
	</xsl:template>

	<xsl:template match="l:img">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:dl | l:dt | l:dd | l:ul | l:li">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:div | l:br">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:h1 | l:h2 | l:h3 | l:h4 | l:h5 | l:h6 | l:h7 ">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:table | l:tr | l:th | l:td ">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:form | l:input | l:label | l:textarea">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="@*|node()"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="l:embed | l:object">
		<xsl:element name="{local-name(.)}">
			<xsl:apply-templates select="@*|node()"/>
		</xsl:element>
	</xsl:template>




</xsl:stylesheet>
