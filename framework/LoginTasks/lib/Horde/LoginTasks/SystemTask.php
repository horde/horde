<?php
/**
 * Abstract class to allow for modularization of specific system login tasks
 * that are always run on login.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  LoginTasks
 */
abstract class Horde_LoginTasks_SystemTask
{
    /**
     * Should the task be run?
     *
     * @var boolean
     */
    public $active = true;

    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::EVERY;

    /**
     * Do login task (if it has been confirmed).
     */
    abstract public function execute();

    /**
     * Skip the current task?  If true, will not run on this access but
     * will attempt to run on the next access.
     *
     * @return boolean  Skip the current task?
     */
    public function skip()
    {
        return false;
    }

}
