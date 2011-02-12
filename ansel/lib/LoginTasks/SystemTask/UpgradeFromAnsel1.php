<?php
/**
 * Login system task for automated upgrade tasks from Ansel 1.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_LoginTasks_SystemTask_UpgradeFromAnsel1 extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::ONCE;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        $this->_upgradeLayout();
    }

    /**
     * Upgrade myansel_layout preference.
     */
    protected function _upgradeLayout()
    {
        $bu = new Horde_Core_Block_Upgrade();
        $bu->upgrade('myansel_layout');
    }

}
