<?php
/**
 * @author Kenney Westerhof <kenney@neonics.com>
 */

namespace template;

class AuthFilter
{

	public static function filter( $d )
	{
    $doc = self::authFilter( self::loadHTMLDoc( $d ) );

		$result = null;

		foreach ( $doc->documentElement->childNodes as $c )
			if ( $c->nodeType == XML_ELEMENT_NODE && $c->tagName == 'body')
				foreach ( $c->childNodes as $c )
					if ( $c->nodeType == XML_ELEMENT_NODE )
						$result .= $doc->saveHTML( $c );

		if ( !$result )
			trigger_error(__METHOD__ . ": cannot find /html/body/*[0]");

		return $result;
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
		if ( ! \ModuleManager::isModuleLoaded( 'auth' ) )
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

				array_merge(
					$x=array_map( function($v){return "core/$v";}, gd_( auth_roles( 'core' ), [] ) ),
					array_map( function($v){return auth_realm()."/$v";}, gd_( auth_roles(), [] ) ),	# XXX! must set a default realm with auth_realm()
					gd_( auth_roles(), [] )	# XXX! must set a default realm with auth_realm(); this does NOT use prefix checking! security risk!
				)
			)

		);

		# transform
    $template = <<<XSL
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
>
  <xsl:template match="*[@data-auth-role]">
		<xsl:choose>
			$case_roles
			<xsl:when test="false"><!-- just so it won't break when there are no roles --></xsl:when>
			<xsl:otherwise>
				<!-- no role, we don't:
				<xsl:copy>
					<xsl:apply-templates/>
				</xsl:copy>
				-->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

  <xsl:template match="*[@data-auth-permission]">
		<xsl:choose>
			<xsl:when test="php:function('auth_permission', string(@data-auth-permission))">
				<xsl:copy>
					<xsl:apply-templates select="@*|node()"/>
				</xsl:copy>
			</xsl:when>
			<xsl:otherwise>
				<!-- debug
				<xsl:copy>
					<xsl:apply-templates select="@*"/>
					<div style='color:red;background-color;white'>No permission <xsl:value-of select="@data-auth-permission"/>
						<xsl:apply-templates select="node()"/>
					</div>
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
		\ModuleManager::registerFunctions( $xslt );
    $dd = new \DOMDocument();
    $dd->loadXML( $template );
    $xslt->importStylesheet( $dd );
		return $xslt->transformToDoc( $doc );
	}

}

