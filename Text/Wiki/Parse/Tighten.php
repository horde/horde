<?php
// $Id$


/**
* 
* The rule removes all remaining newlines.
*
* @author Paul M. Jones <pmjones@ciaweb.net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Parse_Tighten extends Text_Wiki_Parse {
    
    
    /**
    * 
    * Apply tightening directly to the source text.
    *
    * @access public
    * 
    */
    
    function parse()
    {
        $this->wiki->source = str_replace("\n", '',
            $this->wiki->source);
    }
}
?>