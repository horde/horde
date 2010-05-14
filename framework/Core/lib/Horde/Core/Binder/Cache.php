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
        if (empty($driver) || (strcasecmp($driver, 'None') === 0)) {
            $driver = 'Null';
        }

        $params = Horde::getDriverConfig('cache', $driver);

        if (strcasecmp($driver, 'Memcache') === 0) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        } elseif (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter_Base');

            if (!empty($params['use_memorycache'])) {
                $params['use_memorycache'] = $this->_getCacheInstance($params['use_memorycache'], $injector);
            }
        }

        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Cache::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
