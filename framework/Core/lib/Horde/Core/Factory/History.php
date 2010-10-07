<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['sql']['phptype']) ||
            ($GLOBALS['conf']['sql']['phptype'] == 'none')) {
            throw new Horde_Exception(_("The History system is disabled."));
        }

        $ob = Horde_History::factory('Sql', $GLOBALS['conf']['sql']);
        $ob->setLogger($injector->getInstance('Horde_Log_Logger'));

        return $ob;
    }

}
