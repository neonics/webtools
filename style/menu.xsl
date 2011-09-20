<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
>
	<xsl:param name="base"/>

	<xsl:template match="l:menu">
		<ul class="menu {@class}">
			<!--
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="@class"><xsl:value-of select="@class"/></xsl:when>
					<xsl:otherwise>menu</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			-->
			<xsl:apply-templates/>
		</ul>
	</xsl:template>

	<xsl:template match="l:menu/l:item/l:menu">
		<ul class="submenu {@class}" 
			id="{generate-id()}"
			style="visibility: hidden; position: absolute;">
			<xsl:apply-templates/>
		</ul>
	</xsl:template>


	<xsl:template match="l:menu/l:item">
		<xsl:variable name="el">
			document.getElementById( "<xsl:value-of select="generate-id(l:menu)"/>" )
		</xsl:variable>

		<xsl:variable name="onmouseover">
			<xsl:if test="l:menu">
				var el = <xsl:value-of select="$el"/>; el.style.visibility='visible';
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="onmouseout">
			<xsl:if test="l:menu">
				var el = <xsl:value-of select="$el"/>; el.style.visibility='hidden';
			</xsl:if>
		</xsl:variable>

		<li class="menuitem {@class}"
			onmouseover="{$onmouseover}" onmouseout="{$onmouseout}"
		>
			<xsl:call-template name="link"/>
		</li>
	</xsl:template>


	<xsl:template match="l:selectlanguage">
		<div id="langselect">
			<span>
			Choose Language
			</span>
			<a href="javascript:show('menu_nl');hide('menu_en');hide('langselect');">
				<img alt="flag-NL" src="img/flag-nl.png" style="border: 0" id="flag_nl"/>
			</a>
			<a href="javascript:hide('menu_nl');show('menu_en');hide('langselect');">
				<img alt="flag-EN" src="img/flag-en.png" style="border: 0" id="flag_en"/>
			</a>
		</div>
		<script type="text/javascript">
			if ( lang != null )
			{
				show('menu_'+lang);
				hide('langselect');
			}
		</script>
	</xsl:template>


</xsl:stylesheet>
