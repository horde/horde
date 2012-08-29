<?php
/**
 * The Ulaform script to add fields to a Ulaform form or to edit existing ones.
 * It recognises what extra parameters need to be instered for a particular
 * field and adjusts the form accordingly.
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
$field_id = $vars->get('field_id');
$form_id = $vars->get('form_id');
$old_field_type = $vars->get('old_field_type');

/* Check if a field is being edited. */
if ($field_id && !$formname) {
    $vars = $ulaform_driver->getField($form_id, $vars->get('field_id'));
    $vars = new Horde_Variables($vars);
}

/* Set up the form. */
$fieldform = new Horde_Form($vars, _("Field Details"));
$fieldform->setButtons(_("Save Field"));
$fieldform->addHidden('', 'field_id', 'int', false);
$fieldform->addHidden('', 'form_id', 'int', false);
$fieldform->addHidden('', 'field_order', 'int', false);
$fieldform->addHidden('', 'old_field_type', 'text', false);
$fieldform->addVariable(_("Label"), 'field_label', 'text', true);
$fieldform->addVariable(_("Internal name"), 'field_name', 'text', false, false, _("Set this name if you have a particular reason to override the automatic internal naming of fields."));

/* Set up the field type selection, with a submit action. */
$fields = Ulaform::getFieldTypes();
$v = &$fieldform->addVariable(_("Type"), 'field_type', 'enum', true, false, null, array($fields, true));
$v->setAction(Horde_Form_Action::factory('submit'));
$v->setOption('trackchange', true);

$fieldform->addVariable(_("Required"), 'field_required', 'boolean', false);
$fieldform->addVariable(_("Read only"), 'field_readonly', 'boolean', false);
$fieldform->addVariable(_("Description"), 'field_desc', 'longtext', false, false, '', array(3, 40));

/* Check if the submitted field type has extra parameters and set them up. */
$field_type = $vars->get('field_type');
$available_params = Ulaform::getFieldParams($field_type);
if (!is_null($vars->get('formname')) &&
    $vars->get($v->getVarName()) != $vars->get('__old_' . $v->getVarName()) &&
    !empty($available_params)) {
    $notification->push(_("This field type has extra parameters."), 'horde.message');
}
foreach ($available_params as $name => $param) {
    $field_id = 'field_params[' . $name . ']';
    $param['required'] = isset($param['required']) ? $param['required']
                                                   : null;
    $param['readonly'] = isset($param['readonly']) ? $param['readonly']
                                                   : null;
    $param['desc'] = isset($param['desc']) ? $param['desc']
                                           : null;

    $fieldform->addVariable($param['label'], $field_id, $param['type'], $param['required'], $param['readonly'], $param['desc']);
}

/* Set the current field type to the old field type var. */
$vars->set('old_field_type', $field_type);

if ($fieldform->validate($vars)) {
    /* Save field if valid and the current and old field type match. */
    $fieldform->getInfo($vars, $info);
    try {
        $save_field = $injector->getInstance('Ulaform_Factory_Driver')->create()->saveField($info);
        $notification->push(_("Field saved."), 'horde.success');
        Horde::url('fields.php', true)->add('form_id', $info['form_id'])->redirect();
    } catch (Horde_Exception $e) {
        $notification->push(sprintf(_("Error saving field. %s."), $e->getMessage()), 'horde.error');
    }
}

/* Get a field list. */
try {
    $fields_list = $ulaform_driver->getFieldsList($form_id);
    if (empty($fields_list)) {
        /* Show a warning if no fields present. */
        $notification->push(_("No available fields."), 'horde.warning');
    }
} catch (Horde_Exception $e) {
    /* Go back to forms if inexistant form_id, permission denied or another
     * error. */
    $notification->push($e->getMessage(), 'horde.error');
    Horde::url('forms.php', true)->redirect();
}

/* Set up the template. */
$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));

/* Set up the template action links. */
$actions = Horde::link(Horde::url('genhtml.php')->add('form_id', $form_id), _("Generate HTML")) . Horde::img('html.png', _("Generate HTML")) . '</a> ' . Horde::link(Horde::url('display.php')->add('form_id', $form_id), _("Preview")) . Horde::img('display.png', _("Preview")) . '</a> ' . Horde::link(Horde::url('sortfields.php')->add('form_id', $form_id), _("Sort fields")) . Horde::img('sort.png', _("Sort fields")) . '</a>';
$view->actions = $actions;

/* Render the form. */
Horde::startBuffer();
$fieldform->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('fields.php'), 'post');
$view->inputform = Horde::endBuffer();

/* Set up the field list. */
$fieldproperties = array('name' => _("Name"), 'label' => _("Label"), 'type' => _("Type"), 'required' => _("Required"), 'readonly' => _("Read only"), 'desc' => _("Description"));
$view->fieldproperties = $fieldproperties;
$images = array(
    'delete' => Horde::img('delete.png', _("Delete Field"), null),
    'edit' => Horde::img('edit.png', _("Edit Field"), ''));
$view->images = $images;
$view->fields = $fields_list;

/* Render the page. */
$page_output->header(array(
    'title' => _("Form Fields")
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $view->render('fields');
$page_output->footer();
