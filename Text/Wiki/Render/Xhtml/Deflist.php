<?php

class Text_Wiki_Render_Xhtml_Deflist extends Text_Wiki_Render {
    
    var $conf = array(
    	'css_dl' => null,
    	'css_dt' => null,
    	'css_dd' => null
    );
    
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
        $type = $options['type'];
        $pad = "    ";
        
		// pick the css type
		$css = $this->getConf('css', '');
		if ($css) {
			$css = " class=\"$css\"";
		}
		
        switch ($type) {
        
        case 'list_start':
        
			// pick the css type
			$css = $this->getConf('css_dl', '');
			if ($css) {
				$css = " class=\"$css\"";
			}
			
			// done!
            return "<dl>\n";
            break;
        
        case 'list_end':
            return "</dl>\n\n";
            break;
        
        case 'term_start':
        
			// pick the css type
			$css = $this->getConf('css_dt', '');
			if ($css) {
				$css = " class=\"$css\"";
			}
			
			// done!
            return $pad . "<dt>";
            break;
        
        case 'term_end':
            return "</dt>\n";
            break;
        
        case 'narr_start':
        
			// pick the css type
			$css = $this->getConf('css_dd', '');
			if ($css) {
				$css = " class=\"$css\"";
			}
			
			// done!
            return $pad . $pad . "<dd>";
            break;
        
        case 'narr_end':
            return "</dd>\n";
            break;
        
        default:
            return '';
        
        }
    }
}
?>