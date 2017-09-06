<?php

class Text_Wiki_Render_Tiki_Blockquote extends Text_Wiki_Render {
    
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
        // set up indenting so that the results look nice; we do this
        // in two steps to avoid str_pad mathematics.  ;-)
        $pad = str_pad('', $options['level'], '>');
        
        // starting
        if ($options['type'] == 'start') {
            return $pad.' ';
        }
        
        // ending
        if ($options['type'] == 'end') {
            return '';
        }
    }
}
?>