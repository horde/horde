<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Group
{
    public function create(Horde_Injector $injector)
    {
        $group = empty($GLOBALS['conf']['group']['cache'])
            ? null
            : $GLOBALS['session']->retrieve('horde_group');

        if (!$group) {
            $driver = $GLOBALS['conf']['group']['driver'];
            $params = Horde::getDriverConfig('group', $driver);
            if ($driver == 'ldap') {
                $params['ldap'] = $injector->getInstance('Horde_Core_Factory_Ldap')->getLdap('horde', 'group');
            }
            $group = Horde_Group::factory($driver, $params);
        }

        if (!empty($GLOBALS['conf']['group']['cache'])) {
            register_shutdown_function(array($group, 'shutdown'));
        }

        return $group;
    }

}
