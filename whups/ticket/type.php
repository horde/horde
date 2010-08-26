<?php
/**
 * Displays and handles the form to change the ticket type.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

class SetTypeStep1Form extends Horde_Form {

    function SetTypeStep1Form(&$vars, $title = '')
    {
        global $whups_driver;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        /* Types */
        $queue = $vars->get('queue');
        $this->addVariable(_("New Type"), 'type', 'enum', true, false, null, array($whups_driver->getTypes($queue)));
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);

        /* Group restrictions. */
        $groups = Horde_Group::singleton();
        $mygroups = $groups->getGroupMemberships($GLOBALS['registry']->getAuth());
        if ($mygroups) {
            foreach (array_keys($mygroups) as $gid) {
                $grouplist[$gid] = $groups->getGroupName($gid, true);
            }
            asort($grouplist);
            $grouplist = array_merge(array(0 => _("Any Group")), $grouplist);
            $this->addVariable(_("Viewable only by members of"), 'group', 'enum', true, false, null, array($grouplist));
        }
    }

}

class SetTypeStep2Form extends Horde_Form {

    function SetTypeStep2Form(&$vars, $title = '')
    {
        global $whups_driver;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'group', 'int', false, false);
        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'newcomment', 'longtext', false, true);

        /* Give user an opportunity to check that state and priority
         * are still valid. */
        $type = $vars->get('type');
        $this->addVariable(_("State"), 'state', 'enum', true, false, null, array($whups_driver->getStates($type)));
        $this->addVariable(_("Priority"), 'priority', 'enum', true, false, null, array($whups_driver->getPriorities($type)));
    }

}

$ticket = Whups::getCurrentTicket();
$details = $ticket->getDetails();
if (!Whups::hasPermission($details['queue'], 'queue', 'update')) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}
$action = $vars->get('action');
$form = $vars->get('formname');

/* Set Type action. */
if ($form == 'settypestep1form') {
    $settypeform = new SetTypeStep1Form($vars);
    if ($settypeform->validate($vars)) {
        $action = 'st2';
    } else {
        $action = 'st';
    }
}

if ($form == 'settypestep2form') {
    $settypeform = new SetTypeStep2Form($vars);
    if ($settypeform->validate($vars)) {
        $settypeform->getInfo($vars, $info);

        $ticket->change('type', $info['type']);
        $ticket->change('state', $info['state']);
        $ticket->change('priority', $info['priority']);

        if (!empty($info['newcomment'])) {
            $ticket->change('comment', $info['newcomment']);
        }

        if (!empty($info['group'])) {
            $ticket->change('comment-perms', $info['group']);
        }

        $result = $ticket->commit();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
        } else {
            $notification->push(_("Successfully changed ticket type."), 'horde.success');
            $ticket->show();
        }
    } else {
        $notification->push(var_export($settypeform->getErrors()), 'horde.error');
        $action = 'st2';
    }
}

$title = sprintf(_("Set Type for %s"), '[#' . $id . '] ' . $ticket->get('summary'));
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('type');

$r = new Horde_Form_Renderer();

switch ($action) {
case 'st2':
    $form1 = new SetTypeStep1Form($vars, _("Set Type - Step 1"));
    $form2 = new SetTypeStep2Form($vars, _("Set Type - Step 2"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderActive($r, $vars, 'type.php', 'post');
    break;

default:
    $form1 = new SetTypeStep1Form($vars, _("Set Type - Step 1"));
    $form1->renderActive($r, $vars, 'type.php', 'post');
    break;
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
