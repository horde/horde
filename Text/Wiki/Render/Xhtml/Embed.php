<?php

class Text_Wiki_Render_Xhtml_Embed extends Text_Wiki_Render {
    
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
    	$file = $this->getConf('base') . $options['path'];
    	ob_start();
    	include($file);
    	$output = ob_get_contents();
    	ob_end_clean();
		return $output;
	}
}
?>