<?php
/**
 * The Ulaform script to display a form.
 *
 * $Horde: ulaform/display.php,v 1.45 2010-02-03 10:06:46 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */

define('AUTH_HANDLER', true);
require_once dirname(__FILE__) . '/lib/base.php';

$vars = Horde_Variables::getDefaultVariables();
$form_id = $vars->get('form_id');
$form_params = $vars->get('form_params');

/* Get the stored form information from the backend. */
$form_info = $ulaform_driver->getForm($form_id, Horde_Perms::READ);
if (!empty($form_info['form_params']['language'])) {
    Horde_Nls::setLanguageEnvironment($form_info['form_params']['language']);
}
if (is_a($form_info, 'PEAR_Error')) {
    $notification->push(sprintf(_("Could not fetch form ID \"%s\". %s"), $form_id, $form_info->getMessage()), 'horde.error');
}

$done = false;

/* Add form variables. */
$form = new Horde_Form($vars);
$form->addHidden('', 'form_id', 'int', false);

$fields = $ulaform_driver->getFields($form_id);
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
    $submit = $ulaform_driver->submitForm($info);
    if (is_a($submit, 'PEAR_Error')) {
        Horde::logMessage($submit, __FILE__, __LINE__, PEAR_LOG_ERR);
        $notification->push(sprintf(_("Error submitting form. %s."), $submit->getMessage()), 'horde.error');
    } else {
        $notification->push(_("Form submitted successfully."), 'horde.success');
        $done = true;
    }
}

/* Render active or inactive, depending if submitted or not. */
$render_type = ($done) ? 'renderInactive' : 'renderActive';

/* Set target URL, if passed as form url use that, otherwise use selfUrl(). */
$target_url = ($form_params['url']) ? $form_params['url'] : Horde::selfUrl();

/* Render the form. */
$renderer = new Horde_Form_Renderer();
$renderer->showHeader(false);
$main = Horde_Util::bufferOutput(array($form, $render_type), $renderer, $vars, $target_url, 'post', 'multipart/form-data');

$template->set('title', $form_info['form_name']);
$template->set('main', $main);
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

if (!isset($form_params['embed'])) {
    $form_params['embed'] = false;
}

switch ($form_params['embed']) {
case 'php':
    /* PHP style embedding, just fetch the form code. */
    echo $template->fetch(ULAFORM_TEMPLATES . '/display/display.html');
    break;

default:
    /* No special embedding, output with regular header/footer. */
    require ULAFORM_TEMPLATES . '/common-header.inc';
    echo $template->fetch(ULAFORM_TEMPLATES . '/display/display.html');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
