<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('nag');

require_once NAG_BASE . '/lib/Forms/CreateTaskList.php';

// Exit if this isn't an authenticated user or if the user can't
// create new task lists (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_tasklist')) {
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Nag_CreateTaskListForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The task list \"%s\" has been created."), $vars->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    Horde::url('tasklists/', true)->redirect();
}

$title = $form->getTitle();
require NAG_TEMPLATES . '/common-header.inc';
echo Horde::menu();
Nag::status();
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
