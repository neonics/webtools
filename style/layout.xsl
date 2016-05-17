<?xml version="1.0"?>

<!-- @author: Kenney Westerhof -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	xmlns:psp="http://neonics.com/2011/psp"
	exclude-result-prefixes="l"
>

	<xsl:include href="admin.xsl"/>
	<xsl:include href="menu.xsl"/>
	<xsl:include href="html.xsl"/>

	<xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />
	<!-- having them both for the time being - no warnings (yet xmlns xhtml is added!) -->
	<xsl:output method="html" version="5"
		encoding="utf-8" indent="yes" omit-xml-declaration="yes"
		doctype-system="about:legacy-compat" />

	<xsl:param name="psp:requestBaseURI" select="$requestBaseURI"/>
	<xsl:param name="psp:slashpage" select="$slashpage"/>
	<xsl:param name="psp:lang" select="$lang"/>

	<xsl:strip-space elements="xsl:*"/>

  <xsl:template match="l:page">
		<xsl:variable name="themecss"><xsl:if test="@css"><xsl:value-of select="@css"/>/</xsl:if></xsl:variable>
  	<html>
			<xsl:comment>Software author: Kenney Westerhof / Neonics.com </xsl:comment>
   		<head>
     		<title><xsl:value-of select="title"/></title>
     		<link rel="stylesheet" type="text/css" href="{$psp:requestBaseURI}css/{$themecss}layout.css"/>
     		<link rel="stylesheet" type="text/css" href="{$psp:requestBaseURI}css/{$themecss}style.css"/>
				<link rel="icon" href="{$psp:requestBaseURI}img/favicon.ico" type="image/x-icon"/>
				<script type="text/javascript" src="{$psp:requestBaseURI}js/script.js"/>
				<script type="text/javascript" src="{$psp:requestBaseURI}js/dragdrop.js"/>
			</head>
    	<xsl:apply-templates select="l:body"/>
  	</html>
  </xsl:template>

	<xsl:template match="l:body">
		<xsl:comment>core layout.xsl</xsl:comment>
    <body id="body">
			<xsl:apply-templates select="@*"/>
			<div id="main">
				<xsl:apply-templates select="l:menu"/>
				<table width="100%" border="0" padding="0" cellspacing="0">
					<tr>
						<td colspan="3">
							<xsl:apply-templates select="l:header"/>
						</td>
					</tr>
					<tr>

						<td class="left"><xsl:apply-templates select="l:box[@align='left']"/></td>
						<td>
							<xsl:apply-templates select="l:banner"/>
							<xsl:apply-templates select="l:content"/>
							<xsl:apply-templates select="l:edit"/>
						</td>

						<td class="right"><xsl:apply-templates select="l:box[@align='right']"/></td>
					</tr>
				</table>
			</div>

			<xsl:apply-templates select="l:footer"/>
			<!-- XXX TODO FIXME - enable for LIVE -->
			<!--
			<xsl:call-template name="statcounter"/>
			-->
			<!-- -->
    </body>
	</xsl:template>

	<xsl:template match="l:header|l:footer|l:banner">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="l:content">
		<div id="content">
			<xsl:apply-templates select="@*|*"/>
			<div class="copyright">
				<span>&#169; 2011. All Rights Reserved.</span>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="l:messagebox">
<!--		<xsl:if test="count($set)>0">-->
		<div class="messagebox">
			<!--
			<span class="messageboxtitle"><xsl:value-of select="$t"/></span>
			-->
			<xsl:apply-templates select="//l:message" mode="insert"/>
		</div>
<!--		</xsl:if>-->
	</xsl:template>

	<!--
	<xsl:template match="l:messagebox[@type]">
		<xsl:variable name="t" select="@type"/>
		<xsl:variable name="set" select="//l:message[@type=$t]"/>
<!- -		<xsl:if test="count($set)>0">- ->

		<div class="messagebox {$t}">

		TYPED MBOX
			<span class="messageboxtitle"><xsl:value-of select="$t"/></span>
			<xsl:apply-templates select="//l:message[@type=$t]" mode="insert"/>
		</div>
<!- -		</xsl:if>- ->
	</xsl:template>
	-->

	<xsl:template match="l:messages[not(@module)]">
		<xsl:comment>Global messages</xsl:comment>
		<xsl:apply-templates mode="insert"/>
	</xsl:template>

	<xsl:template match="l:messages[@module]">
		<xsl:variable name="m" select="@module"/>
		<xsl:comment>Module '<xsl:value-of select="@module"/>' messages</xsl:comment>
		<xsl:apply-templates select="l:message[@module=$m]" mode="insert"/>
	</xsl:template>

	<xsl:template match="l:message"/>

	<xsl:template match="l:message" mode="insert">
		<span class="message {@type}">
			[<span><xsl:value-of select="@module"/></span>]
			[<span><xsl:value-of select="@type"/></span>]
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<xsl:template match="l:link">
		<xsl:call-template name="link"/>
	</xsl:template>

	<xsl:template match="l:arg"/>

	<xsl:template name="link">
		<xsl:choose>

			<xsl:when test="@anchor">
				<a href="{@anchor}">
					<xsl:apply-templates/>
				</a>
			</xsl:when>

			<xsl:when test="@link">
				<a href="{$psp:requestBaseURI}{@link}">
					<xsl:apply-templates/>
				</a>
			</xsl:when>

			<xsl:when test="@href">
				<a href="{@href}"><xsl:apply-templates/></a>
			</xsl:when>

			<xsl:when test="@action">
				<xsl:variable name="id" select="generate-id()"/>
				<xsl:variable name="page">
					<xsl:call-template name="link-page"/>
				</xsl:variable>

				<form method="post" id="{$id}" style="visibility: hidden; display:inline;">
					<xsl:if test="@page">
						<xsl:attribute name="action">
							<xsl:call-template name="link-page"/>
						</xsl:attribute>
					</xsl:if>

					<input type="hidden" name="action:{@action}"/>
					<xsl:for-each select="l:arg">
						<input type="hidden" name="{@name}" value="{@value}"/>
					</xsl:for-each>
				</form>
				<a href="javascript:document.getElementById( '{$id}' ).submit();">
					<xsl:apply-templates select="@class|@style|@title|node()"/>
				</a>
			</xsl:when>

			<!-- for now equal treatment - it's an XOR -->
			<xsl:when test="@slashpage">
				<xsl:variable name="page">
					<xsl:call-template name="link-slashpage"/>
				</xsl:variable>
				<a href="{$page}">
					<xsl:apply-templates/>
				</a>
			</xsl:when>

			<!-- relative to current slashpage -->
			<xsl:when test="@slashpath">
				<xsl:variable name="page">
					<xsl:call-template name="link-slashpath"/>
				</xsl:variable>
				<a href="{$page}">
					<xsl:apply-templates/>
				</a>
			</xsl:when>


			<xsl:when test="@page">
				<xsl:variable name="page">
					<xsl:call-template name="link-page"/>
				</xsl:variable>
				<a href="{$page}">
					<xsl:apply-templates select="@onclick|@id|@class"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>

		</xsl:choose>
	</xsl:template>


	<xsl:template name="link-page">
		<xsl:variable name="pre">
			<xsl:choose>
				<xsl:when test="contains(@page, '?')">
					<xsl:value-of select="substring-before(@page, '?')"/>
				</xsl:when>
				<xsl:when test="contains(@page, '#')">
					<xsl:value-of select="substring-before(@page, '#')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@page"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="post">
			<xsl:choose>
				<xsl:when test="contains(@page, '?')">
					<xsl:value-of select="concat('?l=', $lang, '&amp;', substring-after(@page, '?'))"/>
				</xsl:when>
				<xsl:when test="contains(@page, '#')">
					<xsl:value-of select="concat('?l=', $lang, '#', substring-after(@page, '#'))"/>
				</xsl:when>
				<xsl:otherwise>
					?l=<xsl:value-of select="$lang"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:value-of select="concat($psp:requestBaseURI, $pre, '.html', $post)"/>
	</xsl:template>

	<xsl:template name="link-slashpage">
		<xsl:variable name="v"><xsl:value-of select="@slashpage"/></xsl:variable>
		<xsl:variable name="pre">
			<xsl:choose>
				<xsl:when test="contains($v, '?')">
					<xsl:value-of select="substring-before($v, '?')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$v"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="post">
			<xsl:if test="contains($v, '?')">
				<xsl:value-of select="concat('?', substring-after($v, '?'))"/>
			</xsl:if>
		</xsl:variable>

		<xsl:value-of select="concat($psp:requestBaseURI, $pre, '/', $post)"/>
	</xsl:template>



	<xsl:template name="link-slashpath">
		<xsl:variable name="v"><xsl:value-of select="@slashpath"/></xsl:variable>
		<xsl:variable name="pre">
			<xsl:choose>
				<xsl:when test="contains($v, '?')">
					<xsl:value-of select="substring-before($v, '?')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$v"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="post">
			<xsl:if test="contains($v, '?')">
				<xsl:value-of select="concat('?', substring-after($v, '?'))"/>
			</xsl:if>
		</xsl:variable>

		<xsl:value-of select="concat($psp:requestBaseURI, $psp:slashpage, '/', $pre, $post)"/>
	</xsl:template>


	<xsl:template match="l:edit">
		<div id="edit" class="{@class}">
			<xsl:apply-templates select="@*|*"/>
		</div>
		<!--
		<div id="edit" style="position: absolute; top: 0px; left: 0px;">
			<form action="/cgi-bin/test.pl" id="editform" target="_blank">
				<input type="hidden" name="base" id="base"/>
				<input type="hidden" name="f" id="editfile"/>
			</form>
			<form action="/cgi-bin/login.pl" id="loginform">
				<input type="hidden" name="url" id="loginurl"/>
			</form>
			<a id="editloginlink" href="javascript:editPage();"></a>
			<script type="text/javascript">
				<xsl:text disable-output-escaping="yes">
					function readCookie(name)
					{
						var nameEQ = name + "=";
						var ca = document.cookie.split(';');
						for(var i=0;i &lt; ca.length;i++)
						{
							var c = ca[i];
							while (c.charAt(0)==' ')
								c = c.substring(1,c.length);
							if (c.indexOf(nameEQ) == 0)
								return c.substring(nameEQ.length,c.length);
						}
						return null;
					}

					function editPage()
					{
						var url = window.location.href;

						if ( readCookie('key') == null )
						{
							document.getElementById('loginurl').value = url;
							document.getElementById('loginform').submit();
						}
						else
						{
							var base = url.substring( 0, url.lastIndexOf( '/' ) +1 );

							var u = url.substring( url.lastIndexOf( '/' )+1 );

							if ( u.indexOf( '?' ) > 0 )
							{
								u = u.substring(0, u.indexOf( '?' ) );
							}
							u = u.replace(/\.html/, '.xml' );
							//alert("File: " + u );
							document.getElementById('base').value = base;
							document.getElementById('editfile').value = u;
							document.getElementById('editform').submit();
						}
					}

					document.getElementById('editloginlink').innerHTML=
						readCookie('key') == null ? 'login' : 'edit';
				</xsl:text>
			</script>
		</div>
		-->
	</xsl:template>

	<!-- @label -->
	<!-- @hidden -->
	<xsl:template match="l:login">
		<xsl:variable name="id" select="generate-id()"/>

		<xsl:if test="@hidden">
			<script type="text/javascript">
				function loginLink( b )
				{
					document.getElementById('loginForm').style.display=b?'block':'none';
					document.getElementById('loginLink').style.display=b?'none':'block';
					return false;
				}
			</script>
			<a id="loginLink" href="javascript:loginLink(true);void(0);">
				<xsl:choose>
					<xsl:when test="@label">
						<xsl:value-of select="@label"/>
					</xsl:when>
					<xsl:otherwise>
						Login
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</xsl:if>

		<div id="loginForm">
			<xsl:if test="@hidden">
				<xsl:attribute name="style">display:none</xsl:attribute>
				<div onclick="javascript:loginLink(false);"
					style="display: block; right: 0px; color: red"
					align="right"><a>X</a></div>
			</xsl:if>
			<xsl:if test="//l:message[@module='auth' and @type='error']">
				<xsl:apply-templates select="//l:message[@module='auth']" mode="insert"/>
				<xsl:if test="@hidden">
					<script type="text/javascript">
						loginLink(true);
					</script>
				</xsl:if>
			</xsl:if>

			<noscript>
				<p><b>Warning:</b> Javascript is required to log in, but it is disabled! </p>
			</noscript>
			<script type="text/javascript" src="{$psp:requestBaseURI}js/crypto/sha1.js"/>
			<script type="text/javascript">
				<xsl:text disable-output-escaping="yes">
				function login_submit(form)
				{
					try
					{
						var s = "";
						for ( v in CryptoJS) s += typeof(v) + " " + v + ", ";

						var l = form.getElementsByTagName( "input" );
						var challenge, password;

						for ( var i = 0; i &lt; l.length; i ++)
						{
							if ( l.item(i).name == 'auth:challenge' )
								challenge = l.item(i);
							else if ( l.item(i).name == 'password' )
								password = l.item(i);

						}

						if ( challenge &amp;&amp; password )
						{
								password.value = CryptoJS.SHA1( challenge.value + CryptoJS.SHA1( password.value ) );
								return true;
						}
						else
						{
							alert("No password.");
						}

					} catch ( e ) {alert("bug: " + e );}

					return false;
				}
				</xsl:text>
			</script>
			<form method="POST" action="{@action}" onsubmit="return login_submit(this);" name="login">
				<xsl:apply-templates select="l:field"/>
				<table class="login">
					<tr>
						<th colspan="2">
							<span title="Secure challenge-response mechanism using SHA1: the password will not be transmitted over the network"> Secure Login </span>
						</th>
					</tr>
					<tr>
						<td><label for="user{$id}">Username</label></td>
						<td><input id="user{$id}" type="text" name="{@userfield}"/></td>
					</tr>
					<tr>
						<td><label for="user{$id}">Password</label></td>
						<td><input id="user{$id}" type="password" name="{@passfield}"/></td>
					</tr>
					<tr>
						<td/>
						<td><input type="submit"/></td>
					</tr>
				</table>
			</form>

		</div>
	</xsl:template>


	<xsl:template match="l:password-reset">
		<xsl:variable name="id" select="generate-id()"/>

		<div class='account-wrapper' id='loginForm'>
			<div class='account-body'>

				<h3>Reset Password</h3>

				<form method="POST" action="{@action}" onsubmit="return login_submit(this);" name="login" class='form account-form'>
					<xsl:apply-templates select="l:field"/>

					<div class='form-group'>
						<label class='placeholder-hidden' for="user{$id}">Username or E-Mail</label>
						<input class='form-control' id="user{$id}" type="text" name="{@userfield}" placeholder='Username or E-Mail'/>
					</div>
					<div class='form-group'>
						<input type="submit" value="Send Password Reset E-Mail" class='btn btn-primary'/>
						<i style='padding: 1em'><a href='/'>Back to login</a></i>
					</div>

				</form>
				<script type="text/javascript">
					document.getElementById("user<xsl:value-of select="$id"/>").focus();
				</script>

				<h4 style='text-align: right'><a href='/signup'>Sign up</a></h4>

			</div>
			<div class='account-footer'>
				<!-- footer -->
			</div>
		</div>

	</xsl:template>


	<xsl:template match="l:field">
		<input type="{@type}" name="{@name}" value="{@value}"/>
	</xsl:template>

	<xsl:template match="l:box">
		<div class="drag {@align} {@class}">
			<xsl:apply-templates select="@*|*"/>
		</div>
	</xsl:template>

  <xsl:template match="l:section">
    <xsl:variable name="depth" select="count(ancestor::l:section)"/>
		<!--
    <table border="0" width="100%">
      <tr>
        <td colspan="2" height="{$depth*6}">&#160;</td>
      </tr>
      <tr>
        <td width="{$depth * 8}">&#160;</td>
        <td align="left" class="section_title">
          <xsl:value-of select="@title"/>
        </td>
      </tr>
      <tr>
        <td width="{$depth * 8}">&#160;</td>
        <td>
          <xsl:apply-templates/>
        </td>
      </tr>
    </table>
		-->
		<div class="section {@class}" style="margin-left: {$depth}em;">
			<xsl:if test="@title">
				<xsl:element name="h{ 1 + $depth }">
					<xsl:value-of select="@title"/>
				</xsl:element>
			</xsl:if>
			<xsl:apply-templates/>
		</div>
  </xsl:template>

	<xsl:template match="l:title">
		<h1 class="{@class}"><xsl:apply-templates/></h1>
	</xsl:template>

  <xsl:template match="l:para|l:p">
    <p>
      <xsl:apply-templates/>
    </p>
  </xsl:template>


  <xsl:template match="l:list">
    <dl>
      <xsl:apply-templates select="@*|*"/>
    </dl>
  </xsl:template>

  <xsl:template match="l:list/l:el">
    <dt><xsl:value-of select="@title"/></dt>
    <dd><xsl:apply-templates/></dd>
  </xsl:template>


  <xsl:template match="l:code">
		<pre class="code">
			<xsl:call-template name="show-code">
				<xsl:with-param name="content" select="."/>
			</xsl:call-template>
			<!--
			<xsl:apply-templates/>
			-->
		</pre>
  </xsl:template>

  <xsl:template name="show-code">
    <xsl:param name="content"/>

    <xsl:choose>
      <xsl:when test="contains($content, '&lt;')">
        <xsl:value-of select="substring-before($content, '&lt;')"/><span class="code_special">&lt;</span>

        <xsl:choose>
          <xsl:when test="contains(substring-after($content, '&lt;'), '&gt;')">
            <span class="code_tag"><xsl:value-of select="substring-before(substring-after($content, '&lt;'), '&gt;')"/></span><span class="code_special">&gt;</span>

            <xsl:call-template name="show-code">
              <xsl:with-param name="content"><xsl:value-of select="substring-after($content, '&gt;')"/></xsl:with-param>
            </xsl:call-template>

          </xsl:when>
          <xsl:otherwise>
            <xsl:call-template name="show-code">
              <xsl:with-param name="content"><xsl:value-of select="substring-after($content, '&lt;')"/></xsl:with-param>
            </xsl:call-template>
          </xsl:otherwise>

        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$content"/>
      </xsl:otherwise>
    </xsl:choose>

  </xsl:template>


	<xsl:template match="processing-instruction()"/>

  <xsl:template match="node()|@*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
