<?php

class Text_Wiki_Render_CoWiki_List extends Text_Wiki_Render {
    
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
        
        $pad = str_pad('', $options['level'] - 1, ' ');
                
        switch ($options['type']) {
        
        case 'bullet_list_start':
        case 'number_list_start':
            if ($options['level'] == 0) {
                //return "\n";
            }
            break;
        case 'bullet_list_end':
        case 'number_list_end':
        
            if ($options['level'] == 0) {
                //return "\n";
            }
            break;
        case 'bullet_item_start':
            return "\n".$pad.'* ';
            break;
        case 'number_item_start':
            return "\n".$pad.'# ';
            break;
        case 'bullet_item_end':
        case 'number_item_end':
        default:
            return '';
            break;
        }
    }
}
?>