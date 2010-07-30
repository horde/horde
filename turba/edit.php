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
$url = Horde_Util::getFormData('url', Horde::applicationUrl($prefs->getValue('initial_page'), true));

/* Edit the first of a list of contacts? */
if ($groupedit && (!$key || $key == '**search')) {
    if (!count($objectkeys)) {
        $notification->push(_("You must select at least one contact first."), 'horde.warning');
        header('Location: ' . $url);
        exit;
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
    header('Location: ' . $url);
    exit;
}

$driver = Turba_Driver::singleton($source);

/* Set the contact from the requested key. */
$contact = $driver->getObject($key);
if (is_a($contact, 'PEAR_Error')) {
    $notification->push($contact, 'horde.error');
    header('Location: ' . $url);
    exit;
}

/* Check permissions on this contact. */
if (!$contact->hasPermission(Horde_Perms::EDIT)) {
    if (!$contact->hasPermission(Horde_Perms::READ)) {
        $notification->push(_("You do not have permission to view this contact."), 'horde.error');
        header('Location: ' . Horde::applicationUrl($prefs->getValue('initial_page'), true));
        exit;
    } else {
        $notification->push(_("You only have permission to view this contact."), 'horde.error');
        header('Location: ' . $contact->url('Contact', true));
        exit;
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
if (!is_a($edited, 'PEAR_Error')) {
    $url = Horde_Util::getFormData('url');
    if (empty($url)) {
        $url = $contact->url('Contact', true);
    } else {
        $url = new Horde_Url($url, true);
    }
    header('Location: ' . $url->unique());
    exit;
}

$title = sprintf(_("Edit \"%s\""), $contact->getValue('name'));
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
$form->setTitle($title);
$form->renderActive(new Horde_Form_Renderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
