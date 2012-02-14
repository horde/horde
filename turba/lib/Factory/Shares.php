<?php
/**
 * Horde_Injector based factory for the Turba share driver.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Turba
 */
class Turba_Factory_Shares extends Horde_Core_Factory_Injector
{
    /**
     * Return the driver instance.
     *
     * @return Horde_Core_Share_Driver
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Factory_Share')->create();
    }

}
