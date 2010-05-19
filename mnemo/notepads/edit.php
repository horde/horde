<?php
/**
 * $Horde: mnemo/notepads/edit.php,v 1.7 2009/12/03 00:01:11 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('MNEMO_BASE', dirname(dirname(__FILE__)));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

require_once MNEMO_BASE . '/lib/Forms/EditNotepad.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $notepad = $mnemo_shares->getShare($vars->get('n'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}
if (!Horde_Auth::getAuth() ||
    $notepad->get('owner') != Horde_Auth::getAuth()) {

    $notification->push(_("You are not allowed to change this notepad."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}
$form = new Mnemo_EditNotepadForm($vars, $notepad);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $notepad->get('name');
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        if ($notepad->get('name') != $original_name) {
            $notification->push(sprintf(_("The notepad \"%s\" has been renamed to \"%s\"."), $original_name, $notepad->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The notepad \"%s\" has been saved."), $original_name), 'horde.success');
        }
    }

    header('Location: ' . Horde::applicationUrl('notepads/', true));
    exit;
}

$vars->set('name', $notepad->get('name'));
$vars->set('description', $notepad->get('desc'));
$title = $form->getTitle();
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
