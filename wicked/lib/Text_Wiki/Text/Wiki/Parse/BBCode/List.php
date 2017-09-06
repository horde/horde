<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for code blocks.
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * bulleted or numbered lists as defined by text surrounded by [list] [*] ... [/list]
 * Numebering is obtained thru [list=1] or [list=a] defining the first item "number"
 * On parsing, the text itself is left in place, but the starting, element and ending
 * tags are replaced with tokens. (nested lists enabled)
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
 * List rule parser class for BBCode.
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
class Text_Wiki_Parse_List extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex =  "#\[list(?:=(.+?))?]\n?((?:((?R))|.)*?)\[/list]\n?#msi";

    /**
     * The regular expression used in second stage to find list's elements
     * used by process() to call back processElement()
     *
     * @access public
     * @var string
     * @see process()
     * @see processElement()
     */
    var $regexElement =  '#\[\*](.*?)(?=\[\*]|$)\n?#msi';

    /**
     * The current list nesting depth, starts by zero
     *
     * @access private
     * @var int
     */
    var $_level = 0;

    /**
     * The count of items for this level
     *
     * @access private
     * @var int
     */
    var $_count = array();

    /**
     * The type of list for this level ('bullet' or 'number')
     *
     * @access private
     * @var int
     */
    var $_type = array();

    /**
     * Generates a replacement for the matched text. Returned token options are:
     * 'type' =>
     *     'bullet_list_start' : the start of a bullet list
     *     'bullet_list_end'   : the end of a bullet list
     *     'number_list_start' : the start of a number list
     *     'number_list_end'   : the end of a number list
     *     'item_start'   : the start of item text (bullet or number)
     *     'item_end'     : the end of item text (bullet or number)
     *     'unknown'      : unknown type of list or item
     *
     * 'level' => the indent level (0 for the first level, 1 for the
     * second, etc)
     *
     * 'count' => the list item number at this level. not needed for
     * xhtml, but very useful for PDF and RTF.
     *
     * 'format' => the optional enumerating type : A, a, I, i, or 1 (default)
     *             as HTML <ol> tag's type attribute (only for number_... type)
     *
     * 'key' => the optional starting number/letter (not for items)
     *
     * @param array &$matches The array of matches from parse().
     * @return A delimited token to be used as a placeholder in
     * the source text and containing the original block of text
     * @access public
     */
    function process(&$matches)
    {
        if (!empty($matches[3])) {
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
        if ($matches[1]) {
            $this->_type[$this->_level] = 'number';
            if (is_numeric($matches[1])) {
                $format = '1';
                $key = $matches[1] + 0;
            } elseif (($matches[1] == 'i') || ($matches[1] == 'I')) {
                $format = $matches[1];
            } else {
                $format =
                    ($matches[1] >= 'a') && ($matches[1] <='z') ? 'a' : 'A';
                $key = $matches[1];
            }
        } else {
            $this->_type[$this->_level] = 'bullet';
        }
        $this->_count[$this->_level] = -1;
        $sub = preg_replace_callback(
            $this->regexElement,
            array(&$this, 'processElement'),
            $expsub
        );
        $param = array(
                'level' => $this->_level,
                'count' => $this->_count[$this->_level] );
        $param['type'] = $this->_type[$this->_level].'_list_start';
        if (isset($format)) {
            $param['format'] = $format;
        }
        if (isset($key)) {
            $param['key'] = $key;
        }
        $ret = $this->wiki->addToken($this->rule, $param );
        $param['type'] = $this->_type[$this->_level].'_list_end';
        return $ret . $sub . $this->wiki->addToken($this->rule, $param );
    }

    /**
     * Generates a replacement for the matched list elements. Token options are:
     * 'type' =>
     *     '[listType]_item_start'   : the start of item text (bullet or number)
     *     '[listType]_item_end'     : the end of item text (bullet or number)
     *  where [listType] is bullet or number
     *
     * 'level' => the indent level (0 for the first level, 1 for the
     * second, etc)
     *
     * 'count' => the item ordeer at this level.
     *
     * @param array &$matches The array of matches from parse().
     * @return A delimited token to be used as a placeholder in
     * the source text and containing the original block of text
     * @access public
     */
    function processElement(&$matches)
    {
        return $this->wiki->addToken($this->rule, array(
                    'type' => $this->_type[$this->_level] . '_item_start',
                    'level' => $this->_level,
                    'count' =>  ++$this->_count[$this->_level]) ) .
               rtrim($matches[1]) .
               $this->wiki->addToken($this->rule, array(
                    'type' => $this->_type[$this->_level] . '_item_end',
                    'level' => $this->_level,
                    'count' =>  $this->_count[$this->_level]) );
    }
}
