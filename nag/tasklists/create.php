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
if (!Horde_Auth::getAuth() || $prefs->isLocked('default_tasklist')) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Nag_CreateTaskListForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(sprintf(_("The task list \"%s\" has been created."), $vars->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}

$title = $form->getTitle();
require NAG_TEMPLATES . '/common-header.inc';
require NAG_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
