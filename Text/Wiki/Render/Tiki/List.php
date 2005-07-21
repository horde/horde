<?php

class Text_Wiki_Render_Tiki_List extends Text_Wiki_Render {
    
    /**
    * 
    * Renders a token into text matching the requested format.
    * 
    * This rendering method is syntactically and semantically compliant
    * with XHTML 1.1 in that sub-lists are part of the previous list item.
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
        // make nice variables (type, level, count)
        
        switch ($options['type']) {
        
        case 'bullet_item_start':
            return "\n".str_pad('', $options['level'], '*');
            break;
        case 'number_item_start':
            return "\n".str_pad('', $options['level'], '#');
            break;
        case 'bullet_item_end':
        case 'number_item_end':
        case 'bullet_list_start':
        case 'number_list_start':
        case 'bullet_list_end':
        case 'number_list_end':
        default:
            return '';
            break;
        }
    }
}
?>