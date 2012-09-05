<?php
/**
 * Defines the AJAX interface for Nag.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        global $registry;

        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');

        switch ($registry->getView()) {
        case $registry::VIEW_SMARTMOBILE:
            $this->addHandler('Nag_Ajax_Application_Handler_Smartmobile');
            break;
        }
    }

}
