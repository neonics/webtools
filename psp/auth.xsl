<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>

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
	xmlns:php="http://php.net/xsl" 
	exclude-result-prefixes="auth"
>

	<xsl:template match="auth:menu">

		<auth:user
			xmlns="http://www.neonics.com/xslt/layout/1.0"
		>
			<auth:success>
		<xsl:message>auth:user success</xsl:message>
				<menu class="vmenu">
					<item class="menutitle">
						<label>Welcome, <auth:username/></label>
					</item>
					<item>
						Roles: <auth:roles/>
					</item>
					<item action="auth:logout">
						<label xml:lang="en">Logout</label>
					</item>
				</menu>
			</auth:success>
			<auth:fail>
		<xsl:message>auth:user fail</xsl:message>
				<menu class="vmenu">
					<!--
					<item><auth:login/></item>
					-->
					<item page="index" action="auth:show-login">Login</item>
				</menu>
			</auth:fail>
		</auth:user>

	</xsl:template>


	<xsl:template match="auth:numusers">
		<xsl:value-of select="php:function('auth_numusers')"/>
	</xsl:template>

	<xsl:template match="auth:listUsers">
		<xsl:apply-templates select="php:function('auth_listUsers')" mode="list"/>
	</xsl:template>

	<xsl:template match="auth:listRoles">
		<xsl:apply-templates select="php:function('auth_listRoles')" mode="list"/>
	</xsl:template>

	<!-- render permission denied even in list mode -->
	<xsl:template match="l:message" mode="list">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="auth:auth" mode="list">
		<table>
			<tr><th>Users</th><th>Roles</th></tr>
			<xsl:apply-templates mode="list"/>
		</table>
	</xsl:template>

	<xsl:template match="auth:user" mode="list">
		<tr>
			<td><xsl:value-of select="@username"/></td>
			<td><xsl:value-of select="@roles"/></td>
		</tr>
	</xsl:template>


	<xsl:template match="auth:firstrun">
		<xsl:call-template name="ifelse">
			<xsl:with-param name="condition" select="php:function('auth_firstrun')"/>
		</xsl:call-template>
	</xsl:template>

  <xsl:template match="auth:user">
		<xsl:call-template name="ifelse">
			<xsl:with-param name="condition" select="php:function('auth_user')"/>
		</xsl:call-template>
  </xsl:template>

  <xsl:template match="auth:role">
		<xsl:call-template name="ifelse">
			<xsl:with-param name="condition" select="php:function('auth_role', string(@role))"/>
		</xsl:call-template>
  </xsl:template>

  <xsl:template match="auth:permission">
		<xsl:call-template name="ifelse">
			<xsl:with-param name="condition" select="php:function('auth_permission', string(@permission))"/>
		</xsl:call-template>
  </xsl:template>

	<xsl:template name="ifelse">
		<xsl:param name="condition"/>
		<xsl:choose>

			<xsl:when test="auth:success | auth:fail">
				<xsl:choose>
					<xsl:when test="$condition">
						<xsl:apply-templates select="auth:success"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="auth:fail"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>

			<xsl:when test="$condition">
				<xsl:apply-templates/>
			</xsl:when>

		</xsl:choose>
	</xsl:template>

	<xsl:template match="auth:fail">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="auth:success">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="auth:login">
		<l:login userfield="username" passfield="password">
			<xsl:apply-templates select="@*"/>
			<l:field type="hidden" name="action:auth:login"/>
			<l:field type="hidden" name="auth:challenge"
				value="{php:function('auth_challenge')}"/>
		</l:login>
	</xsl:template>

	<xsl:template match="auth:username">
		<xsl:value-of select="php:function('auth_username')"/>
	</xsl:template>

	<xsl:template match="auth:roles">
		<xsl:apply-templates select="php:function('auth_xml_roles')" mode="list"/>
	</xsl:template>

	<xsl:template match="auth:roles" mode="list">
		<ul>
			<xsl:for-each select="auth:role">
				<li><xsl:value-of select="@name"/></li>
			</xsl:for-each>
		</ul>
	</xsl:template>

  <xsl:template match="@*|node()" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
