<?php
/**
 * The Ulaform script to display a form.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ulaform');

$vars = Horde_Variables::getDefaultVariables();
$form_id = $vars->get('form_id');
$form_params = $vars->get('form_params');
$error = $done = false;

/* Get the stored form information from the backend. */
try {
    $form_info = $injector->getInstance('Ulaform_Factory_Driver')->create()->getForm($form_id, Horde_Perms::READ);
    if (!empty($form_info['form_params']['language'])) {
        $registry->setLanguageEnvironment($form_info['form_params']['language']);
    }
} catch (Horde_Exception $e) {
    $notification->push(sprintf(_("Could not fetch form ID \"%s\". %s"), $form_id, $e->getMessage()), 'horde.error');
    $error = true;
}

/* Add form variables. */
$form = new Horde_Form($vars);
$form->addHidden('', 'form_id', 'int', false);

$fields = $injector->getInstance('Ulaform_Factory_Driver')->create()->getFields($form_id);
foreach ($fields as $field) {
    /* In case of these types get array from stringlist. */
    if ($field['field_type'] == 'link' ||
        $field['field_type'] == 'enum' ||
        $field['field_type'] == 'multienum' ||
        $field['field_type'] == 'mlenum' ||
        $field['field_type'] == 'radio' ||
        $field['field_type'] == 'set' ||
        $field['field_type'] == 'sorter') {
        $field['field_params']['values'] = Ulaform::getStringlistArray($field['field_params']['values']);
    }
    if ($field['field_type'] == 'matrix') {
        $field['field_params']['cols'] = Ulaform::getStringlistArray($field['field_params']['cols']);
    }

    /* Setup the field with all the parameters. */
    $form->addVariable($field['field_label'], $field['field_name'], $field['field_type'], $field['field_required'], $field['field_readonly'], $field['field_desc'], $field['field_params']);
}

/* Check if submitted and validate. */
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    try {
        $submit = $ulaform_driver->submitForm($info);
        $notification->push(_("Form submitted successfully."), 'horde.success');
        $done = true;
    } catch (Horde_Exception $e) {
        $notification->push(sprintf(_("Error submitting form. %s."), $e->getMessage()), 'horde.error');
    }
}

/* Render active or inactive, depending if submitted or not. */
$render_type = ($done) ? 'renderInactive' : 'renderActive';

/* Set target URL, if passed as form url use that, otherwise use selfUrl(). */
$target_url = ($form_params['url']) ? $form_params['url'] : Horde::selfUrl();

/* Render the form. */
$renderer = new Horde_Form_Renderer();
$renderer->showHeader(false);
Horde::startBuffer();
$form->$render_type($renderer, $vars, $target_url, 'post', 'multipart/form-data');
$main = Horde::endBuffer();

$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
$view->noterror = !$error;
$view->title = isset($form_info['form_name']) ? $form_info['form_name'] : false;
$view->main = $main;

if (!isset($form_params['embed'])) {
    $form_params['embed'] = false;
}

switch ($form_params['embed']) {
case 'php':
    /* PHP style embedding, just fetch the form code. */
    $notification->notify(array('listeners' => 'status'));
    echo $view->render('display');
    break;

default:
    /* No special embedding, output with regular header/footer. */
    $page_output->header();
    $notification->notify(array('listeners' => 'status'));
    echo $view->render('display');
    $page_output->footer();
}
