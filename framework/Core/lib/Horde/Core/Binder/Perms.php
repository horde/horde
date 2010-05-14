<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Perms implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['perms']['driver'])
            ? (empty($GLOBALS['conf']['datatree']['driver']) ? null : 'Datatree')
            : $GLOBALS['conf']['perms']['driver'];
        $params = isset($GLOBALS['conf']['perms'])
            ? Horde::getDriverConfig('perms', $driver)
            : array();

        if (strcasecmp($driver, 'Datatree') === 0) {
            $dt_driver = $GLOBALS['conf']['datatree']['driver'];
            $params['datatree'] = DataTree::singleton(
                $dt_driver,
                array_merge(Horde::getDriverConfig('datatree', $dt_driver), array('group' => 'horde.perms'))
            );
        } elseif (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter_Base');
        }

        $params['cache'] = $injector->getInstance('Horde_Cache');
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Perms::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
