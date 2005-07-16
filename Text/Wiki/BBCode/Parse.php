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
 * @see Text_Wiki_Parse
 * 
 * The parse() method enables the nesting of components
 * thru a custom method to synchronize "start" and "end" tags
 *
 * It also checks the done property (default true)
 * A process() method needing to "rerun" has to set it to false
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
     * Flag indicating to the parse method to synchronize nested start and end tags
     * by calling the custom method synchStartEnd() for each corresponding pair of them
     * 'type'=>'start' or 'type'=>'end' are then mandatory for this rule's tokens
     * The post synchronization function has the followolng interface:
     * function synchStartEnd(&$statok, &$endtok, $level, $stapos, $endpos)
     *   @param &array $statok the param array of start token
     *   @param &array $endtok the param array of end token
     *   @param int $level nesting depth
     *   @param int $stapos the position behind start token in source
     *   @param int $endpos the position of end token in source (after tag's data)
     *   @return null or error
     * 
     * @access private
     * @var boolean
     */
    var $_synch = false;
    
    /**
     * Constructor for this parser rule.
     * 
     * @access public
     * @param object &$obj The calling "parent" Text_Wiki object.
     * 
     */
    function Text_Wiki_Parse_BBCode(&$obj)
    {
        parent::Text_Wiki_Parse($obj);
        $this->_synch = method_exists($this, 'synchStartEnd');
    }
    
    
    /**
     * Abstract method to parse source text for matches.
     *
     * Repeats the parent parse method until done
     * Nesting rules process() can set this flag to false for redo
     * Calls the custom post synchronization if exists in the class
     *
     * @access public
     * @see Text_Wiki_Parse::parse()
     */
    function parse()
    {
        if ($this->_synch) {
            $loop = -1;
            do {
                $this->done = true;
                $tokno = $this->wiki->addToken(
                    $this->rule, array('type'=>'parseStart'.$loop), 'id_only');
                parent::parse();
                $tokend = $this->wiki->addToken(
                    $this->rule, array('type'=>'parseEnd'.$loop), 'id_only');
                $pos = $lev = 0;
                $stapos = array();
                $statok = array();
                while (++$tokno < $tokend) {
                    $find = $this->wiki->delim . $tokno . $this->wiki->delim;
                    $newpos = strpos($this->wiki->source, $find, $pos);
                    if ($this->wiki->tokens[$tokno][1]['type'] == 'start') {
                        $stapos[$lev] = $newpos + strlen($find);
                        $statok[$lev++] = $tokno;
                        continue;
                    }
                    if ($this->wiki->tokens[$tokno][1]['type'] != 'end') {
                        continue;
                    }
                    $this->synchStartEnd(
                        $this->wiki->tokens[array_pop($statok)][1],
                        $this->wiki->tokens[$tokno][1],
                        $lev, array_pop($stapos), $newpos);
                    $pos = $newpos + strlen($find);
                    $lev--;
                }
            } while (!$this->done);
        } else {
            do {
                $this->done = true;
                parent::parse();
            } while (!$this->done);
        }
    }
}
?>
