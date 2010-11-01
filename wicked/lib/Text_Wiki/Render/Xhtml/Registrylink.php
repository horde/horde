<?php
/**
 * This class renders Horde Registry links.
 *
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Registrylink extends Text_Wiki_Render
{
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
    public function token($options)
    {
        try {
            $link = new Horde_Url($GLOBALS['registry']->link($options['method'], $options['args']));
        } catch (Horde_Exception $e) {
            return '';
        }

        return $link->link() . $options['title'] . '</a>';
    }
}
