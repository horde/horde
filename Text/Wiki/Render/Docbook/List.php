<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * List rule end renderer for Docbook
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
 * This class renders bullet and ordered lists in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_List extends Text_Wiki_Render {

    var $conf = array(
        'mark' => null
    );

    var $numeration = array(
        '1' => 'arabic',
        'i' => 'lowerroman',
        'I' => 'upperroman',
        'a' => 'loweralpha',
        'A' => 'upperalpha',
    );

    /**
    *
    * Renders a token into text matching the requested format.
    *
    * This rendering method is syntactically and semantically compliant
    * with DocBook in that sub-lists are part of the previous list item.
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
        // make nice variables (type, level, count)
        extract($options);

        switch ($options['type']) {

        case 'bullet_list_start':
            return '<itemizedlist' . (($mark = $this->getConf('mark', null)) ?
                ' mark="' . $mark . '"' : '') . ">\n";

        case 'bullet_list_end':
            return "</itemizedlist>\n";

        case 'number_list_start':
            if (empty($format) || !isset($this->numeration[$format])) {
                $format = '';
            } else  {
                $format = ' numeration="' . $this->numeration[$format] . '"';
            }
            return '<orderedlist' . $format . ">\n";

        case 'number_list_end':
            return "</orderedlist>\n";

        case 'bullet_item_start':
        case 'number_item_start':
            return "<listitem>\n";

        case 'bullet_item_end':
        case 'number_item_end':
            return "</listitem>\n";

        default:
            return '';
            break;
        }
    }
}
?>
