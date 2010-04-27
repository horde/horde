<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Cache implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return $this->_getCacheInstance($GLOBALS['conf']['cache']['driver'], $injector);
    }

    protected function _getCacheInstance($driver, $injector)
    {
        $params = Horde::getDriverConfig('cache', $driver);

        switch (strtolower($driver)) {
        case 'memcache':
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
            break;

        case 'sql':
            if (!empty($params['use_memorycache'])) {
                $params['use_memorycache'] = $this->_getCacheInstance($params['use_memorycache'], $injector);
            }
            break;
        }

        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        if (empty($driver) || $driver == 'none') {
            $driver = 'Null';
        }

        $class = 'Horde_Cache_' . ucfirst(basename($driver));
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
