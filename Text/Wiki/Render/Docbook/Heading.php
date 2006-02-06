<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Heading rule end renderer for Docbook
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */

/**
 * This class renders headings in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_Heading extends Text_Wiki_Render {

    /**
     * Configuration keys for this rule
     * 'sections' => array of strings, ordered list of sectioning tags,
     *               the first (mandatory) one will apply to the whole doc
     *               empty string '' means no section for this level
     * 'section_after' => string, the section optionnaly used for next, non terminal levels
     * 'section_end' => string, the section optionnaly used for the terminal level
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'sections' => array('sect1', 'sect2', 'sect3', 'sect4', 'sect5'),
        'section_after' => 'section',
        'section_final' => 'simplesect'
    );

    /**
     * Current level
     *
     * @access private
     * @var int current section level
     */
    var $_level = 0;

    /**
     * Parsed heading levels stack
     *
     * @access private
     * @var array of int parsed heading levels stack
     */
    var $_stack = array(-1);

    /**
     * Parsed heading ids stack
     *
     * @access private
     * @var array of int parsed heading ids stack
     */
    var $_id = array(-1);

    /**
     * Final sectioning to apply
     *
     * @access private
     * @var array of string section tags
     */
    var $_section = array();

     /**
     * Constructor.
     * We override the constructor to pre-process the heading tokens
     * - to correct levels as sequential 
     * - mark the terminal ones
     * - prepare the actual sections to be used
     *
     * @param object &$obj the base conversion handler
     * @return The render object
     * @access public
     */
    function Text_Wiki_Render_Docbook_Heading(&$obj)
    {
        parent::Text_Wiki_Render($obj);
        $max = 0;
        foreach ($this->wiki->getTokens('Heading') as $key => $val) {
            if ($val[1]['type'] == 'start') {
                // preceding branch finished ?
                if ($val[1]['level'] <= $this->_stack[$this->_level]) {
                    $this->wiki->tokens[$this->_id[$this->_level]][1]['terminal'] = true;
                    --$this->_level;
                }
                // more finished ?
                while ($val[1]['level'] <= $this->_stack[$this->_level]) {
                    --$this->_level;
                }
                // starting section
                $this->_stack[++$this->_level] = $val[1]['level'];
                $this->_id[$this->_level] = $key;
                if ($this->_level > $max) {
                    $max = $this->_level;
                }
            }
            // adjust level sequentially
            $val[1]['level'] = $this->_level;
            $this->wiki->setToken($key, 'Heading', $val[1]);
        }
        // finish last branch
        if ($this->_level > 0) {
            $this->wiki->tokens[$this->_id[$this->_level]][1]['terminal'] = true;
        }
        // set global sections for process closure
        $sections = $this->getConf('sections', array(''));
        $this->wiki->source =
            // will produce one only if not blank section
            $this->wiki->addToken($this->rule,
                    array('type' => 'start',
                            'level' => 0,
                            'id' => 'global',
                            'text' => '')) .
            $this->wiki->addToken($this->rule,
                    array('type' => 'end',
                            'level' => 0)) . 
            $this->wiki->source .
            // will produce nothing but the closure of preceding sections
            $this->wiki->addToken($this->rule,
                    array('type' => 'start',
                            'level' => -1));
        // prepare final sectioning
        if ( ! ($after = $this->getConf('section_after', ''))) {
            $after = $sections[count($sections) - 1];
        }
        for ($i = 0; $i <= $max; $i++) {
            if ($i < count($sections)) {
                $this->_section[$i] = $sections[$i];
            } else {
                $this->_section[$i] = $after;
            }
        }
        // clean stack
        $this->_stack = array();
        $this->_level = -1;
    }

     /**
     * Replace start heading tokens with closure of preceding sections
     * and opening corresponding section if not blank or the last level -1 one
     *
     * @param array $option the token options
     * @return string the replacement text
     * @access public
     */
    function token($options)
    {
        // get nice variable names (id, type, level, terminal)
        $terminal = false;
        extract($options);
        if ($terminal) {
            $terminal = $this->getConf('section_final', '');
        }

        if ($type == 'end') {
            return $this->_stack[$this->_level] ? "</title>\n" : '';
        }
        $output = '';
        // sections to finish ?
        while ($this->_level >= 0 && $level <= $this->_level) {
            $output .= $this->_stack[$this->_level] ? 
                    '</' . $this->_stack[$this->_level] . ">\n" : '';
            --$this->_level;
        }
        $this->_stack[++$this->_level] = $level < 0 ? '' :
                ($terminal ? $terminal : $this->_section[$level]);
        return $output . ($this->_stack[$this->_level] ?
            '<'. $this->_stack[$this->_level] . ' xml:id="' . $id .
                 "\">\n<title>" : '');
    }
}
?>
