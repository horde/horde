<?php
/**
 * This file contains all Horde_Form classes to create a new ticket.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Whups
 */

/**
 * @package Whups
 */
class Whups_Form_Ticket_CreateStepFour extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver, $conf;

        parent::__construct($vars, _("Create Ticket - Step 4"));

        /* Preserve previously uploaded attachments. */
        $this->addHidden('', 'deferred_attachment', 'text', false);

        /* Groups. */
        $mygroups = $GLOBALS['injector']
            ->getInstance('Horde_Group')
            ->listAll($conf['prefs']['assign_all_groups']
                      ? null
                      : $GLOBALS['registry']->getAuth());
        asort($mygroups);

        $users = $whups_driver->getQueueUsers($vars->get('queue'));
        $f_users = array();
        foreach ($users as $user) {
            $f_users['user:' . $user] = Whups::formatUser($user);
        }

        $f_groups = array();
        if (count($mygroups)) {
            foreach ($mygroups as $id => $group) {
                $f_groups['group:' . $id] = $group;
            }
        }

        if (count($f_users)) {
            asort($f_users);
            $owners = &$this->addVariable(_("Owners"), 'owners', 'multienum',
                                          false, false, null, array($f_users));
        }

        if (count($f_groups)) {
            asort($f_groups);
            $group_owners = &$this->addVariable(_("Group Owners"),
                                                'group_owners', 'multienum',
                                                false, false, null,
                                                array($f_groups));
        }

        if (!count($f_users) && !count($f_groups)) {
            $owner_params = array(
                _("There are no users to which this ticket can be assigned."));
            $this->addVariable(_("Owners"), 'owners', 'invalid', false, false,
                               null, $owner_params);
        }
    }

}