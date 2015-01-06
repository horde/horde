<?php
/**
 * Defines the AJAX interface for Horde.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Horde_Ajax_Application_Handler');
        // Needed because Core contains Imples
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');

        if (!empty($GLOBALS['conf']['twitter']['enabled'])) {
            $this->addHandler('Horde_Ajax_Application_TwitterHandler');
        }

        if (!empty($GLOBALS['conf']['facebook']['enabled'])) {
            $this->addHandler('Horde_Ajax_Application_FacebookHandler');
        }
    }

}
