<?php
/**
 * Turba addressbooks - delete.
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
$addressbook_id = $vars->get('a');
if ($addressbook_id == $GLOBALS['registry']->getAuth()) {
    $notification->push(_("This address book cannot be deleted"), 'horde.warning');
    Horde::url('addressbooks/', true)->redirect();
}

try {
    $addressbook = $injector->getInstance('Turba_Shares')->getShare($addressbook_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('addressbooks/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() ||
    $addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {

    $notification->push(_("You are not allowed to delete this addressbook."), 'horde.error');
    Horde::url('addressbooks/', true)->redirect();
}

$form = new Turba_Form_DeleteAddressBook($vars, $addressbook);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The addressbook \"%s\" has been deleted."), $addressbook->get('name')), 'horde.success');
    } catch (Turba_Exception $e) {
        $notification->push($result, 'horde.error');
    }

    Horde::url('addressbooks/', true)->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
require TURBA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('addressbooks/delete.php'), 'post');
$page_output->footer();
