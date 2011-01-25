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
            throw new Horde_Exception(Horde_Core_Translation::t("The History system is disabled."));
        }

        return $injector->getInstance('Horde_History_Sql');
    }
}
