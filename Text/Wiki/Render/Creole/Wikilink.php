<?php

class Text_Wiki_Render_Creole_Wikilink extends Text_Wiki_Render {

    /**
    *
    * Renders a token into XHTML.
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
        $dup = (($options['page'] == $options['text']) || ($options['page'] == preg_replace('/\s+/', '_', $options['text'])));
        
        if ($options['type'] == 'start') {
            if ($dup) return '[[';
            else return '[['.$options['page'].
                (strlen($options['anchor']) ? $options['anchor'] : '').
                (strlen($options['text']) && (strlen($options['page']) || strlen($options['anchor'])) ? '|' : '');
        } else if ($options['type'] == 'end') {
            if ($dup && strlen($options['anchor'])) return $options['anchor'].']]';
            else return ']]';
        } else {
            if ($dup) return '[['.
                    (strlen($options['text']) ? $options['text'] : '').
                    (strlen($options['anchor']) ? $options['anchor'] : '').
                    ']]';
            else return '[['.$options['page'].
                (strlen($options['anchor']) ? $options['anchor'] : '').
                (strlen($options['text']) && strlen($options['page']) && strlen($options['anchor']) ? '|' : '').
                (strlen($options['text']) ? $options['text'] : '').
                ']]';
        }
    }
}
?>