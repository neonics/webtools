<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:cv="http://www.neonics.com/2004/cv"
	xmlns="http://www.neonics.com/xslt/layout/1.0"
	exclude-result-prefixes="cv"
>
	<xsl:param name="lang" select="'en'"/>

	<xsl:template match="cv:menu">
		<menu class="vmenu">
			<xsl:for-each select="//cv:cv/*[not(local-name(.)='labels')]">
				<item href="#{local-name(.)}">
					<xsl:value-of select="local-name(.)"/>
				</item>
			</xsl:for-each>
		</menu>
	</xsl:template>

	<xsl:template match="cv:cv">
		<h1><xsl:value-of select="@*[name(.)=concat('title.', $lang)]"/></h1>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="cv:personalia">
		<a name="#{personalia}"/>
		<h2>Personalia</h2>
		<!--xsl:call-template name="attrToTable"/-->
		<div>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="address">
		<xsl:call-template name="attrToTable"/>
	</xsl:template>

	<xsl:template match="phone">
		<xsl:call-template name="attrToTable"/>
	</xsl:template>

	<!-- Jobs -->

	<xsl:template match="cv:jobs">
		<a name="#{jobs}"/>
		<div>
			<h2>Jobs</h2>
			<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="cv:jobs/cv:job">
		<h3><xsl:value-of select="@company"/>, <xsl:value-of select="@location"/></h3>
		<div>
			<table>
				<tr><td>Company</td><td><xsl:value-of select="@company"/></td></tr>
				<tr><td>Location</td><td><xsl:value-of select="@location"/></td></tr>
				<tr><td>Website</td><td><xsl:value-of select="@url"/></td></tr>
				<tr><td>Start</td><td><xsl:value-of select="@start-date"/></td></tr>
				<tr><td>End</td><td><xsl:value-of select="@end-date"/></td></tr>
				<tr><td>Branche</td><td><xsl:value-of select="@branche"/></td></tr>
				<tr><td>Dienstverband</td><td><xsl:value-of select="@dienstverband"/></td></tr>
			</table>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="cv:achievements">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="cv:achievements/cv:achievement">
		<h4><xsl:value-of select="@client"/></h4>
		<div>
		<xsl:call-template name="attrToTable"/>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="cv:achievement/cv:functions">
		<xsl:call-template name="attrToTable"/>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="cv:function">
		<div>
		<xsl:call-template name="attrToTable"/>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="cv:description">
		<div>
			<xsl:variable name="l" select="@xml:lang"/>
			<i><xsl:value-of select="//cv:labels/cv:label[@name=$l][@xml:lang=$lang]"/></i>
			<br/>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<!-- Education -->

	<xsl:template match="cv:cv/cv:education">
		<a name="education"/>
		<div>
		<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="cv:education/cv:education">
		<dl>
		<xsl:apply-templates/>
		</dl>
	</xsl:template>

	<xsl:template match="cv:education/cv:vak">
		<dt><xsl:value-of select="@name"/></dt>
		<dd><xsl:value-of select="@grade"/></dd>
		<xsl:apply-templates/>
	</xsl:template>

	<!-- Skills -->

	<xsl:template match="cv:skills">
		<a name="skills"/>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="cv:skillgroup">
		<table>
			<tr>
				<th><xsl:value-of select="@category"/></th>
			</tr>
		<xsl:apply-templates/>
		</table>
	</xsl:template>

	<xsl:template match="cv:skill">
		<tr>
			<td><xsl:value-of select="@type"/></td>
			<td><xsl:value-of select="@name"/></td>
			<td><xsl:value-of select="@level"/></td>
		</tr>
	</xsl:template>



	<!-- labels -->

	<xsl:template match="cv:cv/cv:labels"/>
	<xsl:template match="cv:cv/cv:labels/cv:label"/>

	<xsl:template name="getLabel">
		<xsl:param name="name" select="'UNDEF'"/>
		<xsl:choose>
			<xsl:when test="//labels/label[@name=$name][@xml:lang=$lang]">
				<xsl:value-of select="//labels/label[@name=$name][@xml:lang=$lang]"/>
			</xsl:when>
			<xsl:otherwise>
				[NOT FOUND: (<xsl:value-of select="$lang"/>) <xsl:value-of select="$name"/>]
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="attrToTable">
		<table>
			<xsl:for-each select="@*">
				<tr>
					<td><xsl:value-of select="name(.)"/></td>
					<td><xsl:apply-templates/></td>
				</tr>
			</xsl:for-each>
		</table>
	</xsl:template>

  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
