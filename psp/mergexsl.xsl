<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
	 
	 Blends two stylesheets into one.
	 Input format:
	 <xsl:stylesheets>
	 	<xsl:stylesheet/>
	 	<xsl:stylesheet/>
	 </xsl:stylesheets>

	 Latter ones with same template matches will override earlier ones.
  --> 

<!-- PHP Version -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
>
	<xsl:template match="/xsl:merge">
		<xsl:apply-templates select="xsl:node[1]"/>
	</xsl:template>

	<xsl:template match="xsl:node[position()&gt;1]">
		<xsl:apply-templates select="." mode="skipped"/>
	</xsl:template>

	<xsl:template match="xsl:node[1]">
		<xsl:apply-templates select="node()"/>
	</xsl:template>

	<xsl:template match="xsl:stylesheet">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()"/>

		<!-- this code will copy all templates that are not defined in the
			first template -->
			<xsl:variable name="s" select="."/>

			<xsl:for-each select="/xsl:merge/xsl:node[position()&gt;1]/xsl:stylesheet/xsl:template">
				<xsl:apply-templates select="." mode="skipped"/>
				<xsl:variable name="m" select="@match"/>
				<xsl:comment>
					!FOUND <xsl:value-of select="@match"/> <xsl:value-of select="@mode"/>
				</xsl:comment>
				<xsl:choose>
				<xsl:when test="not($s/xsl:template[@match=$m])">
					<xsl:comment>Not present in first</xsl:comment>
				</xsl:when>
				<xsl:otherwise>
				<xsl:comment>Present in first</xsl:comment>
				</xsl:otherwise>
				</xsl:choose>

				<xsl:choose>
					<xsl:when test="@mode">
						<xsl:variable name="t" select="@mode"/>
						<xsl:if test="not($s/xsl:template[@match=$m and @mode=$t])">
							<xsl:copy-of select="."/>
						</xsl:if>
					</xsl:when>
					<xsl:when test="not($s/xsl:template[@match=$m])">
						<xsl:copy-of select="."/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="." mode="skipped"/>
					</xsl:otherwise>
				</xsl:choose>

			</xsl:for-each>

		</xsl:copy>
	</xsl:template>

	<xsl:template match="xsl:template" mode="skipped">
		<xsl:comment> COMMENT
			Node: <xsl:value-of select="name(.)"/>
			NS:   <xsl:value-of select="namespace-uri(.)"/>
			<xsl:for-each select="@*">
			@<xsl:value-of select="name(.)"/>='<xsl:value-of select="."/>'
			<xsl:value-of select="*"/>
			</xsl:for-each>

		</xsl:comment>
	</xsl:template>

	<!-- this code will copy the first stylesheet's templates but replaces
		any templates with matching templates that are present in later stylesheets
	-->
	<!-- TODO: @priority -->
	<xsl:template match="xsl:template">
		<xsl:variable name="m" select="@match"/>
		<xsl:variable name="n" select="@name"/>
		<xsl:choose>
			<xsl:when test="@match">
				<xsl:choose>
					<xsl:when test="@mode">
						<xsl:variable name="t" select="@mode"/>
						<xsl:for-each select="/xsl:merge/xsl:node/xsl:stylesheet/xsl:template[@match=$m and @mode=$t]">
							<xsl:sort select="position()" data-type="number" order="descending"/>
							<xsl:choose>
								<xsl:when test="position()=1">
									<xsl:copy-of select="."/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:apply-templates select="." mode="skipped"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:for-each>
					</xsl:when>
					<xsl:otherwise>
						<xsl:for-each select="/xsl:merge/xsl:node/xsl:stylesheet/xsl:template[@match=$m]">
							<xsl:sort select="position()" data-type="number" order="descending"/>
							<xsl:choose>
								<xsl:when test="position()=1">
									<xsl:copy-of select="."/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:apply-templates select="." mode="skipped"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:for-each>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="@name">
				<xsl:call-template name="copy1">
					<xsl:with-param name="set" select="/xsl:merge/xsl:node/xsl:stylesheet/xsl:template[@name=$n]"/>
				</xsl:call-template>
			</xsl:when>

		</xsl:choose>
	</xsl:template>

	<xsl:template name="copy1">
		<xsl:param name="set"/>
		<xsl:for-each select="$set">
			<xsl:sort select="position()" data-type="number" order="descending"/>
			<xsl:choose>
				<xsl:when test="position()=1">
					<xsl:copy-of select="."/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="." mode="skipped"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>


	<xsl:template match="processing-instruction()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()|text()"/>
		</xsl:copy>
	</xsl:template>


</xsl:stylesheet>
