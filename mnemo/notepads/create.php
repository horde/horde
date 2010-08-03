<?php
/**
 * $Horde: mnemo/notepads/create.php,v 1.5 2009/07/08 18:29:43 slusarz Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('MNEMO_BASE', dirname(dirname(__FILE__)));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

require_once MNEMO_BASE . '/lib/Forms/CreateNotepad.php';

// Exit if this isn't an authenticated user or if the user can't
// create new notepads (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_notepad')) {
    Horde::applicationUrl('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Mnemo_CreateNotepadForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(sprintf(_("The notepad \"%s\" has been created."), $vars->get('name')), 'horde.success');
    }

    Horde::applicationUrl('notepads/', true)->redirect();
}

$title = $form->getTitle();
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
$notification->notify();
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
