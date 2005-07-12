<?php

class Text_Wiki_Render_Xhtml_Font extends Text_Wiki_Render {
    
/*    var $size = array(
        'xx-small',
        'x-small',
        'small',
        'medium',
        'large',
        'x-large',
        'xx-large',
        'larger',
        'smaller'
    );
    var $units = array(
        'em',
        'ex',
        'px',
        'in',
        'cm',
        'mm',
        'pt',
        'pc'
    );
*/    
    
    /**
    * 
    * Renders a token into text matching the requested format.
    * 
    * @access public
    * 
    * @param array $options The "options" portion of the token (second
    * element).
    * 
    * @return string The text rendered from the token options.
    * 
    */
    
    function token($options)
    {
        if ($options['type'] == 'end') {
            return '</span>';
        }
        if ($options['type'] != 'start') {
            return '';
        }

        $ret = '<span style="';
        if (isset($options['size'])) {
            $size = trim($options['size']);
            if (is_numeric($size)) {
                $size .= 'px';
            }
            $ret .= "font-size: $size;";
        }
        
        return $ret.'">';
    }
}
?>
