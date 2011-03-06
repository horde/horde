<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Group extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['group']['driver']);
        $params = Horde::getDriverConfig('group', $driver);
        switch ($driver) {
        case 'Ldap':
        case 'Kolab':
            $class = 'Horde_Core_Group_' . $driver;
            $params['ldap'] = $injector
                ->getInstance('Horde_Core_Factory_Ldap')
                ->create('horde', 'group');
            break;
        case 'Sql':
            $class = 'Horde_Group_Sql';
            $params['db'] = $injector
                ->getInstance('Horde_Core_Factory_Db')
                ->create('horde', 'group');
            break;
        default:
            $class = 'Horde_Group_' . $driver;
            break;
        }
        return new $class($params);
    }

}
