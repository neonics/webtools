<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 

<!-- PHP Version -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:pst="http://neonics.com/2011/psp/template"
	xmlns:xsp="http://neonics.com/2001/xsp"
	xmlns:php="http://php.net/xsl" 
>
<!-- exclude-result-prefixes="psp" -->

	<xsl:strip-space elements="xsl:* psp:* xsp:*"/>

	<xsl:template match="psp:page | xsp:page">
		<xsl:choose>
			<xsl:when test="@template">
				<xsl:value-of select="php:function('psp_module', 'template')"/>
				<pst:template name="{@template}">
					<xsl:apply-templates/>
				</pst:template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="psp:content">
		<xsl:choose>
			<xsl:when test="../@template">
				<pst:content>
					<xsl:apply-templates/>
				</pst:content>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="psp:element">
		<xsl:element name="{@name}" prefix="{@prefix}" uri="{@uri}">
			<xsl:for-each select="psp:attribute">
				<xsl:attribute name="{@name}" prefix="{@prefix}" uri="{@uri}">
					<xsl:apply-templates/>
				</xsl:attribute>
			</xsl:for-each>
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="psp:attribute"/>

	<xsl:template match="psp:expr">
		<!--
		<xsl:variable name="content">
			<xsl:for-each select="text()|psp:expr|psp:text">
				<xsl:choose>
					<xsl:when test="name(.)='psp:expr'">
						( <xsl:value-of select="."/>)
					</xsl:when>
					<xsl:otherwise>
						"<xsl:value-of select="."/>"
					</xsl:otherwise>
				</xsl:choose>
				.
			</xsl:for-each>
			""
		</xsl:variable>
		-->
		<xsl:value-of select="php:function('psp_expr', string(.))"/>
	</xsl:template>



	<!--
		<psp:if>
			<psp:expr>
			<psp:then/>
			<psp:else/>
		</psp:if>

		or

		<psp:if ( action="" | ... )>
			( then | ( <psp:then/> <psp:else/> ) )
		</psp:if>

		So, if there is <psp:expr>, either or both of <psp:then/> and <psp:else/>.
		If there is no <psp:expr>, the body is treated as <psp:then/> unless
		there is <psp:then> or <psp:else> present.

	-->
	<xsl:template match="psp:if">
		<xsl:variable name="cond">
			<xsl:choose>
				<xsl:when test="psp:expr">
					<xsl:apply-templates select="psp:expr"/>
				</xsl:when>
				<xsl:when test="@action">
					<xsl:value-of select="php:function('psp_isaction', string(@action))"/>
				</xsl:when>
				<xsl:when test="@expr" select="php:function('psp_expr', string(@expr))"/>
				<xsl:otherwise>false</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>


		<!-- check psp:if format: -->
		<xsl:choose>
			<!-- <psp:if> <psp:then/> <psp:else/> </psp:if> -->
			<xsl:when test="psp:expr | psp:then | psp:else">

				<xsl:choose>
					<xsl:when test="$cond = 'true'">
						<xsl:apply-templates select="psp:then"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="psp:else"/>
					</xsl:otherwise>
				</xsl:choose>

			</xsl:when>

			<!-- <psp:if> then </psp:if> -->
			<xsl:when test="$cond = 'true'">
				<xsl:apply-templates/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="psp:then | psp:else">
		<xsl:apply-templates/>
	</xsl:template>

<!--
	<xsl:template match="processing-instruction('xml-stylesheet')">
		<xsl:value-of select="php:function('psp_addXSL', substring-before( substring-after(., 'href=&#34;'), '&#34;') )"/>
	</xsl:template>
-->

	<xsl:template match="//processing-instruction()">
		<!--
		<xsl:value-of select="php:function('psp_module', substring-before( substring-after(., 'module=&#34;'), '&#34;') )"/>
		-->
		<xsl:value-of select="php:function('psp_pi', name(.), string(.))"/>
	</xsl:template>


	<xsl:template match="psp:messages">
		<xsl:copy-of select="php:function('psp_messages')"/>
	</xsl:template>


	<!-- transitional -->
	<xsl:template match="xsp:*">
		<xsl:apply-templates/>
	</xsl:template>


  <xsl:template match="@*|node()" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
