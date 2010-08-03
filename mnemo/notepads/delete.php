<?php
/**
 * $Horde: mnemo/notepads/delete.php,v 1.10 2009/12/03 00:01:11 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('MNEMO_BASE', dirname(dirname(__FILE__)));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

require_once MNEMO_BASE . '/lib/Forms/DeleteNotepad.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::applicationUrl('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$notepad_id = $vars->get('n');
if ($notepad_id == $GLOBALS['registry']->getAuth()) {
    $notification->push(_("This notepad cannot be deleted"), 'horde.warning');
    Horde::applicationUrl('notepads/', true)->redirect();
}
try {
    $notepad = $mnemo_shares->getShare($notepad_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::applicationUrl('notepads/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() || $notepad->get('owner') != $GLOBALS['registry']->getAuth()) {
    $notification->push(_("You are not allowed to delete this notepad."), 'horde.error');
    Horde::applicationUrl('notepads/', true)->redirect();
}

$form = new Mnemo_DeleteNotepadForm($vars, $notepad);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if ($result instanceof PEAR_Error) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The notepad \"%s\" has been deleted."), $notepad->get('name')), 'horde.success');
    }

    Horde::applicationUrl('notepads/', true)->redirect();
}

$title = $form->getTitle();
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
