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
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:xi="http://www.w3.org/2001/XInclude"
>
<!-- exclude-result-prefixes="psp" -->

	<!--
	<xsl:strip-space elements="xsl:* psp:* xsp:*"/>
	<xsl:preserve-space elements="xsl:text"/>
	-->

	<xsl:template match="psp:page | xsp:page">
		<xsl:choose>
			<xsl:when test="@template">
				<xsl:value-of select="php:function('psp_module', 'template')"/>
				<pst:template name="{@template}">
					<xsl:apply-templates select="@*|*"/>
				</pst:template>
			</xsl:when>
			<xsl:otherwise>
				<l:page>
					<xsl:apply-templates/>
				</l:page>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="psp:content">
		<xsl:choose>
			<xsl:when test="../@template">
				<pst:content>
					<xsl:apply-templates select="@*|*"/>
				</pst:content>
			</xsl:when>
			<xsl:otherwise>
				<l:content>
					<xsl:apply-templates select="@*|*"/>
				</l:content>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="psp:element">
		<xsl:element name="{@name}" prefix="{@prefix}" uri="{@uri}">
			<xsl:apply-templates select="psp:attribute|*"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="psp:attribute">
		<xsl:attribute name="{@name}" prefix="{@prefix}" uri="{@uri}">
			<xsl:apply-templates/>
		</xsl:attribute>
	</xsl:template>


	<xsl:template match="psp:arg">
		<xsl:value-of select="php:function('psp_arg', string(.))"/>
	</xsl:template>

	<xsl:template match="psp:expr" mode="psp"><!-- for recursive copy/apply-->
		<xsl:call-template name="handle_psp_expr"/>
	</xsl:template>

	<xsl:template match="psp:expr">
		<xsl:call-template name="handle_psp_expr"/>
	</xsl:template>


	<xsl:template name="handle_psp_expr">
		<xsl:choose>
			<xsl:when test="@slasharg and @one">
				<xsl:value-of select="php:function('psp_slasharg', string(@slasharg), string(@one))"/>
			</xsl:when>
			<xsl:when test="@slasharg">
				<xsl:value-of select="php:function('psp_slasharg', string(@slasharg))"/>
			</xsl:when>

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
			<xsl:otherwise>
				<xsl:value-of select="php:function('psp_expr', string(.))"/>
			</xsl:otherwise>
		</xsl:choose>
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
			<xsl:call-template name="eval-expr"/>
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

	<xsl:template match="psp:switch">
		<xsl:comment>
			<xsl:value-of select="php:function('psp_variable', concat('switch-',generate-id()), 'false')"/>
		</xsl:comment>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="psp:switch/psp:case">
		<xsl:variable name="cond">
			<xsl:call-template name="eval-expr"/>
		</xsl:variable>
		<xsl:if test="$cond = 'true' and php:function('psp_variable', concat('switch-',generate-id(..)))= 'false'">
			<xsl:comment>
				<xsl:value-of select="php:function('psp_variable', concat('switch-',generate-id(..)), 'true')"/>
			</xsl:comment>
			<xsl:apply-templates/>
		</xsl:if>
	</xsl:template>

	<xsl:template match="psp:switch/psp:default">
		<xsl:if test="php:function('psp_variable', concat('switch-',generate-id(..)))='false'">
			<xsl:comment>
				<xsl:value-of select="php:function('psp_variable', concat('switch-',generate-id(..)), 'true')"/>
			</xsl:comment>
			<xsl:apply-templates/>
		</xsl:if>
	</xsl:template>

<!--
	<xsl:template match="psp:variable">
		<xsl:comment>
		<xsl:value-of select="php:function('psp_variable', concat('-',string(@name)), string(@value))"/>
		</xsl:comment>
	</xsl:template>
	-->

	<xsl:template name="eval-expr">
		<xsl:choose>
			<xsl:when test="psp:expr">
				<xsl:apply-templates select="psp:expr"/>
			</xsl:when>
			<xsl:when test="@action">
				<xsl:value-of select="php:function('psp_isaction', string(@action))"/>
			</xsl:when>
			<xsl:when test="@expr">
				<xsl:value-of select="php:function('psp_expr', string(@expr))"/>
			</xsl:when>
			<xsl:when test="@arg">
				<xsl:value-of select="php:function('psp_arg', string(@arg)) != ''"/>
			</xsl:when>
			<xsl:when test="@slashpath">
				<xsl:value-of select="php:function('psp_slashpath', string(@slashpath)) != ''"/>
			</xsl:when>
			<xsl:otherwise>false</xsl:otherwise>
		</xsl:choose>
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

	<xsl:template match="psp:module">
		<!--
		<xsl:variable name="arg"><xsl:apply-templates select="." mode="psp"/></xsl:variable>
		using $arg as last param converts it to string.....
		-->
		<xsl:value-of select="php:function('psp_module', string(@name), .)"/>
	</xsl:template>

	<xsl:template match="psp:xsl-include">
		<xsl:element name="xsl:include">
			<xsl:attribute name="href">
				<xsl:value-of select="php:function('psp_xsl_uri', string(@href))"/>
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<xsl:template match="psp:xsl-import">
		<xsl:element name="xsl:import">
			<xsl:attribute name="href">
				<xsl:value-of select="php:function('psp_xsl_uri', string(@href))"/>
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<xsl:template match="xi:include">
		<xsl:copy>
			<xsl:attribute name="href">
				<xsl:value-of select="php:function('psp_xml_uri', string(@href), 'content')"/>
			</xsl:attribute>
			<xsl:apply-templates select="@*[not(name(.)='href')]"/>
		</xsl:copy>
	</xsl:template>

	<xsl:template match="psp:messages">
		<xsl:apply-templates select="php:function('psp_messages', string(@module))" mode="psp">
			<xsl:with-param name="node" select="."/>
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="l:messages" mode="psp">
		<xsl:param name="node" select="."/>
		<xsl:copy>
			<xsl:apply-templates select="@class|@style|@id"/>

			<xsl:choose>
				<xsl:when test="$node/@module">
					<xsl:apply-templates select="l:message[@module=$node/@module]"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="l:message"/>
				</xsl:otherwise>
			</xsl:choose>

		</xsl:copy>
	</xsl:template>

	<xsl:template match="l:message" mode="psp">
		<xsl:param name="node" select="."/>
		<xsl:copy>
			<xsl:apply-templates select="l:message" mode="psp">
				<xsl:with-param name="node" select="$node"/>
			</xsl:apply-templates>
		</xsl:copy>
	</xsl:template>


  <xsl:template match="@*|node()" mode="psp">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()" mode="psp"/>
    </xsl:copy>
  </xsl:template>




	<xsl:template match="psp:accessLogs">
		<xsl:apply-templates select="php:function('psp_accessLogs')" mode="logs"/>
	</xsl:template>

	<xsl:template match="psp:accessLogs" mode="logs">
		<l:table class="accessLogs">
			<l:tr>
				<l:th>Time</l:th>
				<l:th>Remote</l:th>
				<l:th>Referer</l:th>
				<l:th>Host</l:th>
				<l:th>M</l:th>
				<l:th>URI</l:th>
				<l:th>Protocol</l:th>
				<l:th>User Agent</l:th>
			</l:tr>
			<xsl:for-each select="*">
				<xsl:if test="contains( @uri, '.html' )">
				<l:tr>
					<l:td><xsl:value-of select="@time"/></l:td>
					<l:td><xsl:value-of select="@remote"/></l:td>
					<l:td><xsl:value-of select="@referer"/></l:td>
					<l:td><xsl:value-of select="@host"/></l:td>
					<l:td><xsl:value-of select="name(.)"/></l:td>
					<l:td><xsl:value-of select="@uri"/></l:td>
					<l:td><xsl:value-of select="@protocol"/></l:td>
					<l:td><xsl:value-of select="@agent"/></l:td>
				</l:tr>
				</xsl:if>
			</xsl:for-each>
		</l:table>
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
