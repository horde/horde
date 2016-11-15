<?php
/**
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Login system task for automated upgrade tasks.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_LoginTasks_SystemTask_Upgrade
extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'ingo';

    /**
     */
    protected $_versions = array(
        '4.0',
        '2.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.0':
            $this->_upgradeIngoStorage();
            break;

        case '2.0':
            /* Upgrade to the new preferences storage format. */
            $upgrade_prefs = array(
                'rules',
                'vacation',
            );

            $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
            break;
        }
    }

    /**
     */
    protected function _upgradeIngoStorage()
    {
        global $prefs;

        if (!($old_rules = @unserialize($prefs->getValue('rules')))) {
            $old_rules = array();
        }

        $rules = array();
        $upgrade_rule = function($classname, $data) {
            $ob = new $classname();
            $ob->combine = $data['combine'];
            $ob->conditions = $data['conditions'];
            $ob->flags |= $data['flags'];
            $ob->name = $data['name'];
            $ob->stop = $data['stop'];
            $ob->value = $data['action-value'];
            return $ob;
        };

        foreach ($old_rules as $val) {
            if (($val instanceof Ingo_Storage_Rule) || !is_array($val)) {
                continue;
            }

            $disable = !empty($val['disable']);

            switch ($val['action']) {
            case 7: // ACTION_BLACKLIST
                $ob = new Ingo_Rule_System_Blacklist();
                if ($data = @unserialize($prefs->getValue('blacklist'))) {
                    $ob->addresses = $data['a'];
                    $ob->mailbox = $data['f'];
                }
                break;

            case 3: // ACTION_DISCARD
                $ob = $upgrade_rule('Ingo_Rule_User_Discard', $val);
                break;

            case 12: // ACTION_FLAGONLY
                $ob = $upgrade_rule('Ingo_Rule_User_FlagOnly', $val);
                break;

            case 10: // ACTION_FORWARD
                $ob = new Ingo_Rule_System_Forward();
                if ($data = @unserialize($prefs->getValue('forward'))) {
                    $ob->addresses = $data['a'];
                    $ob->keep = !empty($data['k']);
                }
                break;

            case 1: // ACTION_KEEP
                $ob = $upgrade_rule('Ingo_Rule_User_Keep', $val);
                break;

            case 2: // ACTION_MOVE
                $ob = $upgrade_rule('Ingo_Rule_User_Move', $val);
                break;

            case 11: // ACTION_MOVEKEEP
                $ob = $upgrade_rule('Ingo_Rule_User_MoveKeep', $val);
                break;

            case 13: // ACTION_NOTIFY
                $ob = $upgrade_rule('Ingo_Rule_User_Notify', $val);
                break;

            case 4: // ACTION_REDIRECT
                $ob = $upgrade_rule('Ingo_Rule_User_Redirect', $val);
                break;

            case 5: // ACTION_REDIRECT_KEEP
                $ob = $upgrade_rule('Ingo_Rule_User_RedirectKeep', $val);
                break;

            case 6: // ACTION_REJECT
                $ob = $upgrade_rule('Ingo_Rule_User_Reject', $val);
                break;

            case 14: // ACTION_SPAM
                $ob = new Ingo_Rule_System_Spam();
                if ($data = @unserialize($prefs->getValue('spam'))) {
                    $ob->level = $data['level'];
                    $ob->mailbox = $data['folder'];
                } else {
                    $disable = true;
                }
                break;

            case 8: // ACTION_VACATION
                $ob = new Ingo_Rule_System_Vacation();
                if ($data = @unserialize($prefs->getValue('vacation'))) {
                    $ob->addresses = $data['addresses'];
                    $ob->days = $data['days'];
                    $ob->exclude = $data['excludes'];
                    $ob->ignore_list = $data['ignorelist'];
                    $ob->reason = $data['reason'];
                    $ob->subject = $data['subject'];
                    if (isset($data['start'])) {
                        $ob->start = $data['start'];
                    }
                    if (isset($data['end'])) {
                        $ob->end = $data['end'];
                    }
                } else {
                    $disable = true;
                }
                break;

            case 9: // ACTION_WHITELIST
                $ob = new Ingo_Rule_System_Whitelist();
                if ($data = @unserialize($prefs->getValue('whitelist'))) {
                    $ob->addresses = $data;
                }
                break;
            }

            $ob->disable = $disable;

            $rules[] = $ob;
        }

        new Ingo_Upgrade_Storage_Prefs_v4(array('rules' => $rules));

        $prefs->remove('blacklist');
        $prefs->remove('forward');
        $prefs->remove('spam');
        $prefs->remove('vacation');
        $prefs->remove('whitelist');
    }

}

class Ingo_Upgrade_Storage_Prefs_v4 extends Ingo_Storage_Prefs
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_rules = $params['rules'];
        foreach ($this->_rules as $val) {
            $this->_storeBackend(self::STORE_ADD, $val);
        }
    }

    protected function _load()
    {
    }
}
