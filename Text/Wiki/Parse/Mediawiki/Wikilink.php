<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for links to wiki pages.
 *
 * Text_Wiki rule parser to find Wikilinks: links to Wiki pages
 * as defined by text surrounded by double brackets [[]]
 * Translated are the link itself, the section (anchor) and alternate text
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Wikilinks rule parser class for Mediawiki.
 * This class implements a Text_Wiki_Parse to find links to wiki pages marked
 * in source by text surrounded by 2 opening/closing brackets as 
 * [[Wiki page name#Section|Alternate text]]
 * On parsing, the link is replaced with a token.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Wikilink extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see Text_Wiki_Parse::parse()
     */
    var $regex = '/\[\[(.+?)(?:#(.*?))?(?:\|(.*?))]]/ms';

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'page' => the name of the target wiki page
     * -'anchor' => the optional section in it
     * - 'text' => the optional alternate link text
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string Delimited by start/end tokens to be used as
     * placeholder in the source text surrounding the text to be emphasized.
     */
    function process(&$matches)
    {
        // set the options
        $options = array(
            'page'   => $matches[1],
            'anchor' => (empty($matches[2]) ? '' : $matches[2]),
            'text'   => (empty($matches[3]) ? '' : $matches[3])
        );

        // create and return the replacement token and preceding text
        return $this->wiki->addToken($this->rule, $options); // . $matches[7];
    }
}
?>
