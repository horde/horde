<?php
class Horde_Core_Binder_Perms implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $perm_params = array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'logger' => $injector->getInstance('Horde_Logger')
        );

        $perm_driver = empty($GLOBALS['conf']['perms']['driver'])
            ? (empty($GLOBALS['conf']['datatree']['driver']) ? null : 'datatree')
            : $GLOBALS['conf']['perms']['driver'];

        switch (strtolower($perm_driver)) {
        case 'datatree':
            $driver = $GLOBALS['conf']['datatree']['driver'];
            $perm_params['datatree'] = DataTree::singleton(
                $driver,
                array_merge(Horde::getDriverConfig('datatree', $driver), array('group' => 'horde.perms'))
            );
            break;

        case 'sql':
            // TODO
            break;
        }

        return Horde_Perms::factory($perm_driver, $perm_params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
