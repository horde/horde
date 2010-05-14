<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_SessionHandler implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $driver = empty($conf['sessionhandler']['type'])
            ? 'None'
            : $conf['sessionhandler']['type'];

        $params = Horde::getDriverConfig('sessionhandler', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $write_db = $injector->getInstance('Horde_Db_Pear')->getOb();

            /* Check if we need to set up the read DB connection
             * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = $injector->getInstance('Horde_Db_Pear')->getOb('read');
            }
        }

        if (!empty($conf['sessionhandler']['memcache'])) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        }

        return Horde_SessionHandler::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
