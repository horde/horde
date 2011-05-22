<?php
/**
 * This script manages the deletion of fields from a Ulaform form.
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

/* Get some variables. */
$vars = Horde_Variables::getDefaultVariables();
$formname = $vars->get('formname');

if (is_null($formname)) {
    if ($vars->exists('field_id')) {
        $vars = $ulaform_driver->getField($vars->get('form_id'), $vars->get('field_id'));
        $vars = new Horde_Variables($vars);
    } else {
        $notification->push(_("No field specified."), 'horde.warning');
        $url = Horde_Util::addParameter(Horde::url('fields.php'),
                                  array('form_id' => $vars->get('form_id')),
                                  null, false);
        header('Location: ' . $url);
        exit;
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
        $del_field = $injector->getInstance('Ulaform_Factory_Driver')->create()->deleteField($info['field_id']);
        if (is_a($del_field, 'PEAR_Error')) {
            Horde::logMessage($del_field, 'ERR');
            $notification->push(sprintf(_("Error deleting field. %s."), $del_field->getMessage()), 'horde.error');
        } else {
            $notification->push(sprintf(_("Field \"%s\" deleted."), $info['field_name']), 'horde.success');
            $url = Horde_Util::addParameter(Horde::url('fields.php'),
                                      array('form_id' => $info['form_id']),
                                      null, false);
            header('Location: ' . $url);
            exit;
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Field not deleted."), 'horde.message');
    $url = Horde_Util::addParameter(Horde::url('fields.php'),
                              array('form_id' => $vars->get('form_id')),
                              null, false);
    header('Location: ' . $url);
    exit;
}

/* Render the form. */
$template = $injector->getInstance('Horde_Template');
Horde::startBuffer();
$fieldform->renderActive(new Horde_Form_Renderer(), $vars, 'deletefield.php', 'post');
$template->set('main', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $template->fetch(ULAFORM_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
