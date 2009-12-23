<?php
/**
 * $Horde: framework/Lens/lib/Horde/Lens/Interface.php,v 1.1 2008/03/05 20:37:26 chuck Exp $
 *
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
