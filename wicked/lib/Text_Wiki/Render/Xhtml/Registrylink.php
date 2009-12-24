<?php
/**
 * $Horde: wicked/lib/Text_Wiki/Render/Xhtml/Registrylink.php,v 1.1 2005/10/14 20:44:04 chuck Exp $
 *
 * This class renders Horde Registry links.
 *
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Registrylink extends Text_Wiki_Render {

    /**
     * Renders a token into text matching the requested format.
     *
     * @access public
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    function token($options)
    {
        $link = $GLOBALS['registry']->link($options['method'], $options['args']);
        if (is_a($link, 'PEAR_Error')) {
            return '';
        }

        return Horde::link($link) . $options['title'] . '</a>';
    }

}
