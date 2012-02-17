<?php
/**
 * Abstract class to allow for modularization of specific login tasks.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  LoginTasks
 */
abstract class Horde_LoginTasks_Task
{
    /**
     * Should the task be run?
     *
     * @var boolean
     */
    public $active = true;

    /**
     * The style of the page output.
     *
     * [1] Horde_LoginTasks::DISPLAY_CONFIRM_NO
     *     Horde_LoginTasks::DISPLAY_CONFIRM_YES
     *     Each output from describe() will have a checkbox associated
     *     with it. For each checkbox selected, execute() for that task will
     *     be run. More than 1 confirmation message can be displayed on the
     *     confirmation page at once.
     *
     *     DISPLAY_CONFIRM_YES will be checked by default, DISPLAY_CONFIRM_NO
     *     will be unchecked by default.
     *
     * [2] Horde_LoginTasks::DISPLAY_AGREE
     *     The output from describe() should be text asking the user to
     *     agree/disagree to specified terms. If 'yes' is selected, the POST
     *     variable 'agree' will be set. If 'no' is selected, the POST variable
     *     'not_agree' will be set. In either case, execute() will ALWAYS be
     *     run.
     *     This style will be displayed on its own confirmation page.
     *
     * [3] Horde_LoginTasks::DISPLAY_NOTICE
     *     The output from describe() should be any non-interactive text
     *     desired. There will be a single 'Click to Continue' button below
     *     this text. execute() will ALWAYS be run.
     *     This style will be displayed on its own confirmation page.
     *
     * [4] Horde_LoginTasks::DISPLAY_NONE
     *     Don't display any confirmation to the user.
     *
     * @var integer
     */
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;

    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::MONTHLY;

    /**
     * The priority of the task.
     *
     * @var integer
     */
    public $priority = Horde_LoginTasks::PRIORITY_NORMAL;

    /**
     * Do login task (if it has been confirmed).
     */
    abstract public function execute();

    /**
     * Return description information for the login task.
     *
     * @return string  Description that will be displayed on the login task
     *                 confirmation page.
     */
    public function describe()
    {
        return '';
    }

    /**
     * Does the task need to be displayed?
     *
     * @return boolean  True if the task should be displayed.
     */
    public function needsDisplay()
    {
        return $this->display != Horde_LoginTasks::DISPLAY_NONE;
    }

    /**
     * Indicates if the display of the current task should be joined with the
     * given previous task.
     *
     * @param Horde_Login_Task $previous  The previous task to display.
     *
     * @return boolean  True if both tasks should be displayed together.
     */
    public function joinDisplayWith(Horde_LoginTasks_Task $previous)
    {
        return (($this->display == $previous->display) ||
                ($this->_isConfirmTask($this) &&
                 $this->_isConfirmTask($previous)));
    }

    /**
     * Is this a confirmation task?
     *
     * @param Horde_Login_Task $task  The task to analyze.
     *
     * @return boolean  True if this is a confirmation task.
     */
    private function _isConfirmTask($task)
    {
        return in_array(
            $task->display,
            array(Horde_LoginTasks::DISPLAY_CONFIRM_YES,
                  Horde_LoginTasks::DISPLAY_CONFIRM_NO)
        );
    }

}
