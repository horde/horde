<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for font size tag.
 *
 * This class implements a Text_Wiki_Rule to find source text with size
 * as defined by text surrounded by [size=...] ... [/size]
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
 * Font rule parser class (with nesting) for BBCode. ([size=...]...)
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse_BBCode::Text_Wiki_Parse_BBCode()
 */
class Text_Wiki_Parse_Font extends Text_Wiki_Parse_BBCode {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex = "#\[size=(\d+)]|\[/size]#i";

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * sized text.  The text itself is left in the source.
     * - 'size' => the size indicator (by post synchro for end)
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string A delimited token to be used as a start/end
     * placeholder in the source text surrounding the text to be
     * sized.
     */
    function process(&$matches)
    {
        // end tag ?
        if (!isset($matches[1])) {
            if (!($start = $this->getStart())) {
                return $matches;
            }
            return $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'end',
                    'size' => $this->wiki->tokens[$start['tok']][1]['size']
                )
            );
        }
        return $this->addStart(array('size' => $matches[1]), $matches);
    }
}
?>
