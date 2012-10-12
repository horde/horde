<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('nag');

// Exit if this isn't an authenticated user or if the user can't
// create new task lists (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_tasklist')) {
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Nag_Form_CreateTaskList($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $tasklist = $form->execute();
        $notification->push(sprintf(_("The task list \"%s\" has been created."), $vars->get('name')), 'horde.success');
        Horde::url('tasklists/edit.php')
            ->add('t', $tasklist->getName())
            ->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$page_output->header(array(
    'title' => $form->getTitle()
));
Nag::status();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('tasklists/create.php'), 'post');
$page_output->footer();
