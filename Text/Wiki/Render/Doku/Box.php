<?php

//There is no box rule for Doku, so use a simple table
class Text_Wiki_Render_Doku_Box extends Text_Wiki_Render {
    
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
        if ($options['type'] == 'start') {
            return '

| ';
        }
        
        if ($options['type'] == 'end') {
            return ' |

';
        }
    }
}
?>