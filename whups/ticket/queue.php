<?php
/**
 * Displays and handles the form to move a ticket to a different queue.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
$page_output->addLinkTag($ticket->feedLink());
$vars = Horde_Variables::getDefaultVariables();
$vars->set('id', $id = $ticket->getId());
$form = $vars->get('formname');
if ($form != 'whups_form_queue_stepone') {
    $q = $vars->get('queue');
    $v = $vars->get('version');
    $t = $vars->get('type');
}

// Get all ticket details from storage, then override any values that are
// in the process of being edited.
foreach ($ticket->getDetails() as $varname => $value) {
    $vars->add($varname, $value);
}
if (!empty($q)) {
    $vars->set('queue', $q);
}
if (!empty($v)) {
    $vars->set('version', $v);
}
if (!empty($t)) {
    $vars->set('type', $t);
}

// Check permissions on this ticket.
if (!Whups::hasPermission($ticket->get('queue'), 'queue', Horde_Perms::DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$action = '';

if ($form == 'whups_form_queue_stepone') {
    $setqueueform = new Whups_Form_Queue_StepOne($vars);
    if ($setqueueform->validate($vars)) {
        $action = 'sq2';
    }
}

if ($form == 'whups_form_queue_steptwo') {
    $setqueueform = new Whups_Form_Queue_StepTwo($vars);
    if ($setqueueform->validate($vars)) {
        $action = 'sq3';
    } else {
        $action = 'sq2';
    }
}

if ($form == 'whups_form_queue_stepthree') {
    $smform3 = new Whups_Form_Queue_StepThree($vars);
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

        try {
            $ticket->commit();
            $notification->push(
                sprintf(_("Moved ticket %d to \"%s\""), $id, $ticket->get('queue_name')),
                'horde.success');
            $ticket->show();
        } catch (Whups_Exception $e) {
                $notification->push($e, 'horde.error');
        }
    } else {
        $action = 'sq3';
    }
}

$page_output->header(array(
    'title' => sprintf(_("Set Queue for %s"), '[#' . $id . '] ' . $ticket->get('summary'))
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('queue');

$r = new Horde_Form_Renderer();

switch ($action) {
case 'sq2':
    $form1 = new Whups_Form_Queue_StepOne($vars, _("Set Queue - Step 1"));
    $form2 = new Whups_Form_Queue_StepTwo($vars, _("Set Queue - Step 2"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderActive($r, $vars, Horde::url('ticket/queue.php'), 'post');
    break;

case 'sq3':
    $form1 = new Whups_Form_Queue_StepOne($vars, _("Set Queue - Step 1"));
    $form2 = new Whups_Form_Queue_StepTwo($vars, _("Set Queue - Step 2"));
    $form3 = new Whups_Form_Queue_StepThree($vars, _("Set Queue - Step 3"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderInactive($r, $vars);
    echo '<br />';
    $form3->renderActive($r, $vars, Horde::url('ticket/queue.php'), 'post');
    break;

default:
    $form1 = new Whups_Form_Queue_StepOne($vars, _("Set Queue - Step 1"));
    $form1->renderActive($r, $vars, Horde::url('ticket/queue.php'), 'post');
    break;
}

$page_output->footer();
