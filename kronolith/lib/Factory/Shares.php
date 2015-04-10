<?php
/**
 * Horde_Injector based factory for the Kronolith share driver.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Kronolith
 */
class Kronolith_Factory_Shares extends Horde_Core_Factory_Injector
{
    /**
     * Return the driver instance.
     *
     * @return Kronolith_Shares
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return new Kronolith_Shares($injector->getInstance('Horde_Core_Factory_Share')->create());
    }

}
