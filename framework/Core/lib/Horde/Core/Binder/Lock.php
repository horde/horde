<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Lock implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['lock']['driver'])) {
            $driver = 'Null';
        } else {
            $driver = $GLOBALS['conf']['lock']['driver'];
            if (strcasecmp($driver, 'None') === 0) {
                $driver = 'Null';
            }
        }

        $params = Horde::getDriverConfig('lock', $driver);
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

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

        return Horde_Lock::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
