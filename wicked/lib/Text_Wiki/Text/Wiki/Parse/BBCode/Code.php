<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for code blocks.
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * code blocks as defined by text surrounded by [code] ... [/code]
 * On parsing, the text itself is left in place, but the starting and ending
 * tags are replaced with tokens. (nested blocks ignored)
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Code block rule parser class for BBCode.
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
class Text_Wiki_Parse_Code extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex =  "#\[code]((?:(?R)|.)*?)\[/code]#msi";


    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'text' => the contained text
     * - 'attr' => type empty
     *
     * @param array &$matches The array of matches from parse().
     * @return A delimited token to be used as a placeholder in
     * the source text and containing the original block of text
     * @access public
     */
    function process(&$matches)
    {
        return $this->wiki->addToken($this->rule, array(
                    'text' => $matches[1],
                    'attr' => array('type' => '') ) );
    }
}
