<?php
/**
 * This Ulaform script allows for the fields in a form to be sorted in
 * a specific order, using the standard Horde_Form sorter field.
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
$form_id = $vars->get('form_id');
$formname = $vars->get('formname');
$fields = $ulaform_driver->getFieldsArray($form_id);

/* Set up the form object. */
$sortform = new Horde_Form($vars, _("Sort Fields"));

/* Set up the form. */
$sortform->setButtons(_("Save"));
$sortform->addVariable(_("Select the sort order of the fields"), 'field_order', 'sorter', false, false, null, array($fields, 12));
$sortform->addHidden('', 'form_id', 'int', true);

if ($formname) {
    $sortform->validate($vars);

    if ($sortform->isValid()) {
        $sortform->getInfo($vars, $info);
        try {
            $sort = $injector->getInstance('Ulaform_Factory_Driver')->create()->sortFields($info);
            $notification->push(_("Field sort order saved."), 'horde.success');
            Horde::url('fields.php', true)->add('form_id', $form_id)->redirect();
        } catch (Horde_Exception $e) {
            $notification->push(sprintf(_("Error saving fields. %s."), $e->getMessage()), 'horde.error');
        }
    }
}

/* Render the form. */
$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
Horde::startBuffer();
$sortform->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('sortfields.php'), 'post');
$view->main = Horde::endBuffer();

$page_output->header();
$notification->notify(array('listeners' => 'status'));
echo $view->render('main');
$page_output->footer();
