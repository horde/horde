<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Deflist rule end renderer for Docbook
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
 * This class renders definition lists in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_Deflist extends Text_Wiki_Render {

    /**
    *
    * Renders a token into text matching the requested format.
    *
    * @access public
    *
    * @param array $options The "options" portion of the token (second
    * element).
    *
    * @return string The text rendered from the token options.
    *
    */

    function token($options)
    {
        switch ($options['type']) {

        case 'list_start':
            return "<glosslist>\n";
            break;

        case 'list_end':
            return "</glosslist>\n";
            break;

        case 'term_start':
            return '<glossentry><glossterm>';
            break;

        case 'term_end':
            return "</glossterm>\n";
            break;

        case 'narr_start':
            return '<glossdef>';
            break;

        case 'narr_end':
            return "</glossdef></glossentry>\n";
            break;

        default:
            return '';

        }
    }
}
?>
