<?php
/**
 * The Ulaform script to create a new form or edit an existing form's details.
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
$changed_action = false;
$vars = Horde_Variables::getDefaultVariables();
$form_id = $vars->get('form_id');
$old_form_action = $vars->get('old_form_action');
$formname = $vars->get('formname');
$ulaform_driver = $injector->getInstance('Ulaform_Factory_Driver')->create();

/* Check if a form is being edited. */
if ($form_id && !$formname) {
    $vars = new Horde_Variables($ulaform_driver->getForm($form_id));
}

/* Get the form action var, whether from edit or new. */
$form_action = $vars->get('form_action');

/* Get details for this action. */
$actions = Ulaform_Action::getDrivers();

/* Check if user changed action. */
if ($form_action != $old_form_action && $formname) {
    $changed_action = true;
    $notification->push(_("Changed action driver."), 'horde.message');
}

/* Selected a action so get the info and parameters for this action. */
if ($form_action) {
    $action_info = Ulaform::getActionInfo($form_action);
    $action_params = Ulaform::getActionParams($form_action);
}

/* Set up the form. */
$form = new Horde_Form($vars, _("Form Details"));
$form->setButtons((empty($form_id) ? _("Create") : _("Modify")), true);
$form->addHidden('', 'form_id', 'int', false);
$form->addHidden('', 'old_form_action', 'text', false);
$form->addVariable(_("Name"), 'form_name', 'text', true);

/* Selectable action drivers and update form based on selection. */
$v = &$form->addVariable(_("Action"), 'form_action', 'enum', true, false, null, array(array('' => _("-- select --")) + $actions));
$v->setAction(Horde_Form_Action::factory('submit'));
$v->setHelp('form-action');

if (!empty($action_params)) {
    foreach ($action_params as $id => $param) {
        $param['required'] = isset($param['required']) ? $param['required']
                                                       : true;
        $param['readonly'] = isset($param['readonly']) ? $param['readonly']
                                                       : false;
        $param['desc'] = isset($param['desc']) ? $param['desc']
                                               : null;
        $param['params'] = isset($param['params']) ? $param['params']
                                               : null;

        $form->addVariable($param['label'], 'form_params[' . $id . ']', $param['type'], $param['required'], $param['readonly'], $param['desc'], $param['params']);
    }
}

/* Set default language for the form. */
$v = &$form->addVariable(_("Default language"), 'form_params[language]', 'enum', false, false, null, array($registry->nlsconfig->languages, _("-- default configured --")));
$v->setOption('htmlchars', true);

/* TODO: set up Ulaform to insert any javascript saved here into the form. */
$v = &$form->addVariable(_("Javascript to execute on form \"submit\":"), 'form_onsubmit', 'longtext', false, false, null, array(3, 40));
$v->setHelp('on-submit');

/* Set up the action choice fields. */
$vars->set('old_form_action', $form_action);

if ($formname && !$changed_action) {
    $form->validate($vars);

    if ($form->isValid()) {
        $form->getInfo($vars, $info);
        try {
            $form_id = $ulaform_driver->saveForm($info);
            $notification->push(_("Form details saved."), 'horde.success');
            Horde::url('forms.php', true)->redirect();
        } catch (Ulaform_Exception $e) {
            $notification->push(sprintf(_("Error saving form. %s."), $e->getMessage()), 'horde.error');
        }
    }
}

/* Render the form. */
$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
Horde::startBuffer();
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('edit.php'), 'post');
$view->main = Horde::endBuffer();

$page_output->header(array(
    'title' => _("Edit Forms")
));
$notification->notify(array('listeners' => 'status'));
echo $view->render('main');
$page_output->footer();
