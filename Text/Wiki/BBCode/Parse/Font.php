<?php
/**
* @version Exp $
* 
* Parses for italic text.
* 
* This class implements a Text_Wiki_Rule to find source text with size
* as defined by text surrounded by [size=...] ... [/size]
* On parsing, the text itself is left in place, but the starting and ending
* tags are replaced with tokens.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Bertrand Gugger <bertrand@toggg.com>
* 
*/

class Text_Wiki_Parse_Font extends Text_Wiki_Parse {
    
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
    
    var $regex = "#\[size=(\d+)](.*?)\[/size]#i";
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'type' => ['start'|'end'] The starting or ending point of the
    * emphasized text.  The text itself is left in the source.
    * 
    * 'size' => the size indicator
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
            $this->rule, 
            array(
                'type' => 'start',
                'size' => $matches[1]
            )
        );
        
        $end = $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'end',
                'size' => $matches[1]
            )
        );
        
        return $start . $matches[2] . $end;
    }
}
?>
