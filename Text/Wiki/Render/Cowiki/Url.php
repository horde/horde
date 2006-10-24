<?php


class Text_Wiki_Render_CoWiki_Url extends Text_Wiki_Render {

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
        extract($options);
        if ($type == 'start') {
            if (! strlen($text) || $href == $text) {
                return $href;
            } else {
                return '(('.$href.')(';
            }
        }
        else if ($type == 'end') {
            if (! strlen($text) || $href == $text) {
                return '';
            } else {
                return '))';
            }
        }
        else {
            if (! strlen($text) || $href == $text) {
                return $href;
            } else {
                return '(('.$href.')('.$text.'))';
            }
        }
    }
}
?>