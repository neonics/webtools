<?xml version="1.0"?>
<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  --> 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>
	<xsl:template match="*">
		<xsl:copy>
			<xsl:if test="@OLD_id">
				<xsl:attribute name="id"><xsl:value-of select="@OLD_id"/></xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select="@*[not(name()='OLD_id' or name()='id')]|node()"/>
		</xsl:copy>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>
</xsl:stylesheet>
