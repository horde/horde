<?php
class Nag_Factory_TagBrowser extends Horde_Core_Factory_Base
{
    protected $_instance;

    public function create()
    {
        if (empty($instance)) {
            $instance = new Nag_TagBrowser(
                $GLOBALS['injector']->getInstance('Nag_Tagger'));
        }

        return $instance;
    }

}