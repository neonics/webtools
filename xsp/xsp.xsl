<?xml version="1.0"?>

<!-- PHP Version -->


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xsp="http://neonics.com/2001/xsp"
	exclude-result-prefixes="xsp"
>
	<xsl:import href="preprocessor.xsl"/>


	<xsl:strip-space elements="xsl:* xsp:*"/>
	<xsl:output method="text"/>

	<xsl:template match="xsp:page"><xsl:text disable-output-escaping="yes">&lt;?php</xsl:text>

		$stylesheets = array( "style/layout.xsl" );

		<xsl:for-each select="//processing-instruction('xml-stylesheet')">
			<!-- string matching.. the content is in '.': href="foo" -->
			array_unshift( $stylesheets, "<xsl:value-of select="substring-before( substring-after(., 'href=&#34;'), '&#34;')"/>" );
		</xsl:for-each>

		function __autoload($classname)
		{
			include $classname . '.php';
		}
		
		// user imports
		<xsl:for-each select="xsp:structure/xsp:include">
		include "<xsl:value-of select="text()"/>.php";
		</xsl:for-each>
		// end of user imports


		// User class level definitions
		<xsl:apply-templates select="xsp:logic"/>

		class XSPGenerator
		{
			
			public $contenthandler;

			public function __construct()
			{
				$this->contenthandler= new SAXHandler();
			}

			function init()
			{
				<xsl:apply-templates select="xsp:init"/>
			}

			function deinit()
			{
				<xsl:apply-templates select="xsp:deinit"/>
			}

			function generate()
			{
				global $stylesheets;

			<xsl:text disable-output-escaping="yes">
				$this->init();
			</xsl:text>

				// generated code {
				<xsl:call-template name="process-first-element">
					<xsl:with-param name="content" select="*[not(starts-with(name(.),'xsp:'))][1]"/>
				</xsl:call-template>
				/// } generated code

			<xsl:text disable-output-escaping="yes">
				$this->deinit();

				return $this->contenthandler->dom;
			</xsl:text>
			}
		}


		<xsl:text disable-output-escaping="yes">
		// MAIN PIPELINE //


		// Content
		$gen = new XSPGenerator();
		$doc = $gen->generate();
		$doc->xinclude();

		// Style


		foreach ( $stylesheets as $sheet )
		{
			$xslt = new XSLTProcessor();
			
			$sdoc = new DOMDocument();
			$sdoc->load( $sheet );
			$xslt->importStylesheet( $sdoc );

			$doc = $xslt->transformToDoc( $doc );
		}

		// Serialize
		$doc->formatOutput = true;
		echo trim( $doc->saveXml() );
		?>
		</xsl:text>
	</xsl:template>



	<!-- takes care of first-element processing, 
		typically xsp:logic in xsp:structure -->

	<xsl:template name="process-first-element">
		<xsl:param name="content"/>
		
		<!-- jat van cocoon -->
		<xsl:variable name="parent-element" select="$content/.."/>
		<xsl:for-each select="$content/namespace::*">
			<xsl:variable name="ns-prefix" select="local-name(.)"/>
			<xsl:variable name="ns-uri" select="string(.)"/>
			<!-- Declare namespaces that also exist on the parent (i.e. not
						locally declared) -->

			// name: <xsl:value-of select="name(.)"/>
			// ns-prefix: <xsl:value-of select="$ns-prefix"/>
			// ns-uri: <xsl:value-of select="$ns-uri"/>

			<xsl:if test="($ns-prefix != 'xmlns') and $parent-element/namespace::*[local-name(.) = $ns-prefix and string(.) = $ns-uri]">
				<xsl:text disable-output-escaping="yes">
				$this->contenthandler->startPrefixMapping(
				</xsl:text>
					"<xsl:value-of select="$ns-prefix"/>",
					"<xsl:value-of select="$ns-uri"/>"
				);
			</xsl:if>
		</xsl:for-each>

		<!-- generate content -->
		// THE CONTENT {
		<xsl:apply-templates select="$content"/>
		// } THE CONTENT

		<!-- close the namespaces -->
	 <xsl:for-each select="$content/namespace::*">
			<xsl:variable name="ns-prefix" select="local-name(.)"/>
			<xsl:variable name="ns-uri" select="string(.)"/>
			<xsl:if test="$parent-element/namespace::*[local-name(.) = $ns-prefix and string(.) = $ns-uri]">
			<xsl:text disable-output-escaping="yes">
			$this->contenthandler->endPrefixMapping(
			</xsl:text>
				"<xsl:value-of select="local-name(.)"/>"
			);
			</xsl:if>
		</xsl:for-each>
	</xsl:template>


	<xsl:template match="xsp:logic">
		<xsl:apply-templates/>
	</xsl:template>




	<!-- processes normal elements -->

	<xsl:template match="*[not(starts-with(name(.), 'xsp:'))]">
	<!--and not(starts-with(name(.), 'xsl:'))]">-->
		<xsl:call-template name="start-element-prefix-mapping"/>

		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->startElement("</xsl:text><xsl:value-of select="namespace-uri(.)"/>", "<xsl:value-of select="local-name(.)"/>", "<xsl:value-of select="name(.)"/>");
		<xsl:apply-templates select="@*"/>
		<xsl:apply-templates select="xsp:attribute | xsp:logic[xsp:attribute]"/>

		<xsl:apply-templates select="node()[not(name(.)='xsp:attribute' or (name(.)='xsp:logic' and ./xsp:attribute))]"/>
	
		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->endElement("</xsl:text><xsl:value-of select="namespace-uri(.)"/>", "<xsl:value-of select="local-name(.)"/>", "<xsl:value-of select="name(.)"/>");

		<xsl:call-template name="end-element-prefix-mapping"/>
	</xsl:template>



	<!-- processes raw text data; checks for logic and content -->

	<xsl:template match="text()">
		<xsl:choose>
			<xsl:when test="name(..) = 'xsp:logic' or name(..) = 'xsp:expr'">
			<xsl:value-of select="."/>
			</xsl:when>
			<xsl:otherwise>
			<xsl:text disable-output-escaping="yes">
			$this->contenthandler->characters("</xsl:text><xsl:call-template name="filter-ascii"><xsl:with-param name="content" select="string(.)"/></xsl:call-template>");
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- hmm... -->
	<xsl:template match="text()" mode="value">
		<xsl:value-of select="."/>
	</xsl:template>


	<!-- processes normal attributes -->
	<xsl:template match="@*"><!-- Filter out namespace declaration attributes 
		<xsl:if test="not(starts-with(name(.), 'xmlns:'))">
		hell why?-->
		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->addAttribute("</xsl:text><xsl:value-of select="namespace-uri(.)"/>", "<xsl:value-of select="local-name(.)"/>", "<xsl:value-of select="name(.)"/>", "<xsl:value-of select="."/>");
		<!--
		</xsl:if>
		-->
	</xsl:template>


	<!-- processes XSP expr -->
	<!-- HENK -->
	<xsl:template match="xsp:expr">
		<xsl:choose>

			<xsl:when test="starts-with(name(..), 'xsp:') and name(..) != 'xsp:content' and name(..) != 'xsp:element'">
				<!-- expression is nested within XSP tag, so preserve java type -->
				<!--(<xsl:value-of select="."/>)-->
				<xsl:text disable-output-escaping="yes">
				$this->contenthandler->characters(</xsl:text><xsl:value-of select="."/>);
			</xsl:when>
			<xsl:when test="string-length(.)=0">
				<!-- do nothing -->
			</xsl:when>
			<xsl:otherwise>
				<!--
					Output the value as either elements or char data..
					XSPObjectHelper.xspExpr(contenthandler, <xsl:value-of select="."/>);
				-->
				// for now, just char data.. ;)
				<xsl:text disable-output-escaping="yes">
				$this->contenthandler->characters(</xsl:text><xsl:value-of select="."/>);
			</xsl:otherwise>

		</xsl:choose>
	</xsl:template>



	<!-- handles xsp:element -->
	<xsl:template match="xsp:element">
		<xsl:variable name="uri">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">uri</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="prefix">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">prefix</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="name">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">name</xsl:with-param>
				<xsl:with-param name="required">true</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="raw-name">
			<xsl:if test="($uri=&quot;&quot; and $prefix!='&quot;&quot;') or ($uri != '&quot;&quot;' and $prefix='&quot;&quot;')">
				<xsl:call-template name="error">
					<xsl:with-param name="message">[&lt;xsp:element&gt;] Either both 'uri' and 'prefix' or none of them should be specified
					</xsl:with-param>
				</xsl:call-template>
			</xsl:if>

			<xsl:choose>
				<xsl:when test="$prefix='&quot;&quot;'">
					<xsl:copy-of select="$name"/>
				</xsl:when>
				<xsl:otherwise>
					"xsp:".<xsl:copy-of select="$name"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:call-template name="start-element-prefix-mapping"/>

		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->startElement(</xsl:text><xsl:copy-of select="$uri"/>, <xsl:copy-of select="$name"/>, <xsl:copy-of select="$raw-name"/>);

		<xsl:apply-templates select="xsp:attribute"/>

		<xsl:apply-templates select="node()[not(name(.)='xsp:attribute')]"/>

		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->endElement(</xsl:text><xsl:copy-of select="$uri"/>, <xsl:copy-of select="$name"/>, <xsl:copy-of select="$raw-name"/>);

		<xsl:call-template name="end-element-prefix-mapping"/>
	</xsl:template>



	<!-- handles xsp:attribute -->
	<xsl:template match="xsp:attribute">
		<xsl:variable name="uri">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">uri</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="prefix">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">prefix</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="name">
			<xsl:call-template name="get-parameter">
				<xsl:with-param name="name">name</xsl:with-param>
				<xsl:with-param name="required">true</xsl:with-param>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="raw-name">
			<xsl:if test="($uri=&quot;&quot; and $prefix!='&quot;&quot;') or ($uri != '&quot;&quot;' and $prefix='&quot;&quot;')">
				<xsl:call-template name="error">
					<xsl:with-param name="message">[&lt;xsp:element&gt;] Either both 'uri' and 'prefix' or none of them should be specified
					</xsl:with-param>
				</xsl:call-template>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="$prefix='&quot;&quot;'">
					<xsl:copy-of select="$name"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$prefix"/>.<xsl:copy-of select="$name"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<!--
		<xsl:apply-templates select="xsp:attribute"/>
		-->

		<xsl:variable name="content">
			<xsl:for-each select="text()|xsp:expr|xsp:text">
				/* cur = <xsl:value-of select="name(.)"/> */
				<xsl:choose>
					<xsl:when test="name(.)='xsp:expr'">
						/*String.valueOf*/(  <xsl:value-of select="."/>)
					</xsl:when>
					<xsl:otherwise>
						"<xsl:value-of select="."/>"
					</xsl:otherwise>
				</xsl:choose>
				.
			</xsl:for-each>
			""
		</xsl:variable>

		<xsl:text disable-output-escaping="yes">
		$this->contenthandler->addAttribute(</xsl:text><xsl:copy-of select="$uri"/>, <xsl:copy-of select="$name"/>, <xsl:copy-of select="$raw-name"/>, <xsl:value-of select="normalize-space($content)"/>);
	</xsl:template>



	<!-- UTILITY TEMPLATES -->

	<!-- creates the code for starting namespaces for normal elements -->
	<xsl:template name="start-element-prefix-mapping" mode="element">
		<xsl:variable name="parent-element" select=".."/>
		<xsl:for-each select="namespace::*">
			<xsl:variable name="ns-prefix" select="local-name(.)"/>
			<xsl:variable name="ns-uri" select="string(.)"/>
			<!-- Declare namespaces that also exist on the parent (i.e. not
						locally declared) -->
			<xsl:if test="not($parent-element/namespace::*[local-name(.) = $ns-prefix and string(.) = $ns-uri])">
				<xsl:text disable-output-escaping="yes">
				$this->contenthandler->startPrefixMapping(
				</xsl:text>
					"<xsl:value-of select="$ns-prefix"/>",
					"<xsl:value-of select="$ns-uri"/>"
				);
			</xsl:if>
		</xsl:for-each>
	</xsl:template>

	<!-- creates the code for ending namespaces for normal elements -->
	<xsl:template name="end-element-prefix-mapping">
		<xsl:variable name="parent-element" select=".."/> 
		<xsl:for-each select="namespace::*">
			<xsl:variable name="ns-prefix" select="local-name(.)"/>
			<xsl:variable name="ns-uri" select="string(.)"/>
			<xsl:if test="not($parent-element/namespace::*[local-name(.) = $ns-prefix and string(.) = $ns-uri])">
				<xsl:text disable-output-escaping="yes">
				$this->contenthandler->endPrefixMapping(
				</xsl:text>
					"<xsl:value-of select="local-name(.)"/>"
				);
			</xsl:if>
		</xsl:for-each>
	</xsl:template>


	<!-- transforms all \n to '\n' -->
	<xsl:template name="filter-ascii">
		<xsl:param name="content"/>

		<xsl:call-template name="filter-quote">
			<xsl:with-param name="content">

				<xsl:call-template name="filter-linefeed">
					<xsl:with-param name="content"><xsl:value-of select="$content"/></xsl:with-param>
				</xsl:call-template>

			</xsl:with-param>
		</xsl:call-template>

	</xsl:template>


	<xsl:template name="filter-linefeed">
		<xsl:param name="content"/>

		<!-- filters all \n -->
		<xsl:choose>
			<xsl:when test="contains($content, '&#xA;')">
				<xsl:call-template name="filter-linefeed">
					<xsl:with-param name="content"><xsl:value-of select="substring-before($content, '&#xA;')"/>\n<xsl:value-of select="substring-after($content, '&#xA;')"/></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$content"/>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>



	<xsl:template name="filter-quote">
		<xsl:param name="content"/>

		<!-- filters all " to \" -->
		<xsl:choose>
			<xsl:when test="contains($content, '&#x22;')">
				<xsl:value-of select="substring-before($content, '&#x22;')"/>\"<xsl:call-template name="filter-quote">
					<xsl:with-param name="content"><xsl:value-of select="substring-after($content, '&#x22;')"/></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$content"/>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<!-- get a parameter for xsp code -->
	<xsl:template name="get-parameter">
		<xsl:param name="name"/>
		<xsl:param name="default"/>
		<xsl:param name="required">false</xsl:param>

		<xsl:variable name="qname">
			<xsl:value-of select="string('xsp:param')"/>
		</xsl:variable>

		<xsl:choose>
			<xsl:when test="@*[name(.) = $name]">"<xsl:value-of select="@*[name(.) = $name]"/>"</xsl:when>
			<xsl:when test="(*[name(.) = $qname])[@name = $name]">
				<xsl:call-template name="get-nested-content">
					<xsl:with-param name="content"
													select="(*[name(.) = $qname])[@name = $name]"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="string-length($default) = 0">
						<xsl:choose>
							<xsl:when test="$required = 'true'">
								<xsl:call-template name="error">
									<xsl:with-param name="message">[Logicsheet processor]
Parameter '<xsl:value-of select="$name"/>' missing in dynamic tag &lt;<xsl:value-of select="name(.)"/>&gt;
									</xsl:with-param>
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>""</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise><xsl:copy-of select="$default"/></xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>



	<!-- get all content as a string -->
	<xsl:template name="get-nested-content">
		<xsl:param name="content"/>
		<xsl:choose>
			<xsl:when test="$content/*">
				<xsl:apply-templates select="$content/*"/>
			</xsl:when>
			<xsl:otherwise>"<xsl:value-of select="$content"/>"</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- terminate with a message -->
	<xsl:template name="error">
		<xsl:param name="message"/>
		<xsl:message terminate="yes"><xsl:value-of select="$message"/></xsl:message>
	</xsl:template>

	<!-- Ignored elements -->
	<xsl:template match="xsp:logicsheet|xsp:dependency|xsp:param"/>


	<!-- blijkbaar xsp:content vergeten... -->
	<xsl:template match="xsp:content">
		<xsl:apply-templates/>
	</xsl:template>

</xsl:stylesheet>
