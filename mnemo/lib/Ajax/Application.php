<?php
/**
 * Defines the AJAX interface for Mnemo.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Mnemo
 */
class Mnemo_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');
    }

}
