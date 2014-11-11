<?xml version="1.0"?>

<!--
   - author: Kenney Westerhof <kenney@neonics.com>
  -->


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns:psp="http://neonics.com/2011/psp"
	xmlns:sqldb="http://neonics.com/2014/db/sql"
	xmlns:l="http://www.neonics.com/xslt/layout/1.0"
	exclude-result-prefixes="sqldb"
>

	<xsl:template match="sqldb:test">
		<xsl:apply-templates select="php:function('sqldb_test')"/>
	</xsl:template>

	<xsl:template match="sqldb:listusers">
		<xsl:apply-templates select="php:function('sqldb_listusers')"/>
	</xsl:template>

	<xsl:template match="sqldb:listtables">
		<xsl:apply-templates select="php:function('sqldb_listtables')"/>
	</xsl:template>


	<xsl:template match="sqldb:query">
		<xsl:apply-templates select="php:function('sqldb_query', .)"/>
	</xsl:template>


	<!--  -->

	<xsl:template match="sqldb:result">
		<style type='text/css'>
			/* fixup for fixed header */
			a.anchor{display: block; position: relative; top: -70px; visibility: hidden;}
		</style>
		<div class='db-result'>
			<xsl:apply-templates/>
		</div>
	</xsl:template>


	<!--  meta results  -->

	<xsl:template match="sqldb:table">
		<table class='table'>
			<thead>
				<tr>
					<th>TABLE</th>
					<th><xsl:value-of select="@name"/></th>
					<td>
						<xsl:if test="@inherits">inherits:</xsl:if>
					</td>
					<th><xsl:value-of select="@inherits"/></th>
				</tr>
				<tr>
					<th>COLUMN</th>
					<!--  XXX we assume that all columns have the same attributes,
						or that at least the first column has all of them.
						See lib/db/meta.php for the data format and sqldb.php
						for the XML serialisation
					 -->
					<xsl:for-each select="sqldb:column[1]/@*">
						<th><xsl:value-of select="name(.)"/></th>
					</xsl:for-each>
				</tr>
			</thead>
			<tbody>
				<xsl:apply-templates select="sqldb:column"/>
				<tr><th>FOREIGN KEYS</th></tr>
				<xsl:apply-templates select="sqldb:foreign_key"/>
			</tbody>
		</table>
	</xsl:template>


	<xsl:template match="sqldb:column/sqldb:foreign_key">
		<!--  this is a duplicate of the general fk list -->
	</xsl:template>



	<xsl:template match="sqldb:table/sqldb:foreign_key">
		<tr>
			<th>
			<xsl:value-of select="."/>
			</th>
			<td>
				<xsl:value-of select="sqldb:fk_constraint_name"/>
			</td>
			<td>
				<xsl:value-of select="sqldb:fk_table_name"/>(<xsl:value-of select="sqldb:fk_column_name"/>)
				</td>
				<td>
				<xsl:value-of select="sqldb:referenced_table_name"/>(<xsl:value-of select="sqldb:referenced_column_name"/>)
			</td>
		</tr>
	</xsl:template>


	<xsl:template match="sqldb:column">
		<tr>
			<th>
				<!-- can't use ./@table_name since it might be the base (inherited) table and FKs don't always know. -->
				<a class='anchor' id="{../@name}_{@column_name}"/>

				<xsl:value-of select="@name"/>
			</th>

			<!--  we assume here that all columns have the same attributes in the same order - see above. -->
			<xsl:for-each select="@*[not(@name)]">
				<td><xsl:value-of select="."/></td>
			</xsl:for-each>
			<xsl:apply-templates/>
		</tr>
	</xsl:template>

	<xsl:template match="sqldb:column/sqldb:fk_single">
		<th>FKsingle</th><td><a href="#{@table}_{@column}"><xsl:value-of select="@table"/>(<xsl:value-of select="@column"/>)</a></td>
	</xsl:template>


	<!-- query results  -->
	<xsl:template match="sqldb:row">
		<div>
			<xsl:for-each select="@*">
				<xsl:value-of select="name(.)"/>=<xsl:value-of select="."/><br/>
			</xsl:for-each>
			<br/>
			<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="sqldb:user">
		<div>
		<b>USER:<i><xsl:value-of select="."/></i></b>
		</div>
	</xsl:template>


	<!-- warn for unused tabs -->

	<!--  disabled - users of this module may want to define their own templates for database tables

	<xsl:template match="sqldb:*" priority="-1">
		 TODO: call psp_warning or similar and use psp:messages output system.
		<pre><b>unmatched: <xsl:value-of select="name(.)"/></b>:
		<xsl:value-of select="."/></pre>
	</xsl:template>

	-->

	<xsl:template match="@*|node()" priority="-2">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()"/>
		</xsl:copy>
	</xsl:template>


</xsl:stylesheet>
