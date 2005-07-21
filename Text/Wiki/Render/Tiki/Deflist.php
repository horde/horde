<?php

class Text_Wiki_Render_Tiki_Deflist extends Text_Wiki_Render {
    
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
        $type = $options['type'];
        
        switch ($type) {
        
        case 'list_start':
            return "\n";
            break;
        
        case 'list_end':
            return "\n\n";
            break;
        
        case 'term_start':
            return ';';
            break;
        
        case 'term_end':
            return '';
            break;
        
        case 'narr_start':
            return ';';
            break;
        
        case 'narr_end':
            return "\n";
            break;
        
        default:
            return '';
        
        }
    }
}
?>