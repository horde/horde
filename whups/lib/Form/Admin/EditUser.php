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

class Whups_Form_Admin_EditUser extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        $this->appendButtons(_("Remove User"));

        parent::__construct($vars, _("Responsible Users"));

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
