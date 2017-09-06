<?php

class Text_Wiki_Render_Doku_Heading extends Text_Wiki_Render {
    function token($options)
    {
        return ($options['type'] == 'end' ? ' ' : "\n").
            str_pad('', 7 - $options['level'], '=').
            ($options['type'] == 'start' ? ' ' : '');
    }
}
?>