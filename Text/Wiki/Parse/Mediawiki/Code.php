<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for text marked as a code example block.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Parses for text marked as a code example block.
 * 
 * This class implements a Text_Wiki_Parse to find sections marked as code
 * examples.  Blocks are marked as the string <code> on a line by itself,
 * followed by the inline code example, and terminated with the string
 * </code> on a line by itself.  The code example is run through the
 * native PHP highlight_string() function to colorize it, then surrounded
 * with <pre>...</pre> tags when rendered as XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @author     Moritz Venn <ritzmo@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Code extends Text_Wiki_Parse {
    
    /**
    * The regular expression used to find source text matching this
    * rule.
    * 
    * @access public
    * @var string
    */
    var $regex = ';<code(\s[^>]*)?>(?:<pre>)?\n?((?:(?R)|.)*?)(?:</pre>)?\n?</code>;msi';

    /**
    * Generates a token entry for the matched text.  Token options are:
    * 'text' => The full matched text, not including the <code></code> tags.
    * 
    * @access public
    * @param array &$matches The array of matches from parse().
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    */
    function process(&$matches)
    {
        // are there additional attribute arguments?
        $args = trim($matches[1]);
        
        if ($args == '') {
            $options = array(
                'text' => $matches[2],
                'attr' => array('type' => '')
            );
        } else {
        	// get the attributes...
        	$attr = $this->getAttrs($args);
        	
        	// ... and make sure we have a 'type'
        	if (! isset($attr['type'])) {
        		$attr['type'] = '';
        	}
        	
        	// retain the options
            $options = array(
                'text' => $matches[2],
                'attr' => $attr
            );
        }

        // Can't find out what $matches[3] is meant to include but keep it if found
        if(isset($matches[3])) {
            return $this->wiki->addToken($this->rule, $options) . $matches[3];
        }
        else {
            return $this->wiki->addToken($this->rule, $options);
        }
    }
}
?>
