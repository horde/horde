<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_UrlShortener extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['urlshortener'])
            ? 'TinyUrl'
            : $GLOBALS['conf']['urlshortener'];

        $class = $this->_getDriverName($driver, 'Horde_Service_UrlShortener');
        return new $class($injector->getInstance('Horde_Http_Client'));
    }

}
