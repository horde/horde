<?php
class Content_ApplicationController extends Horde_Controller_Base
{
    protected function _initializeApplication()
    {
        $CONTENT_DIR = dirname(__FILE__) . '/../';

        $GLOBALS['conf']['sql']['adapter'] = $GLOBALS['conf']['sql']['phptype'] == 'mysqli' ? 'mysqli' : 'pdo_' . $GLOBALS['conf']['sql']['phptype'];
        Horde_Rdo::setAdapter(Horde_Rdo_Adapter::factory('pdo', $GLOBALS['conf']['sql']));
        Horde_Db::setAdapter(Horde_Db_Adapter::factory($GLOBALS['conf']['sql']));
    }

}
