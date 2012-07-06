<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Variables extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        return Horde_Variables::getDefaultVariables();
    }

}
