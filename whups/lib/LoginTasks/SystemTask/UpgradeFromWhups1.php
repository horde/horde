<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Whups
 */
class Whups_LoginTasks_SystemTask_UpgradeFromWhups1 extends Horde_LoginTasks_SystemTask
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
        $this->_upgradeAbookPrefs();
        $this->_upgradeLayout();
    }

    /**
     * Upgrade to the new addressbook preferences.
     */
    protected function _upgradeAbookPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('search_sources')) {
            $src = $prefs->getValue('search_sources');
            if (!is_array(json_decode($src))) {
                $prefs->setValue('search_sources', json_encode(explode("\t", $src)));
            }
        }

        if (!$prefs->isDefault('search_fields')) {
            $val = $prefs->getValue('search_fields');
            if (!is_array(json_decode($val, true))) {
                $fields = array();
                foreach (explode("\n", $val) as $field) {
                    $field = trim($field);
                    if (!empty($field)) {
                        $tmp = explode("\t", $field);
                        if (count($tmp) > 1) {
                            $source = array_splice($tmp, 0, 1);
                            $fields[$source[0]] = $tmp;
                        }
                    }
                }
                $prefs->setValue('search_fields', $fields);
            }
        }
    }

    /**
     * Upgrade mybugs_layout preference.
     */
    protected function _upgradeLayout()
    {
        $bu = new Horde_Core_Block_Upgrade();
        $bu->upgrade('mybugs_layout');
    }

}
