<?php
/**
 * @author Kenney Westerhof <kenney@neonics.com>
 */

namespace template;

class AuthFilter
{

	public static function filter( $d )
	{
    return self::authFilter( self::loadHTMLDoc( $d ) )->saveHTML();
	}

	private static function loadHTMLDoc( $d )
	{
		// Parse the HTML so we can transform it

		$prev = libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$doc->loadHTML( $d );
		$html_parse_errors = libxml_get_errors(); // might complain about HTML 5 tags (nav etc)
		libxml_use_internal_errors( $prev );
		return $doc;
	}


	private static function authFilter( $doc )
	{
		\ModuleManager::loadModule('auth');

		// Construct an XSLT template to filter out parts the user is not authenticated for

		$case_roles = implode("\n",

			array_map(
				function($v) {
					return <<<XSL
						<xsl:when test="@data-auth-role='$v'">
							<xsl:copy>
								<xsl:apply-templates select="@*|node()"/>
							</xsl:copy>
						</xsl:when>
XSL;
				},
				auth_roles()
			)

		);

		# transform
    $template = <<<XSL
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>
  <xsl:template match="*[@data-auth-role]">
		<xsl:choose>
			$case_roles
			<xsl:when test="false"><!-- just so it won't break when there are roles --></xsl:when>
			<xsl:otherwise>
				<!-- no role, we don't:
				<xsl:copy>
					<xsl:apply-templates/>
				</xsl:copy>
				-->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


  <xsl:template match="@*|node()" priority="-2">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
XSL;

    $xslt = new \XSLTProcessor();
    $dd = new \DOMDocument();
    $dd->loadXML( $template );
    $xslt->importStylesheet( $dd );
		return $xslt->transformToDoc( $doc );
	}

}

