<?php
/**
 * Turba edit.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$listView = null;
$vars = Horde_Variables::getDefaultVariables();
$source = $vars->source;
$key = $vars->key;
$groupedit = ($vars->actionID == 'groupedit');
$url = new Horde_Url($vars->get('url', Horde::url($prefs->getValue('initial_page'), true)));

/* Edit the first of a list of contacts? */
if ($groupedit && (!$key || $key == '**search')) {
    if (!count($vars->objectkeys)) {
        $notification->push(_("You must select at least one contact first."), 'horde.warning');
        $url->redirect();
    }

    $original_source = ($key == '**search')
        ? $key
        : $vars->original_source;
    list($source, $key) = explode(':', $vars->objectkeys[0], 2);
    if (empty($original_source)) {
        $original_source = $source;
    }
    $vars->set('key', $key);
    $vars->set('source', $source);
    $vars->set('original_source', $original_source);
}

if (is_null($source) || !isset($cfgSources[$source])) {
    $notification->push(_("Not found"), 'horde.error');
    $url->redirect();
}

$driver = $injector->getInstance('Turba_Factory_Driver')->create($source);

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
$form = $groupedit
    ? new Turba_Form_EditContactGroup($vars, $contact)
    : new Turba_Form_EditContact($vars, $contact);

/* Execute() checks validation first. */
try {
    $edited = $form->execute();
    $url = isset($vars->url)
        ? new Horde_Url($url, true)
        : $contact->url('Contact', true);
    $url->unique()->redirect();
} catch (Turba_Exception $e) {}

$title = sprintf($contact->isGroup() ? _("Edit Group \"%s\"") : _("Edit \"%s\""), $contact->getValue('name'));
Horde::startBuffer();
require TURBA_TEMPLATES . '/menu.inc';
$form->setTitle($title);
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('edit.php'), 'post');
$formHtml = Horde::endBuffer();

$page_output->header(array(
    'title' => $title
));
echo $formHtml;
$page_output->footer();
