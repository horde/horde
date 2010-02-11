<?php
class Horde_Core_Binder_Perms implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $perm_driver = $perm_params = null;

        if (empty($GLOBALS['conf']['perms']['driver'])) {
            $perm_driver = empty($GLOBALS['conf']['datatree']['driver'])
                ? null
                : 'datatree';
        } else {
            $perm_driver = $GLOBALS['conf']['perms']['driver'];
            $perm_params = Horde::getDriverConfig('perms', $perm_driver);
        }

        return Horde_Perms::factory($perm_driver, $perm_params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
