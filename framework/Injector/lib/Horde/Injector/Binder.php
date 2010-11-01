<?php
/**
 * Binder interface definition.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Injector
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */

/**
 * Describes a binding class that is able to create concrete object instances.
 *
 * @category Horde
 * @package  Injector
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */
interface Horde_Injector_Binder
{
    /**
     * Create an instance.
     *
     * @param Horde_Injector $injector  The injector should provide all
     *                                  required dependencies for creating the
     *                                  instance.
     *
     * @return mixed The concrete instance.
     */
    public function create(Horde_Injector $injector);

    /**
     * Determine if one binder equals another binder
     *
     * @param Horde_Injector_Binder $binder  The binder to compare against
     *                                       $this.
     *
     * @return boolean  True if equal, false if not equal.
     */
    public function equals(Horde_Injector_Binder $binder);

}
