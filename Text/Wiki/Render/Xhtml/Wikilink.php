<?php

class Text_Wiki_Render_Xhtml_Wikilink extends Text_Wiki_Render {
    
    
	var $conf = array(
		'pages' => array(),
		'view_url' => 'http://example.com/index.php?page=%s',
		'new_url'  => 'http://example.com/new.php?page=%s',
		'new_text' => '?'
	);
    
    /**
    * 
    * Renders a token into XHTML.
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
        // make nice variable names (page, anchor, text)
        extract($options);
        
        // does the page exist?
        if (in_array($page, $this->getConf('pages', array()))) {
        
            // yes, link to the page view, but we have to build
            // the HREF.  we support both the old form where
            // the page always comes at the end, and the new
            // form that uses %s for sprintf()
            $href = $this->getConf('view_url');
            
            if (strpos($href, '%s') === false) {
            	// use the old form
	            $href = $href . $page . $anchor;
	        } else {
	        	// use the new form
	        	$href = sprintf($href, $page . $anchor);
	        }
	        
            return "<a href=\"$href\">$text</a>";
            
        }
        
		// no, link to a create-page url, but only if new_url is set
		$href = $this->getConf('new_url', null);
		
		if (! $href || trim($href) == '') {
			// no useful href, return the text as it is
			return $text;
		} else {
		
            // yes, link to the page view, but we have to build
            // the HREF.  we support both the old form where
            // the page always comes at the end, and the new
            // form that uses sprintf()
            if (strpos($href, '%s') === false) {
            	// use the old form
	            $href = $href . $page;
	        } else {
	        	// use the new form
	        	$href = sprintf($href, $page);
	        }
	        
			return $text . "<a href=\"$href\">" . $this->getConf('new_text') . "</a>";
		}
    }
}
?>