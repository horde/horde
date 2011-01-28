<?php
/**
 * Abstract class to allow for modularization of specific system login tasks
 * that are always run on login.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
