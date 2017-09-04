<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Parse structured wiki text and render into arbitrary formats such as XHTML.
 * This is the Text_Wiki extension for tikiwiki markup
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * "master" class for handling the management and convenience
 */
require_once('Text/Wiki.php');

/**
 * Base Text_Wiki handler class extension for tikiwiki markup
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki()
 */
class Text_Wiki_Tiki extends Text_Wiki {
    var $rules = array(
        'Prefilter',
        'Delimiter',
        'Code',
        'Plugin',
//        'Function',
        'Html',
        'Raw',    // Now Parsed in Plugin
        'Preformatted',    // Now Parsed in Plugin
        'Include',
        'Embed',
        'Page',
        'Anchor',
        'Heading',
        'Toc',
        'Titlebar',
        'Horiz',
        'Redirect',
        'Break',
        'Blockquote',
        'List',
        'Deflist',
        'Table',
        'Box',
        'Image',
        'Smiley',
//        'Phplookup',
        'Center',
        'Newline',
        'Paragraph',
        'Url',
        //'Freelink',
        'Colortext',
        'Wikilink',
        'Strong',
        'Bold',
        'Emphasis',
        'Italic',
        'Underline',
        'Tt',
        'Superscript',
        'Subscript',
        'Specialchar',
        'Revise',
        'Interwiki',
        'Tighten'
    );

    function Text_Wiki_Tiki($rules = null) {
        parent::Text_Wiki($rules);
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)).'Parse/Tiki');
//        $this->addPath('render', $this->fixPath(dirname(__FILE__)).'Render');
    }
}

?>
