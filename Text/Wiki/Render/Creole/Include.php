<?php

class Text_Wiki_Render_Creole_Include extends Text_Wiki_Render {

    function token()
    {
        if ($options['type'] == 'start') {
            return "{{";
        }

        if ($options['type'] == 'end') {
            return "}}";
        }
    }
}
?>