<?php

class Text_Wiki_Parse_SpecialChar extends Text_Wiki_Parse {

    var $types = array('~bs~',
                       '~hs~',
                       '~amp~',
                       '~ldq~',
                       '~rdq~',
                       '~lsq~',
                       '~rsq~',
                       '~c~',
                       '~--~',
                       '~lt~',
                       '~gt~');
    
    function Text_Wiki_Parse_SpecialChar(&$obj) {
        parent::Text_Wiki_Parse($obj);

        $this->regex = '';
        foreach ($this->types as $type) {
            if ($this->regex) {
                $this->regex .= '|';
            }
            $this->regex .= preg_quote($type);
        }
        $this->regex = '/('.$this->regex.'|("|&quot;) \-\- (?:\2)|\~\d+\~)/';
    }
    
    /**
    * 
    * Generates a replacement token for the matched text.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A delimited token to be used as a placeholder in
    * the source text.
    *
    */
    
    function process(&$matches)
    {
        return $this->wiki->addToken($this->rule, array('char' => $matches[1]));
    }
}

?>