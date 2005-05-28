<?php

class Text_Wiki_Render_Xhtml extends Text_Wiki_Render {
    
    var $conf = array(
    	'translate' => HTML_ENTITIES,
    	'quotes'    => ENT_COMPAT,
    	'charset'   => 'ISO-8859-1'
    );
    
    function pre()
    {
        // attempt to translate HTML entities in the source.
        // get the config options.
        $type = $this->getConf('translate', HTML_ENTITIES);
        $quotes = $this->getConf('quotes', ENT_COMPAT);
        $charset = $this->getConf('charset', 'ISO-8859-1');
        
        // have to check null and false because HTML_ENTITIES is a zero
        if ($type !== null && $type !== false && $type == HTML_ENTITIES) {
        
			// keep a copy of the translated version of the delimiter
			// so we can convert it back.
			$new_delim = htmlentities($this->wiki->delim, $quotes, $charset);
			
			// convert the entities
			$this->wiki->source = htmlentities(
				$this->wiki->source,
				$quotes,
				$charset
			);
			
			// re-convert the delimiter
			$this->wiki->source = str_replace(
				$new_delim, $this->wiki->delim, $this->wiki->source
			);
			
		} elseif ($type == HTML_SPECIALCHARS) {
			
			// keep a copy of the translated version of the delimiter
			// so we can convert it back.
			$new_delim = htmlspecialchars($this->wiki->delim, $quotes,
			    $charset);
			
			// convert the special chars
			$this->wiki->source = htmlspecialchars(
				$this->wiki->source,
				$quotes,
				$charset
			);
			
			// re-convert the delimiter
			$this->wiki->source = str_replace(
				$new_delim, $this->wiki->delim, $this->wiki->source
			);
		}
    }
    
    function post()
    {
        return;
    }
    
}
?>