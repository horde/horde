<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Function rule end renderer for Docbook
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
 * This class renders a function description in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_Function extends Text_Wiki_Render {

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
        extract($options); // name, access, return, params, throws

        // build the baseline output
        $output = "<methodsynopsis>\n<modifier>" . $access .
            "</modifier>\n<type>" . $return . "</type>\n<methodname>" .
            $name ."</methodname>\n";

        // build the set of params
        foreach ($params as $key => $val) {
            $output .= '<methodparam><type>' . $val['type'] .
                '</type><parameter>' . $val['descr'] . '</parameter>';

            // is there a default value?
            if ($val['default']) {
                $output .= '<initializer>' . $val['default'] . '</initializer';
            }
            $output .= "</methodparam>\n";
        }

        // build the set of throws
        foreach ($throws as $key => $val) {
            $output .= '<exceptionname>' . $val['type'] . ' ' . $val['descr'] .
                    "</exceptionname>\n";
        }

        // close the method synopsis and return the output
        $output .= "</methodsynopsis>\n";
        return $output;
    }
}
?>
