<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Matcher extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Routes_Matcher(
            $injector->getInstance('Horde_Routes_Mapper'),
            $injector->getInstance('Horde_Controller_Request')
        );
    }
}
