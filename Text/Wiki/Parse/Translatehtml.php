<?php
// $Id$


/**
* 
* Translate HTML entities in the source text.
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @package Text_Wiki
* 
*/

class Text_Wiki_Parse_Translatehtml extends Text_Wiki_Parse {
    
    var $conf = array('type' => HTML_ENTITIES);
    
    /**
    * 
    * Simple parsing method.
    *
    * @access public
    * 
    */
    
    function parse()
    {
        // get the type of html translation
        $type = $this->getConf('type', null);
        
        // shoule we translate?
        if ($type) {
        
            // yes! get the translation table.
            $xlate = get_html_translation_table($type);
            
            // remove the delimiter character it doesn't get translated
            unset($xlate[$this->wiki->delim]);
            
            // convert!
            $this->wiki->source = strtr($this->wiki->source, $xlate);
        }
    }

}
?>