<?php

/**
* 
* Parses for text marked as "raw" (i.e., to be rendered as-is).
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
* Parses for text marked as "raw" (i.e., to be rendered as-is).
* 
* This class implements a Text_Wiki rule to find sections of the source
* text that are not to be processed by Text_Wiki.  These blocks of "raw"
* text will be rendered as they were found.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Raw extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to find source text matching this
    * rule.
    * 
    * @access public
    * 
    * @var string
    * 
    */
    
    var $regex = "/\n<nowiki>\n(.*?)\n<\/nowiki>\n/";
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'text' => The full matched text.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    function parse() {
        $this->wiki->source = preg_replace_callback(
            $this->regex,
            array(&$this, 'process'),
            $this->wiki->source
        );

        $this->wiki->source = preg_replace_callback(
            '/%%(.*?)%%/',
            array(&$this, 'process'),
            $this->wiki->source
        );

    }
    
    function process(&$matches)
    {
        $options = array('text' => $matches[1]);
        return "\n".$this->wiki->addToken($this->rule, $options)."\n";
    }
}
?>