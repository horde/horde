<?php
/**
 * This script manages the deletion of fields from a Ulaform form.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ulaform', array('admin' => true));

/* Get some variables. */
$vars = Horde_Variables::getDefaultVariables();
$formname = $vars->get('formname');

if (is_null($formname)) {
    if ($vars->exists('field_id')) {
        $vars = $ulaform_driver->getField($vars->get('form_id'), $vars->get('field_id'));
        $vars = new Horde_Variables($vars);
    } else {
        $notification->push(_("No field specified."), 'horde.warning');
        Horde::url('fields.php', true)->add('form_id', $vars->get('form_id'))->redirect();
    }
}

/* Set up the form. */
$fieldform = new Horde_Form($vars, _("Delete Field"));
$fieldform->setButtons(array(_("Delete"), _("Do not delete")));
$fieldform->addHidden('', 'field_id', 'int', true);
$fieldform->addHidden('', 'form_id', 'int', true);
$fieldform->addHidden('', 'field_name', 'text', false);
$fieldform->addVariable(_("Delete this field?"), 'field_name', 'text', false, true);

if ($vars->get('submitbutton') == _("Delete")) {
    $fieldform->validate($vars);

    if ($fieldform->isValid()) {
        $fieldform->getInfo($vars, $info);
        try {
            $del_field = $injector->getInstance('Ulaform_Factory_Driver')->create()->deleteField($info['field_id']);
            $notification->push(sprintf(_("Field \"%s\" deleted."), $info['field_name']), 'horde.success');
            Horde::url('fields.php', true)->add('form_id', $info['form_id'])->redirect();
        } catch (Ulaform_Exception $e) {
            $notification->push(sprintf(_("Error deleting field. %s."), $e->getMessage()), 'horde.error');
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Field not deleted."), 'horde.message');
    Horde::url('fields.php', true)->add('form_id', $vars->get('form_id'))->redirect();
}

/* Render the form. */
$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
Horde::startBuffer();
$fieldform->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('deletefield.php'), 'post');
$view->main = Horde::endBuffer();

$page_output->header();
$notification->notify(array('listeners' => 'status'));
echo $view->render('main');
$page_output->footer();
