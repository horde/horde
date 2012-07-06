<?php
/**
 * Nag smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$vars = Horde_Variables::getDefaultVariables();
$view = new Horde_View(array('templatePath' => NAG_TEMPLATES . '/smartmobile'));
new Horde_View_Helper_Text($view);

$view->portal = $registry->getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = $registry->getServiceLink('logout')->setRaw(false);

/* Get the full, sorted task list. */
try {
    $tasks = Nag::listTasks(
        $prefs->getValue('sortby'),
        $prefs->getValue('sortdir'),
        $prefs->getValue('altsortby')
     );
} catch (Nag_Exception $e) {
    $notification->push($tasks, 'horde.error');
    $tasks = new Nag_Task();
}

if ($tasks->hasTasks()) {
    $auth = $registry->getAuth();
    $dateFormat = $prefs->getValue('date_format');
    $dynamic_sort = true;
    $li = array();

    $tasks->reset();
    while ($task = $tasks->each()) {
        $dynamic_sort &= !$task->hasSubTasks();

        $style = '';
        $overdue = false;

        if (!empty($task->completed)) {
            $style = 'closed';
        } elseif (!empty($task->due) && $task->due < time()) {
            $style = 'overdue';
            $overdue = true;
        }

        if ($task->tasklist == '**EXTERNAL**') {
            // Just use a new share that this user owns for tasks from
            // external calls - if the API gives them back, we'll trust it.
            $share = $nag_shares->newShare($auth, '**EXTERNAL**', $task->tasklist_name);
            $owner = $task->tasklist_name;
        } else {
            try {
                $share = $nag_shares->getShare($task->tasklist);
                $owner = $share->get('name');
            } catch (Horde_Share_Exception $e) {
                $owner = $task->tasklist;
            }
        }

        $task_link = $share->hasPermission($auth, Horde_Perms::READ)
            ? $task->view_link
            : '#';

        $task_complete_class = '';
        if ($share->hasPermission($auth, Horde_Perms::EDIT)) {
            if (!$task->completed) {
                $icon = 'nag-unchecked';
                if (!$task->childrenCompleted()) {
                    $label = _("Incomplete sub tasks, complete them first");
                } else {
                    $task_complete_class = 'toggleable incomplete';
                    $label = _("Complete");
                }
            } else {
                $icon = 'check';
                if ($task->parent && $task->parent->completed) {
                    $label = _("Completed parent task, mark it as incomplete first");
                } else {
                    $task_complete_class = 'toggleable complete';
                    $label = _("Mark incomplete");
                }
            }
        } else {
            if ($task->completed) {
                $label = _("Completed");
                $icon = 'check';
            } else {
                $label = _("Not completed");
                $icon = 'nag-unchecked';
            }
        }

        $li[] = array(
            'desc' => substr($task->desc, 0, 1000),
            'due' => ($task->due ? strftime($dateFormat, $task->due) : '&nbsp;'),
            'href' => str_replace('view.php', 'smartmobile-view.php', $task_link),
            'icon' => $icon,
            'id' => $task->id,
            'label' => $label,
            'name' => $task->name,
            'overdue' => $overdue,
            'style' => $style,
            'tasklist' => $tasklist,
            'tcc' => $task_complete_class
        );
    }

    $view->li = $li;
}

/* Task creation page. */
$max_tasks = $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_tasks');
if (($max_tasks === true) || ($max_tasks > Nag::countTasks())) {
    $task_vars = clone $vars;
    if (!$vars->exists('tasklist_id')) {
        $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
    }
    $vars->mobile = true;
    $vars->url = Horde::url('smartmobile.php');

    $view->create_form = new Nag_Form_Task($vars, _("New Task"), $mobile = true);
    $view->create_title = $view->create_form->getTitle();
}

$page_output->addScriptFile('smartmobile.js');
$page_output->addInlineJsVars(array(
    'var NagConf' => array(
        'completeUrl' => strval(Horde::url('t/complete?format=json')),
        'showCompleted' => $prefs->getValue('show_completed')
    )
), array('top' => true));

$page_output->header(array(
    'title' => _("My Tasks"),
    'view' => $registry::VIEW_SMARTMOBILE
));

echo $view->render('main');
if ($view->create_form) {
    echo $view->render('create');
}

$page_output->footer();
