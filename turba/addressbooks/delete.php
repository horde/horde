<?php
/**
 * Turba addressbooks - delete.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('turba');

// Exit if this isn't an authenticated user, or if there's no source
// configured for shares.
if (!$GLOBALS['registry']->getAuth() || empty($_SESSION['turba']['has_share'])) {
    require TURBA_BASE . '/'
        . ($browse_source_count ? basename($prefs->getValue('initial_page')) : 'search.php');
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$addressbook_id = $vars->get('a');
if ($addressbook_id == $GLOBALS['registry']->getAuth()) {
    $notification->push(_("This addressbook cannot be deleted"), 'horde.warning');
    Horde::applicationUrl('addressbooks/', true)->redirect();
}

try {
    $addressbook = $turba_shares->getShare($addressbook_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::applicationUrl('addressbooks/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() ||
    $addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {

    $notification->push(_("You are not allowed to delete this addressbook."), 'horde.error');
    Horde::applicationUrl('addressbooks/', true)->redirect();
}

$form = new Turba_Form_DeleteAddressBook($vars, $addressbook);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The addressbook \"%s\" has been deleted."), $addressbook->get('name')), 'horde.success');
    }

    Horde::applicationUrl('addressbooks/', true)->redirect();
}

$title = $form->getTitle();
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, Horde::applicationUrl('delete.php'), 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
