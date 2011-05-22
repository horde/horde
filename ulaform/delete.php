<?php
/**
 * The Ulaform script to delete a form.
 *
 * $Horde: ulaform/delete.php,v 1.41 2009-07-14 18:43:45 selsky Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* Only admin should be using this. */
if (!Horde_Auth::isAdmin()) {
    Horde::authenticationFailureRedirect();
}

$delvars = Horde_Variables::getDefaultVariables();
$form_id = $delvars->get('form_id');
$form_submit = $delvars->get('submitbutton');

/* Set up the forms. */
$viewvars = new Horde_Variables($ulaform_driver->getForm($form_id));
$viewform = new Horde_Form($viewvars, _("Form Details"));
$delform = new Horde_Form($delvars, _("Delete this form?"));
$viewform->addVariable(_("Name"), 'form_name', 'text', false);
$viewform->addVariable(_("Action"), 'form_action', 'email', false);
$delform->setButtons(array(_("Delete"), _("Do not delete")));
$delform->addHidden('', 'form_id', 'int', true);

if ($form_submit == _("Delete")) {
    $delform->validate($delvars);

    if ($delform->isValid()) {
        $delform->getInfo($delvars, $info);
        $deleteform = $ulaform_driver->deleteForm($info['form_id']);
        if (is_a($deleteform, 'PEAR_Error')) {
            Horde::logMessage($deleteform, __FILE__, __LINE__, PEAR_LOG_ERR);
            $notification->push(sprintf(_("Error deleting form. %s."), $deleteform->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Form deleted."), 'horde.success');
            $url = Horde::applicationUrl('forms.php', true);
            header('Location: ' . $url);
            exit;
        }
    }
} elseif (!empty($form_submit)) {
    $notification->push(_("Form has not been deleted."), 'horde.message');
    $url = Horde::applicationUrl('forms.php', true);
    header('Location: ' . $url);
    exit;
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();
$main = Horde_Util::bufferOutput(array($delform, 'renderActive'), $renderer, $delvars, 'delete.php', 'post') .
        Horde_Util::bufferOutput(array($viewform, 'renderInactive'), $renderer, $viewvars);

$template->set('main', $main);
$template->set('menu', Ulaform::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require ULAFORM_TEMPLATES . '/common-header.inc';
echo $template->fetch(ULAFORM_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
