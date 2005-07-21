<?php

/**
* 
* Parses for wiki freelink text.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id$
* 
*/

/**
* 
* Parses for freelinked page links.
* 
* This class implements a Text_Wiki_Parse to find source text marked as a
* wiki freelink, and automatically create a link to that page.
* 
* A freelink is any page name not conforming to the standard
* StudlyCapsStyle for a wiki page name.  For example, a page normally
* named MyHomePage can be renamed and referred to as ((My Home Page)) --
* note the spaces in the page name.  You can also make a "nice-looking"
* link without renaming the target page; e.g., ((MyHomePage|My Home
* Page)).  Finally, you can use named anchors on the target page:
* ((MyHomePage|My Home Page#Section1)).
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

//Taken care of in WikiLink (I think)
class Text_Wiki_Parse_Freelink extends Text_Wiki_Parse {
    

    var $regex = '/abcdefghijklmnop\(\)\(\#\*%$%/';
    
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'page' => the wiki page name (e.g., HomePage).
    * 
    * 'text' => alternative text to be displayed in place of the wiki
    * page name.
    * 
    * 'anchor' => a named anchor on the target wiki page
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token to be used as a placeholder in
    * the source text, plus any text priot to the match.
    *
    */
    
    function process(&$matches)
    {
        return;
    }
}
?>