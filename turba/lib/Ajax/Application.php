<?php
/**
 * Defines the AJAX interface for Turba.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        global $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_SMARTMOBILE:
            $this->addHandler('Turba_Ajax_Application_Smartmobile');
            break;
        }
    }

}
