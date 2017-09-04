<?php

/**
* 
* Parses for image placement.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author bertrand Gugger <bertrand@toggg.com>
* @author Justin Patrin <papercrane@reversefold.com>
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id$
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
    
    var $regex = '/{img\s+(.+?)\s*}/i';
    
    
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
        $options = array('src' => '', 'attr' => array('border' => '0'));
        $src = $link = $align = $desc = '';
        preg_match_all('/(\w+)\s*=\s*((&quot;|\'|"|&apos;|&#34;|&#39;)(.*?)\3|\S*)/',
                       str_replace(array('}', '{'), '', $matches[1]), $splits, PREG_SET_ORDER);

        foreach ($splits as $attr) {
            if (isset($attr[4])) {
                $attr[2] = $attr[4];
            }
            $attr[1] == strtolower($attr[1]);
            switch ($attr[1]) {
            case 'src':
                $options['src'] = $attr[2];
                break;
	        case 'height':
	        case 'width':
            case 'alt':
                $options['attr'][$attr[1]] = $attr[2];
                break;
	        case 'link':
//	            $link = $attr[2];
                $options['attr'][$attr[1]] = $attr[2];
                break;
    	    case 'align':
    	        $align = $attr[2];
                break;
    	    case 'desc':
    	        $desc = $attr[2];
                break;
    	    case 'imalign':;
                $options['attr']['align'] = $attr[2];
                break;
            case 'usemap':
                $options['attr']['usemap'] = '#'.$attr[2];
                break;
            }
        }
/*        if ($link) {
            $this->wiki->addToken('Url', $options);
        }
            
        
        if ($pos === false) {
            $options = array(
                'src' => $matches[2],
                'attr' => array());
        } else {
            // everything after the space is attribute arguments
            $options = array(
                'src' => substr($matches[2], 0, $pos),
                'attr' => $this->getAttrs(substr($matches[2], $pos+1))
            );
        }
*/
        return $this->wiki->addToken($this->rule, $options);
    }
}
?>
