<?php
/**
 * @category Horde
 * @package Horde_View
 */

/**
 * Concrete class for handling views.
 *
 * @category Horde
 * @package Horde_View
 */
class Horde_View extends Horde_View_Base
{
    /**
     * Includes the template in a scope with only public variables.
     *
     * @param string The template to execute. Not declared in the
     * function signature so it stays out of the view's public scope.
     *
     * @param array  Any local variables to declare.
     */
    protected function _run()
    {
        // set local variables
        if (is_array(func_get_arg(1))) {
            foreach (func_get_arg(1) as $key => $value) {
                ${$key} = $value;
            }
        }

        include func_get_arg(0);
    }

}
