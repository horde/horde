<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for implied line breaks indicated by newlines.
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
 * Parses for implied line breaks indicated by newlines.
 * 
 * This class implements a Text_Wiki_Parse to remove implied line breaks in the
 * source text, usually a single carriage return in the middle of a paragraph
 * or block-quoted text.
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
class Text_Wiki_Parse_Newline extends Text_Wiki_Parse {
    
    /**
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * @var string
    * @see parse()
    */
    var $regex = '/([^\n])\n([^\n])/m';
    
    /**
    * Generates a replacement for the matched text.
    * 
    * @access public
    * @param array &$matches The array of matches from parse().
    * @return string A delimited token to be used as a placeholder in
    * the source text.
    */
    function process(&$matches)
    {
        return $matches[1] . $this->wiki->addToken($this->rule) . $matches[2];
    }
}

?>
