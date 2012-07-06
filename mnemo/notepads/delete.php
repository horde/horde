<?php
/**
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
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$notepad_id = $vars->get('n');
try {
    $notepad = $mnemo_shares->getShare($notepad_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::url('notepads/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() || $notepad->get('owner') != $GLOBALS['registry']->getAuth()) {
    $notification->push(_("You are not allowed to delete this notepad."), 'horde.error');
    Horde::url('notepads/', true)->redirect();
}

$form = new Mnemo_Form_DeleteNotepad($vars, $notepad);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $result = $form->execute();
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    if ($result) {
        $notification->push(sprintf(_("The notepad \"%s\" has been deleted."), $notepad->get('name')), 'horde.success');
    }

    Horde::url('notepads/', true)->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
echo Horde::menu();
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('notepads/delete.php'), 'post');
$page_output->footer();
