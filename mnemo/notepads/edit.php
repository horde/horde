<?php
/**
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package @Mnemo
 */

@define('MNEMO_BASE', dirname(__DIR__));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url('', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $notepad = $mnemo_shares->getShare($vars->get('n'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e);
    Horde::url('', true)->redirect();
}
$owner = $notepad->get('owner') == $GLOBALS['registry']->getAuth() ||
    (is_null($notepad->get('owner')) && $GLOBALS['registry']->isAdmin());
if (!$owner &&
    !$notepad->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You are not allowed to see this notepad."), 'horde.error');
    Horde::url('', true)->redirect();
}
$form = new Mnemo_Form_EditNotepad($vars, $notepad);

// Execute if the form is valid.
if ($owner && $form->validate($vars)) {
    $original_name = $notepad->get('name');
    try {
        $form->execute();
        if ($notepad->get('name') != $original_name) {
            $notification->push(sprintf(_("The notepad \"%s\" has been renamed to \"%s\"."), $original_name, $notepad->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The notepad \"%s\" has been saved."), $original_name), 'horde.success');
        }
        Horde::url('', true)->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$vars->set('name', $notepad->get('name'));
$vars->set('description', $notepad->get('desc'));

$page_output->header(array(
    'title' => $form->getTitle()
));
$notification->notify();
if ($owner) {
    echo $form->renderActive($form->getRenderer(), $vars, Horde::url('notepads/edit.php'), 'post');
} else {
    echo $form->renderInactive($form->getRenderer(), $vars);
}
$page_output->footer();
