<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:project="http://www.neonics.com/2000/project"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	exclude-result-prefixes="project"
>

	<xsl:param name="project"/>
	<xsl:param name="base" select="concat( $base, '/', $project )"/>

	<xsl:template match="project:projects-menu">
		<xsl:variable name="p"><xsl:choose>
			<xsl:when test="@project"><xsl:value-of select="@project"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$project"/></xsl:otherwise>
		</xsl:choose></xsl:variable>
		<xsl:choose>
			<xsl:when test="$p">
				<l:menu class="vmenu">
					<l:item class="menutitle">
						<l:label>Project <xsl:value-of select="$p"/></l:label>
					</l:item>
					<xsl:apply-templates select="php:function('project_index')" mode="pmenu"/>
				</l:menu>
			</xsl:when>
			<xsl:otherwise>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates select="php:function('project_index')" mode="menu"/>
	</xsl:template>

	<xsl:template match="project:projects-list">
		<xsl:choose>
			<xsl:when test="$project">
				<xsl:apply-templates select="php:function('project_index')" mode="single"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="php:function('project_index')"/>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>

	<xsl:template match="project:projects">
		<xsl:apply-templates select="project:project"/>
	</xsl:template>


	<xsl:template match="project:projects" mode="menu">
		<l:menu class="vmenu">
			<l:item class="menutitle">Projects</l:item>
			<l:menu class="vmenu">
				<xsl:for-each select="project:project">
					<l:item page="{@uri}">
						<l:label><xsl:value-of select="@title"/></l:label>
					</l:item>
				</xsl:for-each>
			</l:menu>
		</l:menu>
	</xsl:template>

	
	<xsl:template match="project:projects" mode="single">
		<xsl:apply-templates select="project:project[@name=$project]"/>
	</xsl:template>


	<xsl:template match="project:projects" mode="pmenu">
		<xsl:for-each select="project:project[@name=$project]/project:data">
			<xsl:apply-templates mode="pmenu"/>
		</xsl:for-each>
	</xsl:template>

	<xsl:template match="project:documentation" mode="pmenu">
		<l:menu class="hmenu">
			<l:item>Documentation (</l:item>
			<xsl:apply-templates select="project:format"/>
			<l:item><l:label>)</l:label></l:item>
		</l:menu>
	</xsl:template>

	<xsl:template match="project:api" mode="pmenu">
		<l:menu class="hmenu">
		<l:item>API</l:item>
		<xsl:apply-templates select="project:format"/>
		</l:menu>
	</xsl:template>

	<xsl:template match="project:download" mode="pmenu">
		<l:menu class="vmenu">
			<l:item class="subtitle">Download</l:item>
			<xsl:for-each select="project:version">
			<l:item href="{@file}"><xsl:value-of select="@name"/></l:item>
			</xsl:for-each>
		</l:menu>
	</xsl:template>

	<xsl:template match="project:format">
		<l:item href="{@href}"><xsl:value-of select="@type"/></l:item>
	</xsl:template>


	<xsl:template match="project:project">
		<l:h1 class="project"><xsl:value-of select="@title"/></l:h1>
		<l:div>
			<xsl:apply-templates select="project:description | l:description"/>
		</l:div>
	</xsl:template>

	<xsl:template match="project:data"/>

	<xsl:template match="l:description | project:description">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="project:*">
		<xsl:element name="{local-name(.)}" prefix="l">
			<xsl:apply-templates select="@*"/>
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>

	<!-- Documentation proxy -->

	<xsl:template match="doc | section | para | list | el">
		<xsl:element name="{local-name(.)}" namespace="http://www.neonics.com/xslt/layout/1.0">

			<xsl:apply-templates select="@*|node()"/>
		</xsl:element>
	</xsl:template>

<!--
	<xsl:template match="project:*" priority="-1"/>
-->

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
