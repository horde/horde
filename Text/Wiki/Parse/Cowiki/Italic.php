<?php

/**
* 
* Parses for italic text.
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
* Parses for italic text.
* 
* This class implements a Text_Wiki_Parse to find source text marked for
* emphasis (italics) as defined by text surrounded by two single-quotes.
* On parsing, the text itself is left in place, but the starting and ending
* instances of two single-quotes are replaced with tokens.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Italic extends Text_Wiki_Parse {
    
    /**
     * Setting regex in constructor instead of with var as we need $this->wiki->delim
     */
    function Text_Wiki_Parse_Italic(&$obj) {
        parent::Text_Wiki_Parse($obj);

        //using [^delim] here as CoWiki's Italic syntax is a single / and its other markup is HTML syntax with / in it
        //  This rule *must* be applied after all HTML style rules
        $this->regex = '=(?<!<)/(()|[^/]*?)/=';
    }
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'type' => ['start'|'end'] The starting or ending point of the
    * emphasized text.  The text itself is left in the source.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the text to be
    * emphasized.
    *
    */
    
    function process(&$matches)
    {
        $start = $this->wiki->addToken(
            $this->rule, array('type' => 'start')
        );
        
        $end = $this->wiki->addToken(
            $this->rule, array('type' => 'end')
        );
        
        return $start . $matches[1] . $end;
    }
}
?>