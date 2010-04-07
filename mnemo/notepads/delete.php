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
require_once MNEMO_BASE . '/lib/base.php';
require_once MNEMO_BASE . '/lib/Forms/DeleteNotepad.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$notepad_id = $vars->get('n');
if ($notepad_id == Horde_Auth::getAuth()) {
    $notification->push(_("This notepad cannot be deleted"), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}

$notepad = $mnemo_shares->getShare($notepad_id);
if (is_a($notepad, 'PEAR_Error')) {
    $notification->push($notepad, 'horde.error');
    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
} elseif (!Horde_Auth::getAuth() ||
          $notepad->get('owner') != Horde_Auth::getAuth()) {
    $notification->push(_("You are not allowed to delete this notepad."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}

$form = new Mnemo_DeleteNotepadForm($vars, $notepad);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The notepad \"%s\" has been deleted."), $notepad->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}

$title = $form->getTitle();
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
