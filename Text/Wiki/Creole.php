<?php

/**
 *
 * Parse structured wiki text and render into arbitrary formats such as XHTML.
 * This is the Text_Wiki extension for Mediawiki markup
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 *
 * @package    Text_Wiki
 *
 * @author     Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 * @link       http://pear.php.net/package/Text_Wiki
 *
 * @version    CVS: $Id$
 *
 */

/**
 *
 * "Master" class for handling the management and convenience
 *
 */

require_once 'Text/Wiki.php';

/**
 *
 * Base Text_Wiki handler class extension for Creole markup
 *
 * @category   Text
 *
 * @package    Text_Wiki
 *
 * @author     Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 * @link       http://pear.php.net/package/Text_Wiki
 *
 * @see        Text_Wiki::Text_Wiki()
 *
 */

class Text_Wiki_Creole extends Text_Wiki {

    // *single newlines* are handled as in most wikis (ignored)
    // if Newline is removed from rules, they will be handled as in word-processors (meaning a paragraph break)

    var $rules = array(
        'Prefilter',
        'Delimiter',
        'Preformatted',
        'Tt',
        'Trim',
        'Break',
        'Raw',
        'Box',
        'Footnote',
        'Heading',
        'Newline',
        'Deflist',
        'Blockquote',
        'Newline',
        'Url',
        'Wikilink',
        'Image',
        //'Heading',
        'Table',
        'Center',
        'Horiz',
        'Deflist',
        'List',
        'Address',
        'Paragraph',
        'Superscript',
        'Subscript',
        'Underline',
        'Emphasis',
        'Strong',
        //'Italic',
        //'Bold',
        'Tighten'
    );

    /**
     * Constructor: just adds the path to Creole rules
     *
     * @access public
     * @param array $rules The set of rules to load for this object.
     */

    function Text_Wiki_Creole($rules = null) {
        parent::Text_Wiki($rules);
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)).'Parse/Creole');
        $this->renderingType = 'char';
        $this->setRenderConf('xhtml', 'center', 'css', 'center');
        $this->setRenderConf('xhtml', 'url', 'target', null);
    }

    function checkInnerTags(&$text) {
        $started = array();
		$i = false;
        while (($i = strpos($text, $this->delim, $i)) !== false) {
            $j = strpos($text, $this->delim, $i + 1);
            $t = substr($text, $i + 1, $j - $i - 1);
            $i = $j + 1;
            $rule = strtolower($this->tokens[$t][0]);
            $type = $this->tokens[$t][1]['type'];

            if ($type == 'start') {
				if (empty($started[$rule])) {
					$started[$rule] = 0;
				}
                $started[$rule] += 1;
            }
            else if ($type == 'end') {
                if (empty($started[$rule])) return false;

                $started[$rule] -= 1;
                if (! $started[$rule]) unset($started[$rule]);
            }
        }
        return ! (count($started) > 0);
    }
    
    function restoreRaw($text) {
		$i = false;
        while (($i = strpos($text, $this->delim, $i)) !== false) {
            $j = strpos($text, $this->delim, $i + 1);
            $t = substr($text, $i + 1, $j - $i - 1);
            $rule = strtolower($this->tokens[$t][0]);

            if ($rule == 'raw') {
                $text = str_replace($this->delim. $t. $this->delim, $this->tokens[$t][1]['text'], $text);
            }
            else {
                $i = $j + 1;
            }
        }
        return $text;
    }
}

?>
