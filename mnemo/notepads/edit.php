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

@define('MNEMO_BASE', dirname(dirname(__FILE__)));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $notepad = $mnemo_shares->getShare($vars->get('n'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::url('notepads/', true)->redirect();
}
if (!$GLOBALS['registry']->getAuth() ||
    $notepad->get('owner') != $GLOBALS['registry']->getAuth()) {

    $notification->push(_("You are not allowed to change this notepad."), 'horde.error');
    Horde::url('notepads/', true)->redirect();
}
$form = new Mnemo_Form_EditNotepad($vars, $notepad);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $notepad->get('name');
    try {
        $result = $form->execute();
        if ($notepad->get('name') != $original_name) {
            $notification->push(sprintf(_("The notepad \"%s\" has been renamed to \"%s\"."), $original_name, $notepad->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The notepad \"%s\" has been saved."), $original_name), 'horde.success');
        }
    } catch (Exception $e) {
        $notification->push($result, 'horde.error');
    }
    Horde::url('notepads/', true)->redirect();
}

$vars->set('name', $notepad->get('name'));
$vars->set('description', $notepad->get('desc'));
$title = $form->getTitle();
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('notepads/edit.php'), 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
