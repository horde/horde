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
            $write_db = Horde_Core_Binder_Common::createDb($params, 'cache SQL');

            /* Check if we need to set up the read DB connection
             *              * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = Horde_Core_Binder_Common::createDb(array_merge($params, $params['read']), 'cache SQL');
            }

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
