<?php
class Text_Wiki_Render_Xhtml_Image extends Text_Wiki_Render {

	var $conf = array(
		'base' => '/',
		'css'  => null,
		'css_href' => null
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
    	$src = '"' .
    		$this->getConf('base', '/') .
    		$options['src'] . '"';
    	
    	if (isset($options['attr']['link'])) {
    	
			// this image has a link ... idea from Stephane Le Solliec,
			// stephane@metacites.net
			if (strpos($options['attr']['link'], '://')) {
				// it's a URL
				$href = $options['attr']['link'];
			} else {
				// it's a WikiPage
				$href = $this->wiki->getRenderConf('xhtml', 'wikilink', 'view_url') .
					$options['attr']['link'];
			}
			
    	} else {
    		// image is not linked
    		$href = null;
    	}
    	
		// stephane@metacites.net -- 25/07/2004 
		// we make up an align="center" value for the <img> tag
		// ok, this value doesn't exist, but USERS often want it, 
		// and as wiki syntax if for USERS who don't know much of 
		// html and is here to make life easy for THEM, we create
		// it. Proficient html coders will understand the trick.
		if (isset($options['attr']['align']) &&
			$options['attr']['align'] == 'center') {
			
	    	unset($options['attr']['align']);
	    	
	    	if (! isset($options['attr']['style'])) {
	    		$options['attr']['style'] = '';
	    	}
	    	
			$options['attr']['style'] .= ' display: block; margin-left: auto; margin-right: auto;';
		}
		
		// stephane@metacites.net -- 25/07/2004 
		// try to guess width and height
		if (! isset($options['attr']['width']) &&
			! isset($options['attr']['height'])) {
			
			$imageFile = trim($src,'"');
			if (strpos($src,'://')) {
				// discard protocol
				$imageFile = substr($imageFile,strpos($src,'://')+3); 
				
				// discard hostname but keep the leading slash
				$imageFile = substr($imageFile,strpos($imageFile,'/'));  
			}
			
			$imageSize = @getimagesize($_SERVER['DOCUMENT_ROOT'] . $imageFile);
			
			if (is_array($imageSize)) {
				$options['attr']['width'] = $imageSize[0];
				$options['attr']['height'] = $imageSize[1];
			}
			
		}
    	// unset these so they don't show up as attributes
    	unset($options['attr']['link']);
    	
    	$attr = '';
    	$alt = false;
    	foreach ($options['attr'] as $key => $val) {
    		if (strtolower($key) == 'alt') {
    			$alt = true;
    		}
    		$attr .= " $key=\"$val\"";
    	}
    	
		// always add an "alt" attribute per Stephane Solliec
		if (! $alt) {
			$attr .= ' alt="' . basename($options['src']) . '"';
		}
		
		if ($href) {
			return "<a href=\"$href\"><img src=$src$attr/></a>";
		} else {
			return "<img src=$src$attr/>";
		}
	}
}
?>