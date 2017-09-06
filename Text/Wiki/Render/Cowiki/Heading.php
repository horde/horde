<?php

class Text_Wiki_Render_CoWiki_Heading extends Text_Wiki_Render {
    function token($options)
    {
        if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '+').' ';
        } else {
            return '';
        }
    }
}
?>