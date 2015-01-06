<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl BSD
 * @package  Whups
 */

/**
 * Whups AJAX application API.
 *
 * This file defines the AJAX actions provided by this module. The primary
 * AJAX endpoint is represented by horde/services/ajax.php but that handler
 * will call the module specific actions defined in this class.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsdl BSD
 * @package   Whups
 */
class Whups_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');
    }
}
