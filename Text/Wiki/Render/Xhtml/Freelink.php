<?php

class Text_Wiki_Render_Xhtml_Freelink extends Text_Wiki_Render {
    
    var $conf = array(
		'pages' => array(),
		'view_url' => 'http://example.com/index.php?page=%s',
		'new_url'  => 'http://example.com/new.php?page=%s',
		'new_text' => '?'
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
        // get nice variable names (page, text, anchor)
        extract($options);
        
        if (in_array($page, $this->getConf('pages'))){
        
            // the page exists, show a link to the page
            $href = $this->getConf('view_url');
            if (strpos($href, '%s') === false) {
            	// use the old form
	            $href = $href . $page . '#' . $anchor;
	        } else {
	        	// use the new form
	        	$href = sprintf($href, $page . '#' . $anchor);
	        }
            return "<a href=\"$href\">$text</a>";
            
        } else {
        
            // the page does not exist, show the page name and
            // the "new page" text
            $href = $this->getConf('new_url');
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