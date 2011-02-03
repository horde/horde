<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Browser extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        return new Horde_Core_Browser();
    }

}
