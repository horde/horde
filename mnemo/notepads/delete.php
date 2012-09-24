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
    Horde::url('', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$notepad_id = $vars->get('n');
try {
    $notepad = $mnemo_shares->getShare($notepad_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e);
    Horde::url('', true)->redirect();
}
if ($notepad->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($notepad->get('owner')) || !$GLOBALS['registry']->isAdmin())) {
    $notification->push(_("You are not allowed to delete this notepad."), 'horde.error');
    Horde::url('', true)->redirect();
}

$form = new Mnemo_Form_DeleteNotepad($vars, $notepad);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The notepad \"%s\" has been deleted."), $notepad->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e);
    }
    Horde::url('', true)->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('notepads/delete.php'), 'post');
$page_output->footer();
