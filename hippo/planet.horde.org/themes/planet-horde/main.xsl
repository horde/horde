<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml"

xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rss="http://purl.org/rss/1.0/" xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:syn="http://purl.org/rss/1.0/modules/syndication/" xmlns:admin="http://webns.net/mvcb/"

>
    <xsl:output encoding="utf-8" method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>
    <xsl:include href="common.xsl"/>
    <xsl:param name="startEntry" value="'0'"/>
    <xsl:variable name="searchString" select="/planet/search/string"/>
    <xsl:template match="/">

        <html>

            <xsl:call-template name="htmlhead"/>
            <body>
   <div id="wrapper">
    <xsl:call-template name="bodyhead"/>
    <div id="content" class="clr">
     <xsl:call-template name="middlecol"/>
     <xsl:call-template name="rightcol"/>
    </div>
    <div id="footer" class="clr">
     <span class="right">
       Powered by <a href="http://www.planet-php.net/">Planet PHP</a>, more or less.
     </span>
    </div>
   </div>
            </body>
        </html>
    </xsl:template>
    <xsl:template name="rightcol">
       <div id="sidebar">
            <div class="side_item">
                    <h4>Search Planet Horde</h4>
                    <form onsubmit="niceURL(); return false;" name="search" method="get" action="/">
                        <input id="searchtext" type="text" name="search">
                            <xsl:if test="/planet/search/string">
                                <xsl:attribute name="value">
                                    <xsl:value-of select="/planet/search/string"/>
                                </xsl:attribute>
                            </xsl:if>
                        </input>
                       <input class="submit" type="submit" value="Go"/>
                    </form>
             <div class="side_bottom">&#160;</div>
            </div>

            <div class="side_item">
              <h4>Blogs</h4>
 <p>
              <xsl:apply-templates select="/planet/blogs/blog"/>
</p>
              <div class="side_bottom">&#160;</div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="blogs/blog">
<xsl:if test="maxdate &gt; border">
        <a href="{link}" class="blogLinkPad">
    <xsl:choose>
                <xsl:when test="string-length(author) &gt; 0 ">           
                <xsl:value-of select="author"/>     
<xsl:if test="dontshowblogtitle = 0"> (<xsl:value-of select="title"/>) </xsl:if>
                </xsl:when>
                <xsl:otherwise>
                <xsl:value-of select="title"/>
                </xsl:otherwise>
               </xsl:choose> 
        </a>
</xsl:if>
    </xsl:template>
    
    
        <xsl:template match="/planet/entries[@section='releases']/entry">

        <a href="{link}" class="blogLinkPad">
            <xsl:value-of select="title"/>
        </a>
    </xsl:template>
    <xsl:template name="middlecol">
        <div id="main">
            <xsl:apply-templates select="/planet/entries[@section='default']/entry"/>
            <xsl:variable name="nextEntries">
                <xsl:choose>
                    <xsl:when test="(/planet/search/count - (/planet/search/start + 10)) &gt;= 10">10</xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="(/planet/search/count - (/planet/search/start + 10))"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <div id="pageNav">

                    <span style="float: right;">

                        <xsl:if test="$nextEntries &gt; 0">
                            <xsl:choose>
                                <xsl:when test="$searchString">
                                    <a href="/search/{$searchString}?start={$startEntry + 10}">Next <xsl:value-of select="$nextEntries"/> Older Entries</a>
                                </xsl:when>
                                 <xsl:otherwise>
                                    <a href="/?start={$startEntry + 10}">Next <xsl:value-of select="$nextEntries"/> Older Entries</a>
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:if>
                   

                    </span>
                    <span style="float: left;">
                        <xsl:choose>
                            <xsl:when test="$startEntry = 0 and $nextEntries &lt;= 0">
                             No More Entries
                             </xsl:when>
                            <xsl:when test="$startEntry &gt;= 10">
                                <xsl:choose>
                                    <xsl:when test="$searchString">
                                        <a href="/search/{$searchString}?start={$startEntry - 10}">Previous 10 Newer Entries</a>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <a href="/?start={$startEntry - 10}">Previous 10 Newer Entries</a>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:when>

                        </xsl:choose>

                    </span>
            </div>

        </div>

    </xsl:template>

    <xsl:template match="entries[@section='default']/entry">
        <div class="box">
                <h3>
                <a href="{link}" class="blogTitle">
                    <xsl:value-of select="title"/>
                </a>
                </h3>

                   By <a href="{blog_link}">
    <xsl:choose>
                <xsl:when test="string-length(blog_author) &gt; 0 ">           
                <xsl:value-of select="blog_author"/>     
<xsl:if test="blog_dontshowblogtitle = 0"> (<xsl:value-of select="blog_title"/>) </xsl:if>
                </xsl:when>
                <xsl:otherwise>
                <xsl:value-of select="blog_title"/>
                </xsl:otherwise>
               </xsl:choose> 
                    </a>

                <xsl:text> </xsl:text> 
            (<xsl:value-of select="dc_date"/> UTC)
<div class="feedcontent" >
<xsl:choose>
<xsl:when test="string-length(content_encoded) &gt; 0">
             <xsl:value-of select="content_encoded" disable-output-escaping="yes"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="description" disable-output-escaping="yes"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
        </div>
    </xsl:template>

</xsl:stylesheet>
