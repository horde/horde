<?php

class Text_Wiki_Render_Tiki_Code extends Text_Wiki_Render {
    
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
        $ret = '{CODE(';
        if ($options['attr']['type']) {
            $ret .= 'colors=>'.$options['attr']['type'];
        }
        return $ret.")}\n".$options['text']."\n{CODE}";
    }
}
?>