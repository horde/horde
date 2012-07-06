<?php
/**
 * Turba addressbooks - edit.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('turba');

// Exit if this isn't an authenticated user, or if there's no source
// configured for shares.
if (!$GLOBALS['registry']->getAuth() || !$session->get('turba', 'has_share')) {
    require TURBA_BASE . '/'
        . ($browse_source_count ? basename($prefs->getValue('initial_page')) : 'search.php');
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $addressbook = $injector->getInstance('Turba_Shares')->getShare($vars->get('a'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::url('addressbooks/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() ||
    $addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {

    $notification->push(_("You are not allowed to change this addressbook."), 'horde.error');
    Horde::url('addressbooks/', true)->redirect();
}
$form = new Turba_Form_EditAddressBook($vars, $addressbook);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $addressbook->get('name');
    try {
        $result = $form->execute();
        if ($addressbook->get('name') != $original_name) {
            $notification->push(sprintf(_("The addressbook \"%s\" has been renamed to \"%s\"."), $original_name, $addressbook->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The addressbook \"%s\" has been saved."), $original_name), 'horde.success');
        }
    } catch (Turba_Exception $e) {
        $notification->push($result, 'horde.error');
    }

    Horde::url('addressbooks/', true)->redirect();
}

$vars->set('name', $addressbook->get('name'));
$vars->set('description', $addressbook->get('desc'));

$page_output->header(array(
    'title' => $form->getTitle()
));
require TURBA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('addressbooks/edit.php'), 'post');
$page_output->footer();
