<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Weather extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $conf, $injector;

        // Parameters for all driver types
        $params = array(
            'http_client' => $injector->getInstance('Horde_Core_Factory_HttpClient')->create(),
            'cache' => $injector->getInstance('Horde_Cache')
        );

        if (!empty($conf['weather']['provider'])) {
            $driver = $conf['weather']['provider'];
        } else {
            throw new Horde_Exception('No Weather configuration found.');
        }

        switch ($driver) {
        case 'WeatherUnderground':
            $params['apikey'] = $conf['weather']['params']['key'];
            break;

        case 'Google':
            $l = explode('_', $GLOBALS['language']);
            $params['language'] = $l[0];
            break;
        }

        try {
            $class = 'Horde_Service_Weather_' . $driver;
            $driver = new $class($params);
        } catch (InvalidArgumentException $e) {
            throw new TimeObjects_Exception($e);
        }

        return $driver;
    }

}
