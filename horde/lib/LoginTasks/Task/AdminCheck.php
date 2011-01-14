<?php
/**
 * Login task to check various Horde configuration/setup values, and then
 * report failures to an admin via the notification system.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_LoginTasks_Task_AdminCheck extends Horde_LoginTasks_Task
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::EVERY;

    /**
     * Display type.
     *
     * @var integer
     */
    public $display = Horde_LoginTasks::DISPLAY_NONE;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['registry']->isAdmin();
    }

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        /* Check if test script is active. */
        if (empty($GLOBALS['conf']['testdisable'])) {
            $GLOBALS['notification']->push(_("The test script is currently enabled. For security reasons, disable test scripts when you are done testing (see horde/docs/INSTALL)."), 'horde.warning');
        }

        /* Check that logger configuration is correct. */

        // Ensure Logger object was initialized.
        $GLOBALS['injector']->getInstance('Horde_Log_Logger');

        if ($error = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Logger')->error) {
            $GLOBALS['notification']->push($error, 'horde.warning');
        }
    }

}
