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
class Whups_Form_Ticket_CreateStepThree extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver, $conf;

        parent::__construct($vars, _("Create Ticket - Step 3"));

        $states = $whups_driver->getStates($vars->get('type'), 'unconfirmed');
        $attributes = $whups_driver->getAttributesForType($vars->get('type'));

        $queue = $vars->get('queue');
        $info = $whups_driver->getQueue($queue);

        if ($GLOBALS['registry']->getAuth()) {
            $states2 = $whups_driver->getStates(
                $vars->get('type'), array('new', 'assigned'));
            if (is_array($states2)) {
                $states = $states + $states2;
            }
        }

        if (Whups::hasPermission($queue, 'queue', 'requester')) {
            $test = $this->addVariable(
                _("The Requester's Email Address"), 'user_email',
                'whups:whupsemail', false);
        } elseif (!$GLOBALS['registry']->getAuth()) {
            $this->addVariable(
                _("Your Email Address"), 'user_email', 'email', true);
            if (!empty($conf['guests']['captcha'])) {
                $this->addVariable(
                    _("Spam protection"), 'captcha', 'figlet', true, null, null,
                    array(
                        Whups::getCAPTCHA(!$this->isSubmitted()),
                        $conf['guests']['figlet_font']));
            }
        }

        // Silently default the state if there is only one choice
        if (count($states) == 1) {
            $vars->set('state', reset(array_keys($states)));
            $f_state = &$this->addHidden(
                _("Ticket State"), 'state', 'enum', true, false, null, array($states));
        } else {
            $f_state = &$this->addVariable(
                _("Ticket State"), 'state', 'enum', true, false, null, array($states));
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
            $this->addVariable(
                $attribute_value['human_name'],
                'attributes[' . $attribute_id . ']',
                $attribute_value['type'],
                $attribute_value['required'],
                $attribute_value['readonly'],
                $attribute_value['desc'],
                $attribute_value['params']);
        }

        /* Comment permissions. */
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        $mygroups = $groups->listGroups($GLOBALS['registry']->getAuth());
        if ($mygroups) {
            foreach (array_keys($mygroups) as $gid) {
                $grouplist[$gid] = $groups->getName($gid, true);
            }
            asort($grouplist);
            $grouplist = array(0 => _("This comment is visible to everyone")) + $grouplist;
            $this->addVariable(
                _("Make this comment visible only to members of a group?"), 'group',
                'enum', false, false, null, array($grouplist));
        }
    }

    public function validate(&$vars, $canAutoFill = false)
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