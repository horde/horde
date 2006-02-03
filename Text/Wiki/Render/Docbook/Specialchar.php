<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Specialchar rule end renderer for Docbook
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
 * This class renders special characters in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_SpecialChar extends Text_Wiki_Render {

    var $types = array('~bs~' => '&#92;',
                       '~hs~' => '&nbsp;',
                       '~amp~' => '&amp;',
                       '~ldq~' => '&ldquo;',
                       '~rdq~' => '&rdquo;',
                       '~lsq~' => '&lsquo;',
                       '~rsq~' => '&rsquo;',
                       '~c~' => '&copy;',
                       '~--~' => '&mdash;',
                       '" -- "' => '&mdash;',
                       '&quot; -- &quot;' => '&mdash;',
                       '~lt~' => '&lt;',
                       '~gt~' => '&gt;');

    function token($options)
    {
        if (isset($this->types[$options['char']])) {
            return $this->types[$options['char']];
        } else {
            return '&#'.substr($options['char'], 1, -1).';';
        }
    }
}

?>
