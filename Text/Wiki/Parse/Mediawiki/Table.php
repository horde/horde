<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for tables.
 *
 * This class implements a Text_Wiki_Rule to find tables in pipe syntax
 * {| ... |- ... | ... |}
 * On parsing, the text itself is left in place, but the starting and ending
 * tags for table, rows and cells are replaced with tokens. (nested tables enabled)
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
 * Table rule parser class for Mediawiki.
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
class Text_Wiki_Parse_Table extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex =  '#^\{\|(.*?)^((?:((?R))|.)*?)^\|}#msi';

    /**
     * The regular expression used in second stage to find table's rows
     * used by process() to call back processRows()
     *
     * @access public
     * @var string
     * @see process()
     * @see processRows()
     */
    var $regexRows =  '#^(\||!)-(.*?)^(.*?)(?=^(?:\|-|!-|\z))#msi';

    /**
     * The regular expression used in third stage to find rows's cells
     * used by processRows() to call back processCells()
     *
     * @access public
     * @var string
     * @see process()
     * @see processCells()
     */
    var $regexCells =  '#(^\||^!|\|\||!!)( [^|\n]*? \|(?!\|))?(.*?)(?=^\||^!|\|\||!!|\z)#msi';

    /**
     * The current table nesting depth, starts by zero
     *
     * @access private
     * @var int
     */
    var $_level = 0;

    /**
     * The count of rows for this level
     *
     * @access private
     * @var array of int
     */
    var $_countRows = array();

    /**
     * The max count of cells for this level
     *
     * @access private
     * @var array of int
     */
    var $_maxCells = array();

    /**
     * The count of cells for each row
     *
     * @access private
     * @var array of int
     */
    var $_countCells = array();

    /**
     * The type of table for this level ('bullet' or 'number')
     *
     * @access private
     * @var int
    var $_type = array();
     */

    /**
     * Generates a replacement for the matched text. Returned token options are:
     * 'type' =>
     *     'table_start' : the start of a bullet list
     *     'table_end'   : the end of a bullet list
     *     'row_start'   : the start of a number list
     *     'row_end'     : the end of a number list
     *     'cell_start'  : the start of item text (bullet or number)
     *     'cell_end'    : the end of item text (bullet or number)
     *     'caption'     : the associated caption
     *
     * 'level' => the table nesting level (starting zero) ('table_start')
     *
     * 'rows' => the number of rows in the table ('table_start')
     *
     * 'cols' => the number of columns in the table or rows
     *           ('table_start' and 'row_start')
     *
     * 'span' => column span ('cell_start')
     *
     * 'row_span' => row span ('cell_start')
     *
     * 'attr' => header optional attribute flag ('row_start' or 'cell_start')
     *
     * 'format' => table, row or cell optional styling ('xxx_start')
     *
     * @param array &$matches The array of matches from parse().
     * @return string the original text with tags replaced by delimited tokens
     * which point to the the token array containing their type and definition
     * @access public
     */
    function process(&$matches)
    {//var_dump($matches);echo '<br />';
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
        $this->_countRows[$this->_level] = $this->_maxCells[$this->_level] = 0;
        $this->_countCells[$this->_level] = array();
        $sub = preg_replace_callback(
            $this->regexRows,
            array(&$this, 'processRows'),
            $expsub
        );
        $param = array(
                'type'  => 'table_start',
                'level' => $this->_level,
                'rows' => $this->_countRows[$this->_level],
                'cols' => $this->_maxCells[$this->_level]
        );
        if ($matches[1]) {
            $param['format'] = $matches[1];
        }
        $ret = $this->wiki->addToken($this->rule, $param );
        $param['type'] = 'table_end';
        return $ret . $sub . $this->wiki->addToken($this->rule, $param );
    }

    /**
     * Generates a replacement for the matched rows. Token options are:
     * 'type' =>
     *     'row_start'   : the start of a row
     *     'row_end'     : the end of a row
     *
     * 'order' => the row order in the table
     *
     * 'cols' => the count of cells in the row
     *
     * 'attr' => header optional attribute flag
     *
     * 'format' => row optional styling
     *
     * @param array &$matches The array of matches from process() callback.
     * @return string 2 delimited tokens pointing the row params
     * and containing the cells-parsed block of text between the tags
     * @access public
     */
    function processRows(&$matches)
    {//var_dump($matches);echo '<br />';
        $this->_countCells[$this->_level][$this->_countRows[$this->_level]] = 0;
        $sub = preg_replace_callback(
            $this->regexCells,
            array(&$this, 'processCells'),
            $matches[3]
        );
        $param = array(
                'type'  => 'row_start',
                'order' => $this->_countRows[$this->_level],
                'cols' => $this->_countCells[$this->_level][$this->_countRows[$this->_level]++]
        );
        if ($matches[1] == '!') {
            $param['attr'] = 'header';
        }
        if ($matches[2]) {
            $param['format'] = $matches[2];
        }
        if ($this->_maxCells[$this->_level] < $param['cols']) {
            $this->_maxCells[$this->_level] = $param['cols'];
        }
        $ret = $this->wiki->addToken($this->rule, $param );
        $param['type'] = 'row_end';
        return $ret . $sub . $this->wiki->addToken($this->rule, $param );
    }

    /**
     * Generates a replacement for the matched cells. Token options are:
     * 'type' =>
     *     'cell_start'   : the start of a row
     *     'cell_end'     : the end of a row
     *
     * 'order' => the cell order in the row
     *
     * 'cols' => the count of cells in the row
     *
     * 'span' => column span
     *
     * 'row_span' => row span
     *
     * 'attr' => header optional attribute flag
     *
     * 'format' => cell optional styling
     *
     * @param array &$matches The array of matches from processRows() callback.
     * @return string 2 delimited tokens pointing the cell params
     * and containing the block of text between the tags
     * @access public
     */
    function processCells(&$matches)
    {//var_dump($matches);echo '<br />';
        $param = array(
                'type'  => 'cell_start',
                'attr'  => $matches[1]{0} == '!' ? 'header': null,
                'span'  => 1,
                'order' => $this->_countCells[$this->_level][$this->_countRows[$this->_level]]++
        );
        if ($matches[1]{0} == '!') {
            $param['attr'] = 'header';
        }
        if ($matches[2]) {
            $param['format'] = $matches[2];
        }
        $ret = $this->wiki->addToken($this->rule, $param );
        $param['type'] = 'cell_end';
        return $ret . $matches[3] . $this->wiki->addToken($this->rule, $param );
    }
}
?>
