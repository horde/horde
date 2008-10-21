<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">
    <xsl:output encoding="utf-8" method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"/>

    
     <xsl:template name="htmlhead">

<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
            <title>Planet Horde</title>

            <link rel="shortcut icon" href="/favicon.ico" />
            <link href="./themes/css/style.css" rel="stylesheet" type="text/css"/>
            <link href="./themes/css/screen.css" rel="stylesheet" type="text/css"/>
  
<link rel="alternate" type="application/rss+xml" title="RSS" href="/rss/" />
<link rel="alternate" type="application/rdf+xml" title="RDF" href="/rdf/" />
<link rel="alternate" type="application/x.atom+xml" title="Atom" href="/atom/" />
        </head>
    </xsl:template>

<xsl:template name="bodyhead">
  <div id="header" class="clr">
    <div id="logo" class="left">
      <span>
      <a href="/"><img alt="Planet Horde" src="./themes/img/logo.gif" /></a>
      </span>
    </div>
      <ul id="nav" class="right">
      </ul>
  </div>
</xsl:template>

  </xsl:stylesheet>
