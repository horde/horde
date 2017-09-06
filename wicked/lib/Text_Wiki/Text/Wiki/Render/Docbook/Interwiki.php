<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Interwiki rule end renderer for Docbook
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
 * This class renders inter wikis links in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class Text_Wiki_Render_Docbook_Interwiki extends Text_Wiki_Render {

    var $conf = array(
        'sites' => array(
            'MeatBall' => 'http://www.usemod.com/cgi-bin/mb.pl?%s',
            'Advogato' => 'http://advogato.org/%s',
            'Wiki'       => 'http://c2.com/cgi/wiki?%s'
        ),
    );


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
        $text = $options['text'];
        if (isset($options['url'])) {
            // calculated by the parser (e.g. Mediawiki)
            $href = $options['url'];
        } else {
            $site = $options['site'];
            // toggg 2006/02/05 page name must be url encoded (e.g. may contain spaces)
            $page = $this->urlEncode($options['page']);

            if (isset($this->conf['sites'][$site])) {
                $href = $this->conf['sites'][$site];
            } else {
                return $text;
            }

            // old form where page is at end,
            // or new form with %s placeholder for sprintf()?
            if (strpos($href, '%s') === false) {
                // use the old form
                $href = $href . $page;
            } else {
                // use the new form
                $href = sprintf($href, $page);
            }
        }
        return '<link xlink:href="' . $href . '">' . $text . '</link>';
    }
}
?>
