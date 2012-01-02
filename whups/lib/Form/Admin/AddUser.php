<?php
/**
 * This file contains all Horde_Form classes for administrating responsible
 * users.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_AddUser extends Horde_Form
{

    public function __construct(&$vars)
    {
        parent::Horde_Form($vars, _("Add Users"));

        $this->addHidden('', 'queue', 'int', true, true);

        global $conf, $whups_driver;

        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        if ($auth->hasCapability('list')) {
            $queue = $vars->get('queue');
            $current = $whups_driver->getQueueUsers($queue);

            try {
                $list = $auth->listUsers();
                sort($list);
                $users = array();
                foreach ($list as $user) {
                    if (!isset($current[$user])) {
                        $users[$user] = $GLOBALS['registry']->convertUsername($user, false);
                    }
                }
                $this->addVariable(_("User"), 'user', 'multienum', true, false, null, array($users));
            } catch (Horde_Auth_Exception $e) {
                $this->addVariable(
                    _("User"), 'user', 'invalid', true, false, null,
                    array(sprintf(_("There was an error listing users: %s; %s"), $list->getMessage(), $list->getUserInfo())));
            }
        } else {
            $this->addVariable(_("User"), 'user', 'text', true);
        }
    }

}