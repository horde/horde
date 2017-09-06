<?php


class Text_Wiki_Render_Doku_Url extends Text_Wiki_Render {

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
        $page_text_options = false;
        if (isset($options['page']) && isset($options['text'])) {
            $page_text_options = $options['page'] == $options['text'];
        } elseif (empty($options['page']) && empty($options['text'])) {
            $page_text_options = true;
        }


        if ($options['type'] == 'start') {
            if (!strlen($options['text']) || $page_text_options) {
                return $options['href'];
            } else {
                return '[['.$options['href'].'|';
            }
        }
        else if ($options['type'] == 'end') {
            if (! strlen($options['text']) || $page_text_options) {
                return '';
            } else {
                return ']]';
            }
        }
        else {
            if (! strlen($options['text']) || $page_text_options) {
                return $options['href'];
            } else {
                return '[['.$options['href'].'|'.$options['text'].']]';
            }
        }
    }
}
?>
