<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_History
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['sql']['phptype']) ||
            ($GLOBALS['conf']['sql']['phptype'] == 'none')) {
            $dict = new Horde_Translation_Gettext('Horde_Core', dirname(__FILE__) . '/../../../../locale');
            throw new Horde_Exception($dict->t("The History system is disabled."));
        }

        $ob = Horde_History::factory('Sql', $GLOBALS['conf']['sql']);
        $ob->setLogger($injector->getInstance('Horde_Log_Logger'));

        return $ob;
    }

}
