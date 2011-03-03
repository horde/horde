<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Group extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['group']['driver'];
        $params = Horde::getDriverConfig('group', $driver);
        switch ($driver) {
        case 'ldap':
            $class = 'Horde_Core_Group_Ldap';
            $params['ldap'] = $injector
                ->getInstance('Horde_Core_Factory_Ldap')
                ->create('horde', 'group');
            break;
        case 'sql':
            $class = 'Horde_Group_Sql';
            $params['db'] = $injector
                ->getInstance('Horde_Core_Factory_Db')
                ->create('horde', 'group');
            break;
        }
        return new $class($params);
    }

}
