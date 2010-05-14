<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Perms implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $params = isset($GLOBALS['conf']['perms'])
            ? Horde::getDriverConfig('perms', $GLOBALS['conf']['perms']['driver'])
            : array();

        $driver = empty($GLOBALS['conf']['perms']['driver'])
            ? (empty($GLOBALS['conf']['datatree']['driver']) ? null : 'Datatree')
            : $GLOBALS['conf']['perms']['driver'];

        if (strcasecmp($driver, 'Datatree') === 0) {
            $dt_driver = $GLOBALS['conf']['datatree']['driver'];
            $params['datatree'] = DataTree::singleton(
                $dt_driver,
                array_merge(Horde::getDriverConfig('datatree', $dt_driver), array('group' => 'horde.perms'))
            );
        } elseif (strcasecmp($driver, 'Sql') === 0) {
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

        $params = array_merge($params, array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'logger' => $injector->getInstance('Horde_Log_Logger')
        ));

        return Horde_Perms::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
