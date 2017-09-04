<?php

/**
* 
* This class renders an anchor target name in XHTML.
*
* @author Manuel Holtgrewe <purestorm at ggnore dot net>
*
* @author Paul M. Jones <pmjones at ciaweb dot net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Render_Tiki_Anchor extends Text_Wiki_Render {
    
    function token($options)
    {
        if ($options['type'] == 'start') {
            return '(('.$options['name'];
        }
        
        if ($options['type'] == 'end') {
            return '))';
        }
    }
}

?>