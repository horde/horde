<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for bold text.
 *
 * This class implements a Text_Wiki_Rule to find source text marked for
 * strong emphasis (bold) as defined by text surrounded by [b] ... [/b]
 * On parsing, the text itself is left in place, but the starting and ending
 * tags are replaced with tokens.
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
 * Bold text rule parser class for BBCode.
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
class Text_Wiki_Parse_Bold extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex =  "#\[b](.*?)\[/b]#i";


    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * emphasized text.  The text itself is left in the source.
     *
     * @param array &$matches The array of matches from parse().
     * @return A pair of delimited tokens to be used as a placeholder in
     * the source text surrounding the text to be emphasized.
     * @access public
     */
    function process(&$matches)
    {
        $start = $this->wiki->addToken($this->rule, array('type' => 'start'));
        $end = $this->wiki->addToken($this->rule, array('type' => 'end'));
        return $start . $matches[1] . $end;
    }
}
