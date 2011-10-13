<?php
/**
 * This class renders Wicked blocks.
 *
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Wickedblock extends Text_Wiki_Render
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
            return $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_BlockCollection')
                ->create()
                ->getBlock($options['app'],
                           $options['app'] . '_Block_' . $options['block'],
                           $options['args'])
                ->getContent();
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }
    }
}
