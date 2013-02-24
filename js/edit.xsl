<?xml version="1.0"?>

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

	<xsl:template match="node()" priority="-1">
		<xsl:variable name="fold">
			<xsl:choose>
				<xsl:when test="count(ancestor::*) > 2">hidden</xsl:when>
				<xsl:otherwise/>
			</xsl:choose>
		</xsl:variable>

		<l:dl class="node" style="padding-left: 1em">
			<l:dt onclick="javascript:toggleedit('{@id}', '{name()}');return false;">
				<l:div id="fold{generate-id()}" class="fold"
					onclick="javascript:toggle( '{generate-id()}' );return false;">
					<xsl:choose>
						<xsl:when test="normalize-space($fold) = ''">
							-
						</xsl:when>
						<xsl:otherwise>
							+
						</xsl:otherwise>
					</xsl:choose>
				</l:div>
				<xsl:text> </xsl:text>
				<xsl:apply-templates select="." mode="edit-label"/>
			</l:dt>
			<l:dd id="child{generate-id()}" class="{normalize-space($fold)}">
				<xsl:apply-templates select="." mode="edit-content"/>
			</l:dd>
		</l:dl>
	</xsl:template>


	<xsl:template match="node()" mode="edit-content">
		<xsl:apply-templates select="*"/>
	</xsl:template>


	<xsl:template match="node()" mode="edit-label" priority="-1">
		NODE:
		<l:b>
		<xsl:value-of select="name()"/>
		</l:b>
		ns=<xsl:value-of select="namespace-uri()"/>

		<xsl:for-each select="@*">
			<xsl:text> </xsl:text><xsl:value-of select="name()"/>='<xsl:value-of select="."/>'
		</xsl:for-each>
	</xsl:template>


	<xsl:template match="comment()">
		<xsl:comment>
			<xsl:value-of select="."/>
		</xsl:comment>
	</xsl:template>

	<xsl:template match="text()">
		<xsl:value-of select="."/>
	</xsl:template>

	<!-- Edit Overrides TODO: use template merging in static handler -->
	<xsl:template match="pst:template" mode="edit-label">
		<l:span style='color:green'>
		Template <xsl:if test="@name">'<xsl:value-of select="@name"/>'</xsl:if>
		</l:span>
	</xsl:template>

	<xsl:template match="pst:title" mode="edit-label">
		<l:span style='color:green'>
		Page Title: <xsl:apply-templates/>
		</l:span>
	</xsl:template>

	<xsl:template match="pst:content" mode="edit-label">
		<l:span style='color:green'>
		Content
		</l:span>
	</xsl:template>

	<xsl:template match="processing-instruction()" mode="edit-label">
		<l:span style='color:orange'>
		<xsl:choose>
			<xsl:when test="name() = 'psp'">
				Use PSP Module: <xsl:value-of select="substring-after(., 'module=')"/>
			</xsl:when>
			<xsl:when test="name() = 'xml-stylesheet'">
				Stylesheet <xsl:value-of select="substring-after(., 'href=')"/>
			</xsl:when>
			<xsl:otherwise>
		<!--
			Processing-instruction <xsl:value-of select="name()"/> - <xsl:value-of select="."/>
		-->
			</xsl:otherwise>
		</xsl:choose>
		</l:span>
	</xsl:template>


	<xsl:template match="l:slides" mode="edit-label">
		<l:span style='font-weight: bold;'>
		Slides
		</l:span>
	</xsl:template>


	<xsl:template match="l:slide|layout:slide" mode="edit-label">
		<l:span class="editable">
		Slide:
		<xsl:if test="@xml:lang">[<xsl:value-of select="@xml:lang"/>] </xsl:if>
		<xsl:value-of select="l:title"/>
		</l:span>
	</xsl:template>


	<xsl:template match="l:slide|layout:slide" mode="edit-content">
		<xsl:apply-templates select="*" mode="copy"/>
	</xsl:template>


	<xsl:template match="layout:section" mode="edit-label">
		<l:span style='font-weight: bold;' class="editable">
		Section <xsl:value-of select="@title"/>
		</l:span>
	</xsl:template>

	<xsl:template match="layout:p" mode="edit-label">
		<l:span style='font-weight: bold;' class="editable">
		Paragraph
		</l:span>
	</xsl:template>


	<xsl:template match="l:img|l:br">
		<xsl:copy-of select="."/>
	</xsl:template>


<!--
	<xsl:template match="p" mode="edit-label">
		P
	</xsl:template>
	-->
<!--
	<xsl:template match="p" mode="edit-content">
		<p>
			<xsl:appl-templates select="@*" mode="copy"/>
			<xsl:apply-templates select="*|text()"/>
		</p>
	</xsl:template>

-->


  <xsl:template match="@*|node()" priority="-2" mode="copy">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()" mode="copy"/>
    </xsl:copy>
  </xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
