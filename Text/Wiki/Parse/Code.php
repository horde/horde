<?php
// $Id$


/**
* 
* This class implements a Text_Wiki_Parse to find sections marked as code
* examples.  Blocks are marked as the string <code> on a line by itself,
* followed by the inline code example, and terminated with the string
* </code> on a line by itself.  The code example is run through the
* native PHP highlight_string() function to colorize it, then surrounded
* with <pre>...</pre> tags when rendered as XHTML.
*
* @author Paul M. Jones <pmjones@ciaweb.net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Parse_Code extends Text_Wiki_Parse {
	
	
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
	
	var $regex = '/^(\<code( .+)?\>)\n(.+)\n(\<\/code\>)(\s|$)/Umsi';
	
	
	/**
	* 
	* Generates a token entry for the matched text.  Token options are:
	* 
	* 'text' => The full matched text, not including the <code></code> tags.
	* 
	* @access public
	*
	* @param array &$matches The array of matches from parse().
	*
	* @return A delimited token number to be used as a placeholder in
	* the source text.
	*
	*/
	
	function process(&$matches)
	{
		// are there additional attribute arguments?
		$args = trim($matches[2]);
		
		if ($args == '') {
			$options = array(
				'text' => $matches[3],
				'attr' => array('type' => '')
			);
		} else {
			$options = array(
				'text' => $matches[3],
				'attr' => $this->getAttrs($args)
			);
		}
		
		return $this->wiki->addToken($this->rule, $options) . $matches[5];
	}
}
?>