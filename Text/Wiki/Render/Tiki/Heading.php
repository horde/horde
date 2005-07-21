<?php

class Text_Wiki_Render_Tiki_Heading extends Text_Wiki_Render {
    function token($options)
    {
        return ($options['type'] == 'end' ? ' ' : "\n").
            str_pad('', $options['level'], '!').
            ($options['type'] == 'start' ? ' ' : "\n");
    }
}
?>