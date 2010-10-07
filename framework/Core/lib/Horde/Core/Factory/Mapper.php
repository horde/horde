<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Mapper
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Routes_Mapper();
    }

}
