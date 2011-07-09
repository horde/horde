<?php
/**
 * The Ulaform script to delete a form.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ulaform', array('admin' => true));

$delvars = Horde_Variables::getDefaultVariables();
$form_id = $delvars->get('form_id');
$form_submit = $delvars->get('submitbutton');

/* Set up the forms. */
$ulaform_driver = $injector->getInstance('Ulaform_Factory_Driver')->create();
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
        try {
            $deleteform = $ulaform_driver->deleteForm($info['form_id']);
            $notification->push(_("Form deleted."), 'horde.success');
            header('Location: ' . Horde::url('forms.php', true));
            exit;
        } catch (Ulaform_Exception $e) {
            $notification->push(sprintf(_("Error deleting form. %s."), $e->getMessage()), 'horde.error');
        }
    }
} elseif (!empty($form_submit)) {
    $notification->push(_("Form has not been deleted."), 'horde.message');
    header('Location: ' . Horde::url('forms.php', true));
    exit;
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();
Horde::startBuffer();
$delform->renderActive($renderer, $delvars, 'delete.php', 'post');
$viewform->renderInactive($renderer, $viewvars);
$main = Horde::endBuffer();

$template = $injector->getInstance('Horde_Template');
$template->set('main', $main);

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $template->fetch(ULAFORM_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
