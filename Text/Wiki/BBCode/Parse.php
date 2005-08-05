<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parse redefinition to parse nested components
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
 * Baseline rule class for BBCode parser.
 *
 * The parse() method enables the nesting of components
 * thru a custom method to synchronize "start" and "end" tags
 *
 * It also checks the done property (default true)
 * A process() method needing to "rerun" has to set it to false
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_BBCode extends Text_Wiki_Parse {

    /**
     * Flag indicating to the parse method to re-parse the source for nesting
     *
     * @access public
     * @var boolean
     */
    var $done = true;

    /**
     * Array staking the start tags as they are parsed
     * They will be correspondingly unstacked when parsing the end tags
     * Each element is an associative array:
     * 'token'=>'token number', 'tag'=>'original tag' , 'level' of nesting (starting  0)
     * and eventually specific values for this rule
     *
     * @access private
     * @var boolean
     */
    var $_start = array();

    /**
     * Adds a token id and stacks the start tag
     * The options 'type'=>'start' and 'level' will be auto-added
     *
     * @access public
     * @param array $options The extra options for this token
     * @param string $tag The original tag text
     * @return string The delemited token id
     */
    function addStart($options = array(), $tag = '')
    {
        $options['type'] = 'start';
        $options['level'] = count($this->_start);
        $tok = $this->wiki->addToken($this->rule, $options, true);
        $this->_start[] = array('tok'=>$tok, 'level'=>$options['level'], 'tag'=>$tag);

        // return the token number with delimiters
        return $this->wiki->delim . $tok . $this->wiki->delim;
    }

    /**
     * Pops a start token from the stack
     *
     * @access public
     * @return mixed null if error, the associative array describing the start token if OK
     */
    function getStart()
    {
        // pops and returns the last element in stack
        return array_pop($this->_start);
    }

    /**
     * Abstract method to parse source text for matches.
     *
     * Repeats the parent parse method until done
     * Nesting rules process() can set this flag to false for redo
     * Cares of orphan start tags if any
     *
     * @access public
     * @see Text_Wiki_Parse::parse()
     */
    function parse()
    {
        do {
            $this->done = true;
            $this->_start = array();
            parent::parse();
            foreach ($this->_start as $elt) { // orphan start tags
                $this->wiki->source = str_replace(
                        $this->wiki->delim . $elt['tok'] . $this->wiki->delim,
                        $elt['tag'],
                        $this->wiki->source);
            }
        } while (!$this->done);
    }
}
?>
