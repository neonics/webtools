<?xml version="1.0"?>

<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>

  <xsl:template match="section">
    <xsl:variable name="depth" select="1+count(ancestor::section)"/>
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
  </xsl:template>


  <xsl:template match="para|p">
    <p>
      <xsl:apply-templates/>
    </p>
  </xsl:template>

  <xsl:template match="link">
    <a>
      <xsl:for-each select="@*">
        <xsl:copy/>
      </xsl:for-each>
      <xsl:apply-templates/>
    </a>
  </xsl:template>

  <xsl:template match="img">
    <xsl:copy>
      <xsl:for-each select="@*">
        <xsl:copy/>
      </xsl:for-each>
    </xsl:copy>
  </xsl:template>



  <xsl:template match="list">
    <dl>
      <xsl:apply-templates/>
    </dl>
  </xsl:template>

  <xsl:template match="list/el">
    <dt><xsl:value-of select="@title"/></dt>
    <dd><xsl:apply-templates/></dd>
  </xsl:template>


  <xsl:template match="code">
    <div class="code">
      <pre>
            <xsl:call-template name="show-code">
              <xsl:with-param name="content" select="."/>
            </xsl:call-template>
            <!--
            <xsl:apply-templates/>
            -->
      </pre>
    </div>
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




</xsl:stylesheet>
