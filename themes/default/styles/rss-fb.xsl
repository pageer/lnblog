<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
   <xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>
   <xsl:variable name="title" select="/rss/channel/title"/>
	<xsl:variable name="feedUrl" select="/rss/channel/atom10:link[@rel='self']/@href" xmlns:atom10="http://www.w3.org/2005/Atom"/>
	
   <xsl:template match="/">
      <xsl:element name="html">
         <head>
            <title><xsl:value-of select="$title"/></title>
			<link rel="alternate" type="application/rss+xml" title="{$title}" href="{$feedUrl}"/>
         </head>
         <xsl:apply-templates select="rss/channel"/>
      </xsl:element>
   </xsl:template>
   
   <xsl:template match="channel">
      <body id="browserfriendly">
         <div id="bodycontainer">
               <h1>
                  <a href="{link}">
                     <xsl:value-of select="$title"/>
                  </a>
               </h1>
            <div id="bodyblock">
               <xsl:apply-templates select="item"/>
            </div>
         </div>
      </body>
   </xsl:template>
   
   <xsl:template match="item" xmlns:dc="http://purl.org/dc/elements/1.1/">
      <xsl:if test="position() = 1">
         <h3 xmlns="http://www.w3.org/1999/xhtml" id="currentFeedContent">Current Feed Content</h3>
      </xsl:if>
      <ul xmlns="http://www.w3.org/1999/xhtml">
         <li class="regularitem">
            <h4 class="itemtitle">
               <a href="{link}">
                  <xsl:value-of select="title"/>
               </a>
            </h4>
            <h5 class="itemposttime">
               <xsl:if test="count(child::pubDate)=1"><span>Posted:</span><xsl:text> </xsl:text><xsl:value-of select="pubDate"/></xsl:if>
				<xsl:if test="count(child::dc:date)=1"><span>Posted:</span><xsl:text> </xsl:text><xsl:value-of select="dc:date"/></xsl:if>
            </h5>
            <div class="itemcontent" name="decodeable">
               <xsl:call-template name="outputContent"/>
            </div>
            <xsl:if test="count(child::enclosure)=1">
               <p class="mediaenclosure">MEDIA ENCLOSURE: <a href="{enclosure/@url}"><xsl:value-of select="child::enclosure/@url"/></a></p>
            </xsl:if>
         </li>
      </ul>
   </xsl:template>

   <xsl:template name="outputContent">
      <xsl:choose>
         <xsl:when xmlns:xhtml="http://www.w3.org/1999/xhtml" test="xhtml:body">
            <xsl:copy-of select="xhtml:body/*"/>
         </xsl:when>
         <xsl:when xmlns:xhtml="http://www.w3.org/1999/xhtml" test="xhtml:div">
            <xsl:copy-of select="xhtml:div"/>
         </xsl:when>
         <xsl:when xmlns:content="http://purl.org/rss/1.0/modules/content/" test="content:encoded">
            <xsl:value-of select="content:encoded" disable-output-escaping="yes"/>
         </xsl:when>
         <xsl:when test="description">
            <xsl:value-of select="description" disable-output-escaping="yes"/>
         </xsl:when>
      </xsl:choose>
   </xsl:template>
   
</xsl:stylesheet>
