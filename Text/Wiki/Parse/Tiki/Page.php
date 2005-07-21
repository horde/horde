<?php

class Text_Wiki_Parse_Page extends Text_Wiki_Parse {
    
    
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
    
    var $regex = '/\.\.\.page\.\.\./i';


    function parse() {
        $this->wiki->source = preg_replace_callback($this->regex, array(&$this, 'process'), $this->wiki->source);

    }
    
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
    
    function process(&$matches)
    {
        return $this->wiki->addToken($this->rule);
    }
}
?>