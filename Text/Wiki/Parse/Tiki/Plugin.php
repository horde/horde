<?php
class Text_Wiki_Parse_Plugin extends Text_Wiki_Parse {
    
    
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
    
    //var $regex = '/^({([A-Z]+?)\((.+)?\)})((.+)({\2}))?(\s|$)/Umsi';
    var $regex = '/^(?:{([A-Z]+?)\((.+)?\)})(?:(.+)(?:{\1}))(\s|$)/Umsi';
    
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'text' => The full matched text, not including the <code></code> tags.
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
        // are there additional attribute arguments?
        $args = trim($matches[2]);
        
        if ($args == '') {
            $options = array(
                'text' => $matches[3],
                'plugin' => $matches[1],
                'attr' => array()
            );
        } else {
        	// get the attributes...
        	//$attr = $this->getAttrs($args);
            foreach (explode(',', $args) as $part) {
                if (false !== ($eq = strpos($part, '=')) && $eq != strlen($part) - 1) {
                    $attr[substr($part, 0, $eq)] = substr($part, $eq + 1 + ($part[$eq + 1] == '>' ? 1 : 0));
                } else {
                    $attr[$part] = '';
                }
            }
        	
        	// retain the options
            $options = array(
                'text' => $matches[3],
                'plugin' => $matches[1],
                'attr' => $attr
            );
        }
        
        return $this->wiki->addToken($this->rule, $options) . $matches[4];
    }
}
?>