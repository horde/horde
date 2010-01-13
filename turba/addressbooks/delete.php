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

require_once TURBA_BASE . '/lib/Forms/DeleteAddressBook.php';

// Exit if this isn't an authenticated user, or if there's no source
// configured for shares.
if (!Horde_Auth::getAuth() || empty($_SESSION['turba']['has_share'])) {
    require TURBA_BASE . '/'
        . ($browse_source_count ? basename($prefs->getValue('initial_page')) : 'search.php');
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$addressbook_id = $vars->get('a');
if ($addressbook_id == Horde_Auth::getAuth()) {
    $notification->push(_("This addressbook cannot be deleted"), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('addressbooks/', true));
    exit;
}

$addressbook = $turba_shares->getShare($addressbook_id);
if (is_a($addressbook, 'PEAR_Error')) {
    $notification->push($addressbook, 'horde.error');
    header('Location: ' . Horde::applicationUrl('addressbooks/', true));
    exit;
} elseif (!Horde_Auth::getAuth() ||
          $addressbook->get('owner') != Horde_Auth::getAuth()) {
    $notification->push(_("You are not allowed to delete this addressbook."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('addressbooks/', true));
    exit;
}

$form = new Turba_DeleteAddressBookForm($vars, $addressbook);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The addressbook \"%s\" has been deleted."), $addressbook->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('addressbooks/', true));
    exit;
}

$title = $form->getTitle();
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
