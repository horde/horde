<?php
/**
 * Turba edit.php.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$listView = null;
$vars = Horde_Variables::getDefaultVariables();
$source = $vars->get('source');
$original_source = $vars->get('original_source');
$key = $vars->get('key');
$groupedit = $vars->get('actionID') == 'groupedit';
$objectkeys = $vars->get('objectkeys');
$url = new Horde_Url(Horde_Util::getFormData('url', Horde::applicationUrl($prefs->getValue('initial_page'), true)));

/* Edit the first of a list of contacts? */
if ($groupedit && (!$key || $key == '**search')) {
    if (!count($objectkeys)) {
        $notification->push(_("You must select at least one contact first."), 'horde.warning');
        $url->redirect();
    }
    if ($key == '**search') {
        $original_source = $key;
    }
    list($source, $key) = explode(':', $objectkeys[0], 2);
    if (empty($original_source)) {
        $original_source = $source;
    }
    $vars->set('key', $key);
    $vars->set('source', $source);
    $vars->set('original_source', $original_source);
}

if ($source === null || !isset($cfgSources[$source])) {
    $notification->push(_("Not found"), 'horde.error');
    $url->redirect();
}

$driver = $injector->getInstance('Turba_Driver')->getDriver($source);

/* Set the contact from the requested key. */
try {
    $contact = $driver->getObject($key);
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    $url->redirect();
}

/* Check permissions on this contact. */
if (!$contact->hasPermission(Horde_Perms::EDIT)) {
    if (!$contact->hasPermission(Horde_Perms::READ)) {
        $notification->push(_("You do not have permission to view this contact."), 'horde.error');
        $url->redirect();
    } else {
        $notification->push(_("You only have permission to view this contact."), 'horde.error');
        $contact->url('Contact', true)->redirect();
    }
}

/* Create the edit form. */
if ($groupedit) {
    $form = new Turba_Form_EditContactGroup($vars, $contact);
} else {
    $form = new Turba_Form_EditContact($vars, $contact);
}

/* Execute() checks validation first. */
$edited = $form->execute();
if (!($edited instanceof PEAR_Error)) {
    $url = Horde_Util::getFormData('url');
    if (empty($url)) {
        $url = $contact->url('Contact', true);
    } else {
        $url = new Horde_Url($url, true);
    }
    $url->unique()->redirect();
}

$title = sprintf(_("Edit \"%s\""), $contact->getValue('name'));
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
$form->setTitle($title);
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::applicationUrl('edit.php'), 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
