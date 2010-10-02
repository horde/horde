<?php
/**
 * This file contains all Horde_Form classes for administrating responsible
 * users.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddUserForm extends Horde_Form {

    function AddUserForm(&$vars)
    {
        parent::Horde_Form($vars, _("Add Users"));

        $this->addHidden('', 'queue', 'int', true, true);

        global $conf, $whups_driver;

        $auth = $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth();
        if ($auth->hasCapability('list')) {
            $queue = $vars->get('queue');
            $current = $whups_driver->getQueueUsers($queue);

            $list = $auth->listUsers();
            if (is_a($list, 'PEAR_Error')) {
                $this->addVariable(_("User"), 'user', 'invalid', true, false, null,
                                   array(sprintf(_("There was an error listing users: %s; %s"), $list->getMessage(), $list->getUserInfo())));
            } else {
                sort($list);
                $users = array();
                foreach ($list as $user) {
                    if (!isset($current[$user])) {
                        $users[$user] = Horde_Auth::removeHook($user);
                    }
                }
                $this->addVariable(_("User"), 'user', 'multienum', true, false, null, array($users));
            }
        } else {
            $this->addVariable(_("User"), 'user', 'text', true);
        }
    }

}

class EditUserStep1Form extends Horde_Form {

    function EditUserStep1Form(&$vars)
    {
        global $whups_driver;

        $this->appendButtons(_("Remove User"));

        parent::Horde_Form($vars, _("Responsible Users"));

        $queue = $vars->get('queue');
        $users = $whups_driver->getQueueUsers($queue);
        $f_users = array();
        foreach ($users as $user) {
            $f_users[$user] = Whups::formatUser($user);
        }
        if ($f_users) {
            asort($f_users);
            $usertype = 'enum';
            $type_params = array($f_users);
        } else {
            $usertype = 'invalid';
            $type_params = array(_("There are no users responsible for this queue."));
        }

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addVariable(_("Users responsible for this queue"), 'user', $usertype, true, false, null, $type_params);
    }

}
