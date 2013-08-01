<?php
/**
 * Horde_Injector based factory for the Gollem share driver.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_Factory_Shares extends Horde_Core_Factory_Injector
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
