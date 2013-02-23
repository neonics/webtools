<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
	 
	 Blends two stylesheets into one.
	 Input format:
	 <xsl:merge>
	 	<xs;:node> <xsl:stylesheet/> </xsl:node>
	 	<xs;:node> <xsl:stylesheet/> </xsl:node>
	 </xsl:merge>

	 Latter ones with same template matches will override earlier ones.
  --> 

<!-- PHP Version -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
>
	<xsl:template match="/xsl:merge">
		<xsl:comment>OVERVIEW</xsl:comment>
		<xsl:for-each select="xsl:node">
			<xsl:comment>  stylesheet <xsl:value-of select="position()"/></xsl:comment>
			<xsl:for-each select="xsl:stylesheet/xsl:template">
				<xsl:comment>    match=<xsl:value-of select="./@match"/> mode=<xsl:value-of select="./@mode"/></xsl:comment>
			</xsl:for-each>
		</xsl:for-each>

		<xsl:comment>second node BEGIN</xsl:comment>
		<xsl:apply-templates select="xsl:node[1]"/>
		<xsl:comment>second node END</xsl:comment>
	</xsl:template>

	<!-- doesn't seem to be called...-->
	<!--
	<xsl:template match="xsl:node[position()&gt;1]">
		<xsl:comment><xsl:value-of select="position()"/>'th node BEGIN</xsl:comment>
		<xsl:apply-templates select="." mode="skipped"/>
		<xsl:comment><xsl:value-of select="position()"/>'th node END</xsl:comment>
	</xsl:template>
	-->

	<xsl:template match="xsl:node[1]">
		<xsl:comment>first node BEGIN node</xsl:comment>
		<xsl:apply-templates select="node()"/>
		<xsl:comment>first node END node</xsl:comment>
	</xsl:template>

	<xsl:template match="xsl:stylesheet">
		<xsl:comment> STYLE SHEET </xsl:comment>
		<xsl:copy>
			<xsl:apply-templates select="@*|*"/>

			<xsl:comment>
			new stylesheet copy done.
			</xsl:comment>

			<!-- this code will copy all templates that are not defined in the
			first template -->
			<xsl:variable name="s" select="."/>

			<xsl:for-each select="/xsl:merge/xsl:node[position()&gt;1]/xsl:stylesheet/xsl:template">
				<!--<xsl:apply-templates select="." mode="skipped"/>-->
				<xsl:variable name="m" select="@match"/>
				<xsl:variable name="mode" select="@mode"/>
				<xsl:comment>
					!FOUND match=<xsl:value-of select="@match"/> mode=<xsl:value-of select="@mode"/>
				</xsl:comment>
				<xsl:choose>
					<xsl:when test="@mode and not($s/xsl:template[@match=$m and @mode=$mode])">
						<xsl:comment>[with mode] Not present in first</xsl:comment>
					</xsl:when>
					<xsl:when test="not($s/xsl:template[@match=$m])">
						<xsl:comment>Not present in first</xsl:comment>
					</xsl:when>
					<xsl:otherwise>
						<xsl:comment>Present in first</xsl:comment>
					</xsl:otherwise>
				</xsl:choose>


				<xsl:comment>APPLY BEGIN</xsl:comment>
				<xsl:choose>
					<xsl:when test="@mode and not($s/xsl:template[@match=$m and @mode=$mode])">
						<xsl:comment>[mode] copy</xsl:comment>
						<xsl:copy-of select="."/>
					</xsl:when>
					<xsl:when test="not($s/xsl:template[@match=$m])">
						<xsl:comment>copy</xsl:comment>
						<xsl:copy-of select="."/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:comment>skip</xsl:comment>
						<xsl:apply-templates select="." mode="skipped"/>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:comment>APPLY END</xsl:comment>

			</xsl:for-each>

		</xsl:copy>
	</xsl:template>

	<xsl:template match="xsl:template" mode="skipped">
		<xsl:comment> SKIPPED  <xsl:value-of select="count(parent::*/parent::*/preceding-sibling::*) + 0"/>
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
		<xsl:comment>original non-conflicting template
			match = <xsl:value-of select="@match"/>
			mode  = <xsl:value-of select="@mode"/>
			name  = <xsl:value-of select="@name"/>
			prio  = <xsl:value-of select="@priority"/>
		</xsl:comment>
		<xsl:variable name="m" select="@match"/>
		<xsl:variable name="n" select="@name"/>
		<xsl:variable name="t" select="@mode"/>
		<xsl:choose>
			<xsl:when test="@match">
				<xsl:choose>
					<xsl:when test="@mode">
						<xsl:comment>match and mode</xsl:comment>
						<xsl:for-each select="/xsl:merge/xsl:node/xsl:stylesheet/xsl:template[@match=$m and @mode=$t]">
							<xsl:sort select="position()" data-type="number" order="descending"/>
							<xsl:choose>
								<xsl:when test="position()=1">
									<xsl:copy-of select="."/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:comment>match and mode - position not 1 but <xsl:value-of select="position()"/></xsl:comment>
									<xsl:apply-templates select="." mode="skipped"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:for-each>
					</xsl:when>
					<xsl:otherwise>
						<xsl:comment>match</xsl:comment>
						<xsl:for-each select="/xsl:merge/xsl:node/xsl:stylesheet/xsl:template[not(@mode) and (@match=$m)]">
							<xsl:sort select="position()" data-type="number" order="descending"/>
							<xsl:choose>
								<xsl:when test="position()=1">
									<xsl:copy-of select="."/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:comment>match - position not 1 but <xsl:value-of select="position()"/></xsl:comment>
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

	<xsl:template match="xsl:param|processing-instruction()|xsl:import|xsl:include|xsl:output">
		<xsl:comment>FOO <xsl:value-of select="name()"/></xsl:comment>
		<xsl:apply-templates select="." mode="copy"/>
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

  <xsl:template match="@*|node()" priority="-1" mode="copy">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()" mode="copy"/>
    </xsl:copy>
  </xsl:template>



	<xsl:template match="processing-instruction()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()|text()"/>
		</xsl:copy>
	</xsl:template>


</xsl:stylesheet>
