<?php


class Text_Wiki_Render_Tiki_Url extends Text_Wiki_Render {

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
            if (! strlen($options['text']) || $options['href'] == $options['text']) {
                return '['.$options['href'];
            } else {
                return '['.$options['href'].'|';
            }
        }
        else if ($options['type'] == 'end') {
            return ']';
        }
        else {
            if (! strlen($options['text']) || $options['href'] == $options['text']) {
                return '['.$options['href'].']';
            } else {
                return '['.$options['href'].'|'.$options['text'].']';
            }
        }
    }
}
?>