<?xml version="1.0"?>

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xsp="http://neonics.com/2001/xsp"
	exclude-result-prefixes="xsp"
>


	<!-- this code is run at generation-time -->
	<xsl:template match="xsp:run-code"
		xmlns:php="http://php.net/xsl" 
	>
		<xsl:variable name="code">
			<xsl:apply-templates/>
		</xsl:variable>
		// Gen-time executed CODE result:
		{
		<xsl:value-of select="php:function('runCode', string($code))"/>
		}
		// Gen-time end
	</xsl:template>

</xsl:stylesheet>
