<?php
/**
 * Login task to check if the test script is active.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_LoginTasks_Task_TestScriptActive extends Horde_LoginTasks_Task
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
     * Perform all functions for this task.
     */
    public function execute()
    {
        if ($GLOBALS['registry']->isAdmin() &&
            empty($GLOBALS['conf']['testdisable'])) {
            $GLOBALS['notification']->push(_("The test script is currently enabled. For security reasons, disable test scripts when you are done testing (see horde/docs/INSTALL)."), 'horde.warning');
        }
    }

}
