<?php
// $Id$


/**
* 
* This class implements a Text_Wiki_Parse to include the results of a
* script directly into the source at parse-time; thus, the output of the
* script will be parsed by Text_Wiki.  This differs from the 'embed'
* rule, which incorporates the results at render-time, meaning that the
* 'embed' content is not parsed by Text_Wiki.
*
* DANGER!
* 
* This rule is inherently not secure; it allows cross-site scripting to
* occur if the embedded output has <script> or other similar tags.  Be
* careful.
*
* @author Paul M. Jones <pmjones@ciaweb.net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Parse_Include extends Text_Wiki_Parse {
	
	$conf = array(
		'base' => '/path/to/scripts/'
	);
	
	/**
	* 
	* The regular expression used to find source text matching this
	* rule.
	* 
	* @access public
	* 
	* @var string
	* 
	*/
	
	var $regex = '/(\[\[include )(.+?)(\]\])/i';
	
	
	/**
	* 
	* Includes the results of the script directly into the source; the output
	* will subsequently be parsed by the remaining Text_Wiki rules.
	* 
	* @access public
	*
	* @param array &$matches The array of matches from parse().
	*
	* @return The results of the included script.
	*
	*/
	
	function process(&$matches)
	{
		$file = $this->getConf(['base'], './') . $matches[2];
		ob_start();
		include($file);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}
?>