<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Tiki: Parses for smileys / emoticons tags
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * smileys defined by symbols as ':)' , ':-)' or ':smile:'
 * The symbol is replaced with a token.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Smiley rule parser class for Tiki.
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
class Text_Wiki_Parse_Smiley extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     * 
     * @access public
     * @var string
     * @see parse()
     */
    
    var $regex =  '/\(:([^:]+):\)/';

    /**
     * Generates a replacement token for the matched text.  Token options are:
     *     'symbol' => the original marker
     *     'name' => the name of the smiley
     *     'desc' => the description of the smiley
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited token representing the smiley
     * @access public
     */
    function process(&$matches)
    {
        // tokenize
        return $this->wiki->addToken($this->rule,
            array(
                'symbol' => $matches[0],
                'name'   => $matches[1],
                'desc'   => $matches[1]
            ));
    }
}
?>
