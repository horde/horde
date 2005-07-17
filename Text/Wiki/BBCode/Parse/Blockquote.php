<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for block-quoted text.
 * 
 * This class implements a Text_Wiki_Rule to find source text block-quoted
 * as defined by text surrounded by [quote="author"] ... [/quote] (author optional)
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
 * Block-quoted text rule parser class (with nesting) for BBCode.
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
class Text_Wiki_Parse_Blockquote extends Text_Wiki_Parse_BBCode {
    
    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     * We match either [color..] or [/color], will be post synchronized
     * 
     * @access public
     * @var string
     * @see Text_Wiki_Parse_BBCode::parse()
     */
    
    var $regex = '#(?:(\[quote(?:=\s*"(.*?)")?\s*])|\[/quote])#i';
    
    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * block-quoted text.  The text itself is left in the source.
     * - 'name' => the author indicator 'optional) (by post synchro for end)
     * 
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string A delimited token to be used as a start/end
     * placeholder in the source text surrounding the text to be
     * colored.
     */
    function process(&$matches)
    {
        // end tag ?
        if (!isset($matches[1])) {
            return $this->wiki->addToken(
                $this->rule, 
                array(
                    'type' => 'end',
                )
            );
        }

        // builds the option array
        $options = array('type' => 'start');
        if (isset($matches[2])) {
            $options['name'] = $matches[2];
        }

        return $this->wiki->addToken($this->rule, $options);
    }
    
    /**
     * Post parsing, start and end synchro
     * 
     * That will report 'name' => the author indicator if present
     * from start token to end token
     * and set the level of nesting
     *
     * @access public
     * @param &array $statok the param array of start token
     * @param &array $endtok the param array of end token
     * @param int $level nesting depth
     * @param int $stapos the position behind start token in source
     * @param int $endpos the position of end token in source (after tag's data)
     * @return null or error
     */
    function synchStartEnd(&$statok, &$endtok, $level, $stapos, $endpos)
    {
        $endtok['level'] = $statok['level'] = $level;
        if (isset($statok['name'])) {
            $endtok['name'] = $statok['name'];
        }
        return null;
    }
}
?>
