<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Skeleton
 */

/**
 * Skeleton AJAX application API.
 *
 * This file defines the AJAX actions provided by this module. The primary
 * AJAX endpoint is represented by horde/services/ajax.php but that handler
 * will call the module specific actions defined in this class.
 *
 * @author    Your Name <you@example.com>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Skeleton
 */
class Skeleton_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Application specific initialization tasks should be done in here.
     */
    protected function _init()
    {
        // This adds the 'noop' action to the current application.
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Noop');
    }

}
