<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Template
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Template(array(
            'cacheob' => $injector->getInstance('Horde_Cache'),
            'logger' => $injector->getInstance('Horde_Log_Logger')
        ));
    }

}
