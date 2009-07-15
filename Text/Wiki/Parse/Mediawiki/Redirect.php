<?php

/**
* 
* Parses for wiki redirects.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
* 
* @license LGPL
* 
*/

/**
* 
* Parses for wiki redirects.
* 
* This class implements a Text_Wiki_Parse to find source text marked to
* be a wiki redirect, as defined by #REDIRECT [[Wiki link]].
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
* 
*/

class Text_Wiki_Parse_Redirect extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * 
    * @var string
    * 
    * @see parse()
    * 
    */
    
    var $regex = '/^\s*?#(?:REDIRECT|redirect)\s*?\[\[(.+?)\]\]$/m';
    
    /**
    * 
    * Generates a replacement token for the matched text.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A pair of delimiters surrouding the wiki page name.
    *
    */
    
    function process(&$matches)
    {    
        $start = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'start',
                'text' => $matches[1]
            )
        );

        $end = $this->wiki->addToken($this->rule, array('type' => 'end'));
        
        return $start . $matches[1] . $end;
    }
}
?>
