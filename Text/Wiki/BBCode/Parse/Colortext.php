<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for color text.
 * 
 * This class implements a Text_Wiki_Rule to find source text coloured
 * as defined by text surrounded by [color=...] ... [/color]
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
class Text_Wiki_Parse_Colortext extends Text_Wiki_Parse_BBCode {
    
    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     * We match either [color..] or [/color], will be post synchronized
     * 
     * @access public
     * @var string
     * @see parse()
     * 
     */
    
    var $regex = "'(?:\[color=(aqua|black|blue|fuchsia|gray|green|lime|maroon|navy|olive|purple|red|silver|teal|white|yellow|\#[0-9a-f]{6})]|\[/color])'i";
    
    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * colored text.  The text itself is left in the source.
     * - 'color' => the color indicator
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
                    'color' => ''
                )
            );
        }

        // needs to withdraw leading # as renderer put it in
        $color = $matches[1]{0} == '#' ? substr($matches[1], 1) : $matches[1];
        return $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'start',
                'color' => $color
            )
        );
    }
    
    /**
     * Post parsing, start and end synchro
     * 
     * That will just report 'color' => the color indicator
     * from start token to end token
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
        $endtok['color'] = $statok['color'];
        return null;
    }
}
?>
