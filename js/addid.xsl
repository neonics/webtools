<?xml version="1.0"?>
<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>
	<xsl:template match="*">
		<xsl:copy>
			<xsl:if test="@id">
				<xsl:attribute name="OLD_id"><xsl:value-of select="@id"/></xsl:attribute>
			</xsl:if>
			<xsl:attribute name="id"><xsl:value-of select="generate-id()"/></xsl:attribute>
			<xsl:apply-templates select="@*[not(name()='id')]|node()"/>
		</xsl:copy>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>
</xsl:stylesheet>
