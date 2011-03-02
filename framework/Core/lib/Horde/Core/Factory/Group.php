<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Group extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $group = empty($GLOBALS['conf']['group']['cache'])
            ? null
            : $GLOBALS['session']->retrieve('horde_group');

        if (!$group) {
            $driver = $GLOBALS['conf']['group']['driver'];
            $params = Horde::getDriverConfig('group', $driver);
            switch ($driver) {
            case 'ldap':
                $params['ldap'] = $injector
                    ->getInstance('Horde_Core_Factory_Ldap')
                    ->create('horde', 'group');
                break;
            case 'sql':
                $params['db'] = $injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('horde', 'group');
                break;
            }
            $group = Horde_Group::factory($driver, $params);
        }

        if (!empty($GLOBALS['conf']['group']['cache'])) {
            register_shutdown_function(array($group, 'shutdown'));
        }

        return $group;
    }

}
