<?php
/**
 * Displays and handles the form to change the ticket type.
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
$details = $ticket->getDetails();
if (!Whups::hasPermission($details['queue'], 'queue', 'update')) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
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
if ($form == 'whups_form_settypestepone') {
    $settypeform = new Whups_Form_SetTypeStepOne($vars);
    if ($settypeform->validate($vars)) {
        $action = 'st2';
    } else {
        $action = 'st';
    }
}

if ($form == 'whups_form_settypesteptwo') {
    $settypeform = new Whups_Form_SetTypeStepTwo($vars);
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

        try {
            $ticket->commit();
            $notification->push(_("Successfully changed ticket type."), 'horde.success');
            $ticket->show();
        } catch (Whups_Exception $e) {
            $notification->push($e, 'horde.error');
        }
    } else {
        $notification->push(var_export($settypeform->getErrors(), true), 'horde.error');
        $action = 'st2';
    }
}

$page_output->header(array(
    'title' => sprintf(_("Set Type for %s"), '[#' . $id . '] ' . $ticket->get('summary'))
));
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $id);
echo $tabs->render('type');

$r = new Horde_Form_Renderer();

switch ($action) {
case 'st2':
    $form1 = new Whups_Form_SetTypeStepOne($vars, _("Set Type - Step 1"));
    $form2 = new Whups_Form_SetTypeStepTwo($vars, _("Set Type - Step 2"));

    $form1->renderInactive($r, $vars);
    echo '<br />';
    $form2->renderActive($r, $vars, Horde::url('ticket/type.php'), 'post');
    break;

default:
    $form1 = new Whups_Form_SetTypeStepOne($vars, _("Set Type - Step 1"));
    $form1->renderActive($r, $vars, Horde::url('ticket/type.php'), 'post');
    break;
}

$page_output->footer();
