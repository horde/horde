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
        case 'Contactlists':
            $class = 'Horde_Group_Contactlists';
            $params['api'] = $GLOBALS['registry']->contacts;
            break;

        case 'Kolab':
        case 'Ldap':
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
            $class = $this->_getDriverName($driver, 'Horde_Group');
            break;
        }

        return new $class($params);
    }

}
