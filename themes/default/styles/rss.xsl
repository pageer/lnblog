<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:slash="http://purl.org/rss/1.0/modules/slash/" 
xmlns:wfw="http://wellformedweb.org/CommentAPI/">
<xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>

<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><xsl:value-of select="/rss/channel/title" disable-output-escaping="yes"/></title>
<link type="text/css" href="rss.css" />
</head>
<body>
<h1><a href=""><xsl:value-of select="/rss/channel/title" disable-output-escaping="yes"/></a></h1>
<h2><xsl:value-of select="/rss/channel/description" disable-output-escaping="yes"/></h2>
<ul>
<xsl:apply-templates select="/rss/channel/item"/>
</ul>
</body>
</html>
</xsl:template>

<xsl:template match="item">
	<li>
	<h3><a href="{link}"><xsl:value-of select="title"/></a></h3>
	<ul>
	<xsl:choose>
		<xsl:when test="slash:comments &gt; 1">
		<li><a href="{comments}" lang="en"><xsl:value-of select="slash:comments"/> comments</a></li>
		</xsl:when>
		<xsl:when test="slash:comments &gt; 0">
		<li><a href="{comments}" lang="en"><xsl:value-of select="slash:comments"/> comment</a></li>
		</xsl:when>
		<xsl:otherwise>
		<li lang="en"><a href="{comments}">Post a comment</a></li>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:if test="wfw:commentRss">
	<li><a href="{wfw:commentRss}" lang="en">Subscribe to comments.</a></li>
	</xsl:if>
	</ul>
	<xsl:value-of select="description" disable-output-escaping="yes"/>
  	</li>
</xsl:template>
   
</xsl:stylesheet>
