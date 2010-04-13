<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Vfs implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $vfs = VFS::singleton($GLOBALS['conf']['vfs']['type'], Horde::getDriverConfig('vfs', $GLOBALS['conf']['vfs']['type']));
        $vfs->setLogger($injector->getInstance('Horde_Log_Logger'));

        return $vfs;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
