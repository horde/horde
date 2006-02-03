<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: extra Font rules renderer to size the text
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */

/**
 * Font rule render class (used for BBCode)
 * 
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 * @see        Text_Wiki::Text_Wiki_Render()
 */
class Text_Wiki_Render_Docbook_Font extends Text_Wiki_Render {

    var $conf = array(
        'role' => 'fontsize',
        'attribute' => 'condition'
        );
    
    /**
      * Renders a token into text matching the requested format.
      * process the font size option 
      *
      * @access public
      * @param array $options The "options" portion of the token (second element).
      * @return string The text rendered from the token options.
      */
    function token($options)
    {
        if ($options['type'] == 'end') {
            return '</phrase>';
        }
        if (isset($options['size'])) {
            $size = trim($options['size']);
            if (is_numeric($size)) {
                $size .= 'px';
            }
        }
        return '<phrase role="' . $this->getConf('role', 'fontsize') . '" ' .
            $this->getConf('attribute', 'condition') .'="' . $size . '">';
    }
}
?>
