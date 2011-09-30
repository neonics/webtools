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
	xmlns:email="http://neonics.com/2011/psp/email"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
>
<!-- exclude-result-prefixes="psp" -->

	<xsl:strip-space elements="xsl:* psp:* xsp:*"/>

	<xsl:template match="email:form">
		<l:form method="post">
			<l:table>
				<l:tr>
					<l:td><l:label for="name">Name</l:label></l:td>
					<l:td>
						<l:input size="40" type="text" id="name" name="email:sender:name">
						<xsl:attribute name="value">
							<xsl:value-of select="php:function('psp_arg', 'email:sender:name')"/>
						</xsl:attribute>
						</l:input>
					</l:td>
				</l:tr>
				<l:tr>
					<l:td><l:label for="email">E-Mail</l:label></l:td>
					<l:td><l:input size="40" type="text" id="email" name="email:sender:email">
						<xsl:attribute name="value">
							<xsl:value-of select="php:function('psp_arg', 'email:sender:name')"/>
						</xsl:attribute>
						</l:input>
					</l:td>

				</l:tr>
				<l:tr>
					<l:td><l:label for="subject">Subject</l:label></l:td>
					<l:td><l:input size="40" type="text" id="subject" name="email:subject">						<xsl:attribute name="value">
							<xsl:value-of select="php:function('psp_arg', 'email:sender:name')"/>
						</xsl:attribute>
						</l:input>
					</l:td>
				</l:tr>
				<l:tr>
					<l:td><l:label for="message">Message</l:label></l:td>
					<l:td>
						<l:textarea rows="20" cols="80" id="body" name="email:body">
							<xsl:value-of select="php:function('psp_arg', 'email:sender:name')"/>
						</l:textarea>
					</l:td>
				</l:tr>
				<l:tr>
					<l:td><l:input type="submit" name="action:email:send" value="Send"/></l:td>
				</l:tr>
			</l:table>
		</l:form>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
