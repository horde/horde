<?php

/**
* 
* Parses for image placement.
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
* Parses for image placement.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Image extends Text_Wiki_Parse {
    
    
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
    
    var $regex = '/({{)(wiki:|https?:\/\/|ftp:\/\/)(.+?)(}})/i';
    
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'src' => The image source, typically a relative path name.
    *
    * 'opts' => Any macro options following the source.
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
        if ($matches[2] != 'wiki:') {
            $matches[3] = $matches[2].$matches[3];
        }

        $pos = strpos($matches[3], '?');
        if ($pos === false) {
            $options = array(
                'src' => $matches[3],
                'attr' => array());
        } else {
            $options = array('src' => substr($matches[3], 0, $pos));
            $attr = substr($matches[3], $pos + 1);
            $parts = explode('x', $attr);
            if (isset($parts[0]) && $parts[0] != '') {
                $options['attr']['width'] = $parts[0];
            }
            if (isset($parts[1]) && $parts[1] != '') {
                $options['attr']['height'] = $parts[1];
            }
        }
        
        return $this->wiki->addToken($this->rule, $options);
    }
}
?>