<?xml version="1.0"?>

<!--

Offers:

	<auth:user>
		<auth:success>content when successful</auth:success>
		<auth:fail>content when fail</auth:fail>
	</auth:user>

	<auth:permission role="rolename">
		<auth:success/>
		<auth:fail/>
	</auth:permission>

	<auth:login/>
	
Produces:

	<auth:login> produces the layout form:

	<l:login userfield="X" passfield="Y" action="Z"/>

-->
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xsp="http://neonics.com/2001/xsp"
  xmlns:auth="http://neonics.com/2000/xsp/auth"
  xmlns:ns="http://neonics.com/2003/xsp/ns"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:html="http://www.w3.org/1999/xhtml"
>

  <xsl:template match="xsp:page">
    <xsp:page>
      <xsl:apply-templates select="@*"/>

      <xsp:structure>
        <xsp:include>Database</xsp:include>
      </xsp:structure>

      <xsp:init>
        <xsp:logic>
					session_start();

					foreach ($_POST as $k=>$v )
					{
					<xsp:content>
						<l:message type="debug">
							<xsp:expr>$k . "=>" . $v</xsp:expr>
						</l:message>
					</xsp:content>
					}


					if ( array_key_exists( "action:logout", $_POST ) )
					{
						unset( $_SESSION["auth.user"] );
					}

					if ( array_key_exists( "action:login", $_POST ) )
					{
						$_SESSION["auth.user"] = $_POST["username"];
					}

        </xsp:logic>
      </xsp:init>

      <xsl:apply-templates/>
    </xsp:page>
  </xsl:template>

  <xsl:template match="auth:user">
		<xsp:logic>
			if ( isset( $_SESSION["auth.user"] ) )
			{
				<xsp:content>
					<xsl:apply-templates select="auth:success"/>
				</xsp:content>
			}
			else
			{
				<xsp:content>
					<xsl:apply-templates select="auth:fail"/>
				</xsp:content>
			}

		</xsp:logic>
  </xsl:template>

	<xsl:template match="auth:fail">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="auth:success">
		<xsl:apply-templates/>
	</xsl:template>

  <xsl:template match="auth:permission">
		<xsp:logic>
			if ( isset( $_SESSION['auth.role'] ) &amp;&amp; $_SESSION['auth.role'] == "<xsl:value-of select="@role"/>" )
			{
				<xsp:content>
					<xsl:apply-templates select="auth:success"/>
				</xsp:content>
			}
			else
			{
				<xsp:content>
					<xsl:apply-templates select="auth:fail"/>
				</xsp:content>
			}
		</xsp:logic>
  </xsl:template>

	<xsl:template match="auth:login">
		<l:login userfield="username" passfield="password">
			<l:field type="hidden" name="action:login"/>
		</l:login>
	</xsl:template>

	<xsl:template match="auth:username">
		<xsp:expr>$_SESSION["auth.user"]</xsp:expr>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
