<?php

class Text_Wiki_Render_Doku_Blockquote extends Text_Wiki_Render {
    
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
        // starting
        if ($options['type'] == 'start') {
            $this->wiki->registerRenderCallback(array(&$this, 'renderInsideText'));
            return '';
        }
        // ending
        if ($options['type'] == 'end') {
            $this->wiki->popRenderCallback();
            return '';
        }
    }

    function renderInsideText($text) {
        $text = preg_replace('/(^|\n)(?!$)/', '\1>', $text);
        return $text;
    }
}
?>