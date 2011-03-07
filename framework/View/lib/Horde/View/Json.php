<?php
/**
 * @category Horde
 * @package View
 */

/**
 * Concrete class for handling views.
 *
 * @category Horde
 * @package View
 */
class Horde_View_Json extends Horde_View_Base
{
    /**
     * Processes a template and returns the output.
     *
     * @param string $name The template to process.
     *
     * @return string The template output.
     */
    public function render($name = '', $locals = array())
    {
        return json_encode((object)(array)$this);
    }

    /**
     * Satisfy the abstract _run function in Horde_View_Base.
     */
    protected function _run()
    {
    }
}
