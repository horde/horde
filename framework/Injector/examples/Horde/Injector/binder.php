<?php
/**
 * Demonstrates how we register binders so that the instances get created only
 * when actually accessing the instance.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Injector
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */

require 'Horde/Autoloader.php';

/**
 * A dummy binder.
 *
 * @category Horde
 * @package  Injector
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */
class Binder implements Horde_Injector_Binder
{
    /**
     * Create an instance.
     *
     * @param Horde_Injector $injector The injector should provide all required
     *                                 dependencies for creating the instance.
     *
     * @return mixed The concrete instance.
     */
    public function create(Horde_Injector $injector)
    {
        return 'constructed';
    }

    /**
     * Determine if one binder equals another binder
     *
     * @param Horde_Injector_Binder $binder The binder to compare against $this
     *
     * @return bool true if they are equal, or false if they are not equal
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}

$a = new Horde_Injector(new Horde_Injector_TopLevel());
$a->addBinder('constructed', new Binder());
var_dump($a->getInstance('constructed'));