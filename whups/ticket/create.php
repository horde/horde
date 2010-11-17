<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
if (Horde_Util::getPost('formname') == 'createstep3form') {
    $params = array('notransparent' => true);
} else {
    $params = array();
}
Horde_Registry::appInit('whups', $params);

require_once WHUPS_BASE . '/lib/Forms/CreateTicket.php';
require_once WHUPS_BASE . '/lib/Forms/VarRenderer.php';

$empty = '';
$beendone = 0;
$wereerrors = 0;

$vars = Horde_Variables::getDefaultVariables($empty);
$formname = $vars->get('formname');

$form1 = new CreateStep1Form($vars);
$form2 = new CreateStep2Form($vars);
$form3 = new CreateStep3Form($vars);
$form4 = new CreateStep4Form($vars);
$r = new Horde_Form_Renderer(
    array('varrenderer_driver' => 'whups'));

$valid4 = $form4->validate($vars) &&
     $formname == 'createstep4form';
$valid3 = $form3->validate($vars, true);
$valid2 = $form2->validate($vars, !$form1->isSubmitted());
$valid1 = $form1->validate($vars, true);
$doAssignForm = $GLOBALS['registry']->getAuth() &&
    $whups_driver->isCategory('assigned', $vars->get('state'));

if ($valid1 && $valid2 && $valid3 &&
    // Don't validate the assignment form if it isn't being used.
    (!$doAssignForm || $valid4)) {

    $form1->getInfo($vars, $info);
    $form2->getInfo($vars, $info);
    $form3->getInfo($vars, $info);
    if ($doAssignForm) {
        $form4->getInfo($vars, $info);
    }

    $ticket = Whups_Ticket::newTicket($info, $GLOBALS['registry']->getAuth());
    if (is_a($ticket, 'PEAR_Error')) {
        Horde::logMessage($ticket, 'ERR');
        $notification->push(sprintf(_("Adding your ticket failed: %s."),
                                    $ticket->getMessage()),
                            'horde.error');
        Horde::url('ticket/create.php', true)->redirect();
    }
    $notification->push(sprintf(_("Your ticket ID is %s. An appropriate person has been notified of this request."), $ticket->getId()), 'horde.success');
    $ticket->show();
    exit;
}

// Start the page.
$title = _("New Ticket");
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';

if ($valid3 && $valid2 && $valid1) {
    $form4->open($r, $vars, 'create.php', 'post');

    // Preserve previous forms.
    $form1->preserve($vars);
    $r->_name = $form1->getName();
    $r->beginInactive($form1->getTitle());
    $r->renderFormInactive($form1, $vars);
    $r->end();
    echo '<br />';

    $form2->preserve($vars);
    $r->_name = $form2->getName();
    $r->beginInactive($form2->getTitle());
    $r->renderFormInactive($form2, $vars);
    $r->end();
    echo '<br />';

    $form3->preserve($vars);
    $r->_name = $form3->getName();
    $r->beginInactive($form3->getTitle());
    $r->renderFormInactive($form3, $vars);
    $r->end();
    echo '<br />';

    // Preserve an uploaded file if there was one.
    $form3->getInfo($vars, $info);
    if (!empty($info['newattachment']['name'])) {
        $file_name = $info['newattachment']['name'];

        $tmp_file_path = tempnam(Horde::getTempDir(), 'att');
        if (move_uploaded_file($info['newattachment']['tmp_name'],
                               $tmp_file_path)) {
            $session->set('whups', 'deferred_attachment/' . $file_name, $tmp_file_path);
            $vars->set('deferred_attachment', $file_name);
            $form3->preserveVarByPost($vars, 'deferred_attachment');
        }
    }

    // Render the 4th stage form.
    if ($formname != 'createstep4form') {
        $form4->clearValidation();
    }
    $r->_name = $form4->getName();
    $r->beginActive($form4->getTitle());
    $r->renderFormActive($form4, $vars);
    $r->submit();
    $r->end();
    $form3->close($r);

    $beendone = 1;
} elseif ($valid2 && $valid1) {
    $form3->open($r, $vars, 'create.php', 'post');

    // Render the stage 1 form readonly.
    $form1->preserve($vars);
    $r->beginInactive($form1->getTitle());
    $r->renderFormInactive($form1, $vars);
    $r->end();
    echo '<br />';

    // Render the stage 2 form readonly.
    $form2->preserve($vars);
    $r->beginInactive($form2->getTitle());
    $r->renderFormInactive($form2, $vars);
    $r->end();
    echo '<br />';

    // Render the third stage form.
    if ($formname != 'createstep3form') {
        $form3->clearValidation();
    }
    $r->beginActive($form3->getTitle());
    $r->renderFormActive($form3, $vars);
    $r->submit(_("Submit"), true);
    $r->end();

    $form3->close($r);

    $beendone = 1;
} else {
    if ($valid1) {
        $form2->open($r, $vars, 'create.php', 'post');

        // Render the original form readonly.
        $form1->preserve($vars);
        $r->beginInactive($form1->getTitle());
        $r->renderFormInactive($form1, $vars);
        $r->end();
        echo '<br />';

        // Render the second stage form.
        if ($formname != 'createstep2form') {
            $form2->clearValidation();
        }
        $r->beginActive($form2->getTitle());
        $r->renderFormActive($form2, $vars);
        $r->submit();
        $r->end();

        $form2->close($r);

        $beendone = 1;
    } else {
        if ($formname != 'createstep1form') {
            $form1->clearValidation();
        }
        $form1->open($r, $vars, 'create.php', 'post');
        $r->beginActive($form1->getTitle());
        $r->renderFormActive($form1, $vars);
        $r->submit();
        $r->end();
        $form1->close($r);
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
