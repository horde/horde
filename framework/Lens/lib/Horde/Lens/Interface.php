<?php
/**
 * This set of classes implements a Flyweight pattern
 * (http://en.wikipedia.org/wiki/Flyweight_pattern). Refactor/rename
 * some based on this fact?
 *
 * @package Horde_Lens
 */

/**
 * @package Horde_Lens
 */
interface Horde_Lens_Interface {

    /**
     * Set the current object to view with the Lens.
     */
    public function decorate($target);

}
