<?php
/**
 * This file contains all Horde_Form classes to create a new ticket.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
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
class CreateStep1Form extends Horde_Form {

    var $_useFormToken = false;

    function CreateStep1Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Create Ticket - Step 1"));

        $queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue',
                                           Horde_Perms::EDIT);
        if (!$queues) {
            $this->addVariable(
                _("Queue Name"), 'queue', 'invalid', true, false, null,
                array(_("There are no queues which you can create tickets in.")));
        } else {
            foreach (array_keys($queues) as $queue_id) {
                $info = $whups_driver->getQueue($queue_id);
                if (!empty($info['description'])) {
                    $queues[$queue_id] .= ' [' . $info['description'] . ']';
                }
            }

            // Auto-select the only queue if only one option is available
            if (count($queues) == 1) {
                $vars->set('queue', array_pop(array_keys($queues)));
            }

            require_once 'Horde/Form/Action.php';
            $queues = &$this->addVariable(_("Queue Name"), 'queue', 'enum',
                                          true, false, null,
                                          array($queues, _("Choose:")));
            $queues->setAction(Horde_Form_Action::factory('submit'));
        }
    }

}

/**
 * @package Whups
 */
class CreateStep2Form extends Horde_Form {

    var $_useFormToken = false;

    function CreateStep2Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Create Ticket - Step 2"));

        $types = $whups_driver->getTypes($vars->get('queue'));
        $info  = $whups_driver->getQueue($vars->get('queue'));
        $type = $whups_driver->getDefaultType($vars->get('queue'));
        if (count($types) == 0) {
            $typetype = 'invalid';
            $type_params = array(_("There are no ticket types associated with this queue; until there are, you cannot create any tickets in this queue."));
        } else {
            $typetype = 'enum';
            $type_params = array($types);
            if (empty($type) || !isset($types[$type])) {
                $type_params[] = _("Choose:");
            }
        }
        $types = &$this->addVariable(_("Ticket Type"), 'type', $typetype, true,
                                     false, null, $type_params);
        $types->setDefault($type);

        if (!empty($info['versioned'])) {
            $versions = $whups_driver->getVersions($vars->get('queue'));
            if (count($versions) == 0) {
                $vtype = 'invalid';
                $v_params = array(_("This queue requires that you specify a version, but there are no versions associated with it. Until versions are created for this queue, you will not be able to create tickets."));
            } else {
                $vtype = 'enum';
                $v_params = array($versions);
            }
            $this->addVariable(_("Queue Version"), 'version', $vtype, true,
                               false, null, $v_params);
        } else {
            require_once 'Horde/Form/Action.php';
            $types->setAction(Horde_Form_Action::factory('submit'));
        }
    }

}

/**
 * @package Whups
 */
class CreateStep3Form extends Horde_Form {

    function CreateStep3Form(&$vars)
    {
        global $whups_driver, $conf;

        parent::Horde_Form($vars, _("Create Ticket - Step 3"));

        $states = $whups_driver->getStates($vars->get('type'), 'unconfirmed');
        $attributes = $whups_driver->getAttributesForType($vars->get('type'));

        $queue = $vars->get('queue');
        $info = $whups_driver->getQueue($queue);

        if ($GLOBALS['registry']->getAuth()) {
            $states2 = $whups_driver->getStates($vars->get('type'),
                                                array('new', 'assigned'));
            if (is_array($states2)) {
                $states = $states + $states2;
            }
        }

        if (Whups::hasPermission($queue, 'queue', 'requester')) {
            $this->addVariable(_("The Requester's Email Address"), 'user_email',
                               'whups_email', false);
        } elseif (!$GLOBALS['registry']->getAuth()) {
            $this->addVariable(_("Your Email Address"), 'user_email', 'email',
                               true);
            if (!empty($conf['guests']['captcha'])) {
                $this->addVariable(
                    _("Spam protection"), 'captcha', 'figlet', true, null, null,
                    array(Whups::getCAPTCHA(!$this->isSubmitted()),
                          $conf['guests']['figlet_font']));
            }
        }

        // Silently default the state if there is only one choice
        if (count($states) == 1) {
            $vars->set('state', reset(array_keys($states)));
            $f_state = &$this->addHidden(_("Ticket State"), 'state', 'enum',
                                           true, false, null, array($states));
        } else {
            $f_state = &$this->addVariable(_("Ticket State"), 'state', 'enum',
                                           true, false, null, array($states));
            $f_state->setDefault(
                $whups_driver->getDefaultState($vars->get('type')));
        }

        $f_priority = &$this->addVariable(
            _("Priority"), 'priority', 'enum', true, false, null,
            array($whups_driver->getPriorities($vars->get('type'))));
        $f_priority->setDefault(
            $whups_driver->getDefaultPriority($vars->get('type')));
        $this->addVariable(_("Due Date"), 'due', 'datetime', false, false);
        $this->addVariable(_("Summary"), 'summary', 'text', true, false);
        $this->addVariable(_("Attachment"), 'newattachment', 'file', false);
        $this->addVariable(_("Description"), 'comment', 'longtext', true);
        foreach ($attributes as $attribute_id => $attribute_value) {
            $this->addVariable($attribute_value['human_name'],
                               'attributes[' . $attribute_id . ']',
                               $attribute_value['type'],
                               $attribute_value['required'],
                               $attribute_value['readonly'],
                               $attribute_value['desc'],
                               $attribute_value['params']);
        }
    }

    function validate(&$vars, $canAutoFill = false)
    {
        global $conf;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!$GLOBALS['registry']->getAuth() && !empty($conf['guests']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->insertVariableBefore(
                    'state', _("Spam protection"), 'captcha', 'figlet', true,
                    null, null,
                    array(Whups::getCAPTCHA(true),
                          $conf['guests']['figlet_font']));
            }
            return false;
        }

        return true;
    }

}

/**
 * @package Whups
 */
class CreateStep4Form extends Horde_Form {

    function CreateStep4Form(&$vars)
    {
        global $whups_driver, $conf;

        parent::Horde_Form($vars, _("Create Ticket - Step 4"));

        /* Preserve previously uploaded attachments. */
        $this->addHidden('', 'deferred_attachment', 'text', false);

        /* Groups. */
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        if ($conf['prefs']['assign_all_groups']) {
            $mygroups = $groups->listGroups();
        } else {
            $mygroups = $groups->getGroupMemberships($GLOBALS['registry']->getAuth());
        }

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
            $owners->setDefault($whups_driver->getOwners($vars->get('id')));
        }

        if (count($f_groups)) {
            asort($f_groups);
            $group_owners = &$this->addVariable(_("Group Owners"),
                                                'group_owners', 'multienum',
                                                false, false, null,
                                                array($f_groups));
            $group_owners->setDefault(
                $whups_driver->getOwners($vars->get('id')));
        }

        if (!count($f_users) && !count($f_groups)) {
            $owner_params = array(
                _("There are no users to which this ticket can be assigned."));
            $this->addVariable(_("Owners"), 'owners', 'invalid', false, false,
                               null, $owner_params);
        }
    }

}

/**
 * @package Horde_Form
 */
class Horde_Form_Type_whups_email extends Horde_Form_Type_email {}
