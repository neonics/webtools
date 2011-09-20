<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 


<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:db="http://neonics.com/2011/db/xml"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	exclude-result-prefixes="db"
>

	<xsl:param name="base"/>

	<xsl:template match="db:list[@name]">
		<xsl:apply-templates select="php:function('db_listTable', @name)" mode="single"/>
	</xsl:template>


	<xsl:template match="db:list">
		<xsl:apply-templates select="php:function('db_listTable')" mode="single"/>
	</xsl:template>

	<xsl:template match="db:projects" mode="menu">
		<l:menu class="vmenu">
			<l:item class="menutitle">Projects</l:item>
			<l:menu class="vmenu">
				<xsl:for-each select="db:project">
					<l:item page="{@uri}">
						<l:label><xsl:value-of select="@title"/></l:label>
					</l:item>
				</xsl:for-each>
			</l:menu>
		</l:menu>
	</xsl:template>

	
	<xsl:template match="db:projects" mode="single">
		<xsl:apply-templates select="db:project[@name=$project]"/>
	</xsl:template>


	<xsl:template match="db:projects" mode="pmenu">
		<xsl:for-each select="db:project[@name=$project]/db:data">
			<xsl:apply-templates mode="pmenu"/>
		</xsl:for-each>
	</xsl:template>

	<xsl:template match="db:documentation" mode="pmenu">
		<l:menu class="hmenu">
			<l:item>Documentation (</l:item>
			<xsl:apply-templates select="db:format"/>
			<l:item><l:label>)</l:label></l:item>
		</l:menu>
	</xsl:template>

	<xsl:template match="db:api" mode="pmenu">
		<l:menu class="hmenu">
		<l:item>API</l:item>
		<xsl:apply-templates select="db:format"/>
		</l:menu>
	</xsl:template>

	<xsl:template match="db:download" mode="pmenu">
		<l:menu class="vmenu">
			<l:item class="subtitle">Download</l:item>
			<xsl:for-each select="db:version">
			<l:item href="{@file}"><xsl:value-of select="@name"/></l:item>
			</xsl:for-each>
		</l:menu>
	</xsl:template>

	<xsl:template match="db:format">
		<l:item href="{@href}"><xsl:value-of select="@type"/></l:item>
	</xsl:template>


	<xsl:template match="db:project">
		<l:h3><xsl:value-of select="@title"/></l:h3>
		<l:div>
			<xsl:apply-templates select="db:description | l:description"/>
		</l:div>
	</xsl:template>

	<xsl:template match="db:data"/>

	<xsl:template match="l:description | db:description">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="db:*">
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
	<xsl:template match="db:*" priority="-1"/>
-->

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


</xsl:stylesheet>
