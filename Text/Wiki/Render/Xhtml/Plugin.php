<?php
class Text_Wiki_Render_Xhtml_Plugin extends Text_Wiki_Render {

    /**
    *
    * Renders a token into text matching the requested format.
    * Plugins produce wiki markup so are processed by parsing, no tokens produced
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
        return '';
    }
}
?>
