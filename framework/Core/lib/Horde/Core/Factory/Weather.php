<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Weather extends Horde_Core_Factory_Injector
{
    /**
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $conf, $injector;

        if (empty($conf['weather']['provider'])) {
            throw new Horde_Exception(_("Weather support not configured."));
        }

        // Parameters for all driver types
        $params = array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'cache_lifetime' => $conf['weather']['params']['lifetime'],
            'http_client' => $injector->createInstance('Horde_Core_Factory_HttpClient')->create()
        );

        $driver = $conf['weather']['provider'];

        switch ($driver) {
        case 'WeatherUnderground':
        case 'Wwo':
            $params['apikey'] = $conf['weather']['params']['key'];
            break;

        case 'Google':
            $l = explode('_', $GLOBALS['language']);
            $params['language'] = $l[0];
            break;
        }

        $class = $this->_getDriverName($driver, 'Horde_Service_Weather');

        try {
            return new $class($params);
        } catch (InvalidArgumentException $e) {
            throw new Horde_Exception($e);
        }
    }

}
