<?xml version="1.0"?>

<!-- PHP Version -->


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xsp="http://neonics.com/2001/xsp"
	exclude-result-prefixes="xsp"
>
	<xsl:output method="text"/>

	<xsl:template match="@*|node()|*">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
	</xsl:template>

</xsl:stylesheet>
