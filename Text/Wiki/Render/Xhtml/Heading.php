<?php

class Text_Wiki_Render_Xhtml_Heading extends Text_Wiki_Render {
    
    function token($options)
    {
        // get nice variable names (type, level)
        extract($options);
        
        if ($type == 'start') {
            return "<h$level>";
        }
        
        if ($type == 'end') {
            return "</h$level>\n";
        }
    }
}
?>