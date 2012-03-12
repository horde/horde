<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Hermes
 */
class Hermes_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'hermes';

    /**
     */
    protected $_versions = array(
        '2.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '2.0':
            $this->_upgradeTimersStorage();
            $this->_upgradePausedTimers();
        }
    }

    protected function _upgradeTimersStorage()
    {
        /* Upgrade to the new preferences storage format. */
        $upgrade_prefs = array(
            'running_timers'
        );

        $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
    }

    /**
     * Add new fields to support pausing timers to any timers that are currently
     * running.
     */
    protected function _upgradePausedTimers()
    {
        $timers = @unserialize($GLOBALS['prefs']->getValue('running_timers'));
        if (!is_array($timers)) {
            $timers = array();
        };

        foreach ($timers as &$timer) {
            $timer['paused'] = false;
            $timer['elapsed'] = 0;
        }

        $GLOBALS['prefs']->setValue('running_timers', serialize($timers));
    }

}
