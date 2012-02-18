<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Weather extends Horde_Core_Factory_Injector
{
    /**
     *
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
            'http_client' => $injector->createInstance('Horde_Core_Factory_HttpClient')->create(),
            'cache' => $injector->getInstance('Horde_Cache'),
            'cache_lifetime' => $conf['weather']['params']['lifetime']
        );

        if (!empty($conf['weather']['provider'])) {
            $driver = $conf['weather']['provider'];
        } else {
            throw new Horde_Exception('No Weather configuration found.');
        }

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

        try {
            $class = 'Horde_Service_Weather_' . $driver;
            $driver = new $class($params);
        } catch (InvalidArgumentException $e) {
            throw new Horde_Exception($e);
        }

        return $driver;
    }

}
