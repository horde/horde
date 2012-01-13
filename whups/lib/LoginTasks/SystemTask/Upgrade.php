<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSDL
 * @package  Whups
 */
class Whups_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'whups';

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
            $this->_upgradeAbookPrefs();
            $this->_upgradeLayout();
            break;
        }
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
