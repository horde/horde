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
    Horde::url('', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $addressbook = $injector->getInstance('Turba_Shares')->getShare($vars->get('a'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e);
    Horde::url('', true)->redirect();
}
$owner = $addressbook->get('owner') == $GLOBALS['registry']->getAuth() ||
    (is_null($addressbook->get('owner')) && $GLOBALS['registry']->isAdmin());
if (!$owner &&
    !$addressbook->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You are not allowed to see this addressbook."), 'horde.error');
    Horde::url('', true)->redirect();
}

$form = new Turba_Form_EditAddressBook($vars, $addressbook);

// Execute if the form is valid.
if ($owner && $form->validate($vars)) {
    $original_name = $addressbook->get('name');
    try {
        $form->execute();
        if ($addressbook->get('name') != $original_name) {
            $notification->push(sprintf(_("The addressbook \"%s\" has been renamed to \"%s\"."), $original_name, $addressbook->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The addressbook \"%s\" has been saved."), $original_name), 'horde.success');
        }
        Horde::url('', true)->redirect();
    } catch (Turba_Exception $e) {
        $notification->push($e);
    }
}

$vars->set('name', $addressbook->get('name'));
$vars->set('description', $addressbook->get('desc'));

$page_output->header(array(
    'title' => $form->getTitle()
));
$notification->notify(array('listeners' => 'status'));
if ($owner) {
    echo $form->renderActive($form->getRenderer(), $vars, Horde::url('addressbooks/edit.php'), 'post');
} else {
    echo $form->renderInactive($form->getRenderer(), $vars);
}
$page_output->footer();
