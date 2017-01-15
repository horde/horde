<?php
class Mnemo_Factory_TagBrowser extends Horde_Core_Factory_Base
{
    protected $_instance;

    public function create()
    {
        if (empty($this->_instance)) {
            $this->_instance = new Mnemo_TagBrowser(
                $GLOBALS['injector']->getInstance('Mnemo_Tagger'));
        }

        return $this->_instance;
    }

}