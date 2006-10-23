<?php

class Text_Wiki_Render_Creole_Heading extends Text_Wiki_Render {
    function token($options)
    {
        return ($options['type'] == 'end' ? ' ' : '').
            str_pad('', 1 + $options['level'], '=').
            ($options['type'] == 'start' ? ' ' : "\n\n");
    }
}
?>