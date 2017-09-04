<?php

class Text_Wiki_Render_Doku_Newline extends Text_Wiki_Render {
    
    
    function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}

?>