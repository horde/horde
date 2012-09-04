<?php
/**
 * Defines the AJAX interface for Ansel.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Ansel_Ajax_Application_Handler');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');
    }

}
