<?php

// $Id$

class Text_Wiki_Render_Doku_Toc extends Text_Wiki_Render {
    
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
        // type, id, level, count, attr
        //TOC is automatic for more than 3 headers in DokuWiki
    }
}
?>