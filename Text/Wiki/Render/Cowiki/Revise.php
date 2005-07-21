<?php

class Text_Wiki_Render_CoWiki_Revise extends Text_Wiki_Render {
    
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
        switch($options['type']) {
        case 'del_start':
            return '<del>';
            break;
        case 'del_end':
            return '</del>';
            break;
        case 'ins_start':
            return '<ins>';
            break;
        case 'ins_end':
            return '</ins>';
            break;
        }
    }
}
?>