<?php

class Text_Wiki_Render_Xhtml_Function extends Text_Wiki_Render {
    
    var $conf = array(
    	'css_div' => null,
    	'access'  => '%s',
    	'return'  => '%s',
    	'name'    => '<strong>%s</strong>',
    	'type'    => '%s',
    	'descr'   => '<em>%s</em>',
    	'default' => 'default %s'
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
    	extract($options); // name, access, return, params
    	
    	$css = $this->formatConf(' class="%s"', $this->conf['css_div']);
    	$output = "<div$css>";
    	
    	$output .= sprintf($this->conf['access'], $access) . ' ';
    	$output .= sprintf($this->conf['return'], $return) . ' ';
    	$output .= sprintf($this->conf['name'], $name) . ' ( ';
    	
    	$list = array();
    	foreach ($params as $key => $val) {
    		$tmp = sprintf($this->conf['type'], $val['type']) . ' ';
    		$tmp .= sprintf($this->conf['descr'], $val['descr']);
    		if ($val['default']) {
    			$tmp .= ' ' . sprintf($this->conf['default'], $val['default']);
    			$tmp = "[$tmp]";
    		}
    		$list[] = $tmp;
    	}
    	$output .= implode(', ', $list) . " )</div>";
    	
    	return "\n$output\n\n";
    }
}
?>