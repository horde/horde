<?php

class Text_Wiki_Render_Creole_Blockquote extends Text_Wiki_Render {

    var $css_stack = array();
    
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
			if (empty($options['css'])) $options['css'] = '';
            array_push($this->css_stack, $options['css']);
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
        $text = trim($text);
        if (array_pop($this->css_stack) == 'remark') {
            $text = preg_replace('/(^|\n)([\>\:]*) */', '\1:\2 ', $text);
        }
        else {
            $text = preg_replace('/(^|\n)([\>\:]*) */', '\1>\2 ', $text);
        }
        return $text . "\n\n";
    }
}
?>