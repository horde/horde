<?php
/**
 * Defines the AJAX interface for Kronolith.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gonçalo Queirós <mail@goncaloqueiros.net>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Kronolith_Ajax_Application_Handler');

        $this->addHandler('Horde_Core_Ajax_Application_Handler_Chunk');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Groups');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');

        $email = $this->addHandler('Horde_Core_Ajax_Application_Handler_Email');
        $email->defaultDomain = empty($GLOBALS['conf']['storage']['default_domain'])
            ? null
            : $GLOBALS['conf']['storage']['default_domain'];
    }

}
