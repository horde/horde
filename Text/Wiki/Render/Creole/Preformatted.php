<?php

class Text_Wiki_Render_Creole_Preformatted extends Text_Wiki_Render {

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
        $text = $options['text'];
        
        $text = preg_replace("/\n( +)}}}/s", "\n$1 }}}", $text);
        
        return "{{{\n" . $text . "\n}}}\n\n";
    }
}
?>