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
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Blockquote extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     * We match [quote=..] ... [/quote] with nesting
     *
     * @access public
     * @var string
     * @see Text_Wiki_Parse::parse()
     */
    var $regex = '#\[quote(?:=\s*"(.*?)")?\s*]((?:((?R))|.)*?)\[/quote]#msi';

    /**
     * The current quote nesting depth, starts by zero
     *
     * @access private
     * @var int
     */
    var $_level = 0;

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the block-quoted text.
     * The text itself is left in the source but may content bested blocks
     * - 'level' => the level of nesting (starting 0)
     * - 'name' => the author indicator (optional)
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited by start/end tokens to be used as
     * placeholder in the source text surrounding the text to be quoted.
     * @access public
     */
    function process(&$matches)
    {
        // nested block ?
        if (array_key_exists(3, $matches)) {
            $this->_level++;
            $expsub = preg_replace_callback(
                $this->regex,
                array(&$this, 'process'),
                $matches[2]
            );
            $this->_level--;
        } else {
            $expsub = $matches[2];
        }

        // builds the option array
        $options = array('type' => 'start', 'level'=>$this->_level);
        if (isset($matches[1])) {
            $options['name'] = $matches[1];
        }
        $statok = $this->wiki->addToken($this->rule, $options);
        $options['type'] = 'end';
        return $statok . $expsub . $this->wiki->addToken($this->rule, $options);
    }
}
