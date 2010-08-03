<?php
/**
 * Turba addressbooks - edit.
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
try {
    $addressbook = $turba_shares->getShare($vars->get('a'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::applicationUrl('addressbooks/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() ||
    $addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {

    $notification->push(_("You are not allowed to change this addressbook."), 'horde.error');
    Horde::applicationUrl('addressbooks/', true)->redirect();
}
$form = new Turba_Form_EditAddressBook($vars, $addressbook);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $addressbook->get('name');
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        if ($addressbook->get('name') != $original_name) {
            $notification->push(sprintf(_("The addressbook \"%s\" has been renamed to \"%s\"."), $original_name, $addressbook->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The addressbook \"%s\" has been saved."), $original_name), 'horde.success');
        }
    }

    Horde::applicationUrl('addressbooks/', true)->redirect();
}

$vars->set('name', $addressbook->get('name'));
$vars->set('description', $addressbook->get('desc'));
$title = $form->getTitle();
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
