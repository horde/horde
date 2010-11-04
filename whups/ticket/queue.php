<?php
/**
 * Displays and handles the form to move a ticket to a different queue.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

/**
 * Queue editing forms.
 */
class SetQueueStep1Form extends Horde_Form {

    function SetQueueStep1Form(&$vars, $title = '')
    {
        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        /* Queues. */
        $this->addVariable(
            _("New Queue"), 'queue', 'enum', true, false, null,
            array(Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(),
                                           'queue', Horde_Perms::EDIT)));
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);

        /* Group restrictions. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => Horde_Perms::EDIT)) ||
            $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('whups:hiddenComments',
                                             $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $mygroups = $groups->getGroupMemberships($GLOBALS['registry']->getAuth());
            if ($mygroups) {
                foreach (array_keys($mygroups) as $gid) {
                    $grouplist[$gid] = $groups->getGroupName($gid, true);
                }
                asort($grouplist);
                $grouplist = array_merge(array(0 => _("Any Group")),
                                         $grouplist);
                $this->addVariable(_("Viewable only by members of"), 'group',
                                   'enum', true, false, null,
                                   array($grouplist));
            }
        }
    }

}

class SetQueueStep2Form extends Horde_Form {

    function SetQueueStep2Form(&$vars, $title = '')
    {
        global $whups_driver;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'group', 'int', false, true);
        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'newcomment', 'longtext', false, true);

        /* Give the user an opportunity to check that type, version,
         * etc. are still valid. */

        $queue = $vars->get('queue');

        $info = $whups_driver->getQueue($queue);
        if (!empty($info['versioned'])) {
            $versions = $whups_driver->getVersions($vars->get('queue'));
            if (count($versions) == 0) {
                $vtype = 'invalid';
                $v_params = array(_("This queue requires that you specify a version, but there are no versions associated with it. Until versions are created for this queue, you will not be able to create tickets."));
            } else {
                $vtype = 'enum';
                $v_params = array($versions);
            }
            $this->addVariable(_("Queue Version"), 'version', $vtype, true, false, null, $v_params);
        }

        $this->addVariable(_("Type"), 'type', 'enum', true, false, null, array($whups_driver->getTypes($queue)));
    }

}

class SetQueueStep3Form extends Horde_Form {

    function SetQueueStep3Form(&$vars, $title = '')
    {
        global $whups_driver;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'group', 'int', false, true);
        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'newcomment', 'longtext', false, true);

        $info = $whups_driver->getQueue($vars->get('queue'));
        if (!empty($info['versioned'])) {
            $this->addHidden('', 'version', 'int', true, true);
        }

        /* Give user an opportunity to check that state and priority
         * are still valid. */
        $type = $vars->get('type');
        $this->addVariable(_("State"), 'state', 'enum', true, false, null, array($whups_driver->getStates($type)));
        $this->addVariable(_("Priority"), 'priority', 'enum', true, false, null, array($whups_driver->getPriorities($type)));
    }

}

$ticket = Whups::getCurrentTicket();
$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
$form = $vars->get('formname');
if ($form != 'setqueuestep1form') {
    $q = $vars->get('queue');
}
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}
if (!empty($q)) {
    $vars->set('queue', $q);
}

// Check permissions on this ticket.
if (!Whups::hasPermission($ticket->get('queue'), 'queue', Horde_Perms::DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$action = '';

if ($form == 'setqueuestep1form') {
    $setqueueform = new SetQueueStep1Form($vars);
    if ($setqueueform->validate($vars)) {
        $action = 'sq2';
    }
}

if ($form == 'setqueuestep2form') {
    $setqueueform = new SetQueueStep2Form($vars);
    if ($setqueueform->validate($vars)) {
        $action = 'sq3';
    } else {
        $action = 'sq2';
    }
}

if ($form == 'setqueuestep3form') {
    $smform3 = new SetQueueStep3Form($vars);
    if ($smform3->validate($vars)) {
        $smform3->getInfo($vars, $info);

        $ticket->change('queue', $info['queue']);
        $ticket->change('type', $info['type']);
        $ticket->change('state', $info['state']);
        $ticket->change('priority', $info['priority']);

        if (!empty($info['version'])) {
            $ticket->change('version', $info['version']);
        }

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
            $notification->push(sprintf(_("Moved ticket %d to \"%s\""), $id, $ticket->get('queue_name')), 'horde.success');
            $ticket->show();
        }
    } else {
        $action = 'sq3';
    }
}

$title = sprintf(_("Set Queue for %s"), '[#' . $id . '] ' . $ticket->get('summary'));
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('queue');

$r = new Horde_Form_Renderer();

switch ($action) {
case 'sq2':
    $form1 = new SetQueueStep1Form($vars, _("Set Queue - Step 1"));
    $form2 = new SetQueueStep2Form($vars, _("Set Queue - Step 2"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderActive($r, $vars, 'queue.php', 'post');
    break;

case 'sq3':
    $form1 = new SetQueueStep1Form($vars, _("Set Queue - Step 1"));
    $form2 = new SetQueueStep2Form($vars, _("Set Queue - Step 2"));
    $form3 = new SetQueueStep3Form($vars, _("Set Queue - Step 3"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderInactive($r, $vars);
    echo '<br />';
    $form3->renderActive($r, $vars, 'queue.php', 'post');
    break;

default:
    $form1 = new SetQueueStep1Form($vars, _("Set Queue - Step 1"));
    $form1->renderActive($r, $vars, 'queue.php', 'post');
    break;
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
