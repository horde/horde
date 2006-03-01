<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Parse structured wiki text and render into arbitrary formats such as XHTML.
 * This is the Text_Wiki extension for Cowiki markup
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
 * Base Text_Wiki handler class extension for Cowiki markup
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
class Text_Wiki_Cowiki extends Text_Wiki {

    function Text_Wiki_Cowiki() {
        parent::Text_Wiki();
        $paths = $this->getPath('parse');
        $this->addPath('parse', str_replace('Default', 'Cowiki', $paths[0]));
    }
}
