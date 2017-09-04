<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for text marked as "raw" (i.e., to be rendered as-is).
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
 * Parses for text marked as "raw" (i.e., to be rendered as-is).
 * 
 * This class implements a Text_Wiki rule to find sections of the source
 * text that are not to be processed by Text_Wiki.  These blocks of "raw"
 * text will be rendered as they were found.
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
class Text_Wiki_Parse_Raw extends Text_Wiki_Parse {
    
    
    /**
    * The regular expression used to find source text matching this
    * rule.
    * 
    * @access public
    * @var string
    */
    var $regex = "/<nowiki>\n?(.*?)\n?<\/nowiki>/ms";
    
    
    /**
    * Generates a token entry for the matched text.  Token options are:
    * 'text' => The full matched text.
    * 
    * @access public
    * @param array &$matches The array of matches from parse().
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    */
    function process(&$matches)
    {
        $options = array('text' => $matches[1]);
        return $this->wiki->addToken($this->rule, $options);
    }
}
?>
