<?php

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

$vars = Horde_Variables::getDefaultVariables();
$actionID = $vars->actionID;

/* Page variables. */
$title = _("My Tasks");

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
require $registry->get('templates', 'horde') . '/common-header-mobile.inc';
?>
<style type="text/css">
.ui-icon-nag-unchecked {
    background-image: none;
}
.overdue {
    color: #f00;
}
.closed {
    color: #aaa;
}
.closed a {
    text-decoration: line-through;
}
</style>
</head>

<body>
<div data-role="page">

<div data-role="header">
 <h1>My Tasks</h1>
 <a rel="external" href="<?php echo Horde::getServiceLink('portal', 'horde')?>"><?php echo _("Portal")?></a>
 <?php if (Horde::getServiceLink('logout')): ?>
 <a href="<?php echo Horde::getServiceLink('logout')->setRaw(false) ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
 <?php endif ?>
</div>
<div data-role="content">
 <ul data-role="listview">
<?php
if ($tasks->hasTasks()) {
    $sortby = $prefs->getValue('sortby');
    $sortdir = $prefs->getValue('sortdir');
    $dateFormat = $prefs->getValue('date_format');
    $columns = @unserialize($prefs->getValue('tasklist_columns'));
    if (empty($columns)) {
        $columns = array();
    }
    $dynamic_sort = true;

    $baseurl = 'list.php';
    if ($actionID == 'search_tasks') {
        $baseurl = Horde_Util::addParameter(
            $baseurl,
            array('actionID' => 'search_tasks',
                  'search_pattern' => $search_pattern,
                  'search_name' => $search_name ? 'on' : 'off',
                  'search_desc' => $search_desc ? 'on' : 'off',
                  'search_category' => $search_category ? 'on' : 'off'));
    }

    $tasks->reset();
    while ($task = $tasks->each()) {
        $dynamic_sort &= !$task->hasSubTasks();

        $style = '';
        if (!empty($task->completed)) {
            $style = 'closed';
        } elseif (!empty($task->due) && $task->due < time()) {
            $style = 'overdue';
        }
        if ($style) { $style = ' class="' . $style . '"'; }

        if ($task->tasklist == '**EXTERNAL**') {
            // Just use a new share that this user owns for tasks from
            // external calls - if the API gives them back, we'll trust it.
            $share = $GLOBALS['nag_shares']->newShare($GLOBALS['registry']->getAuth(), '**EXTERNAL**');
            $owner = $task->tasklist_name;
        } else {
            try {
                $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
                $owner = $share->get('name');
            } catch (Horde_Share_Exception $e) {
                $owner = $task->tasklist;
            }
        }

        $task_link = '#';
        if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            $task_link = $task->view_link;
        }

        if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            if (!$task->completed) {
                $icon = 'nag-unchecked';
                if (!$task->childrenCompleted()) {
                    $href = '#';
                    $label = _("Incomplete sub tasks, complete them first");
                } else {
                    $href = $task->complete_link;
                    $label = sprintf(_("Complete \"%s\""), $task->name);
                }
            } else {
                $icon = 'check';
                if ($task->parent && $task->parent->completed) {
                    $href = '#';
                    $label = _("Completed parent task, mark it as incomplete first");
                } else {
                    $href = $task->complete_link;
                    $label = sprintf(_("Mark \"%s\" as incomplete"), $task->name);
                }
            }
        } else {
            $href = '#';
            if ($task->completed) {
                $label = _("Completed");
                $icon = 'check';
            } else {
                $label = _("Not completed");
                $icon = 'nag-unchecked';
            }
        }

        echo '<li' . $style . '><a rel="external" href="' . $task_link . '">' . htmlspecialchars($task->name) . '</a><a rel="external" data-icon="' . $icon . '" href="' . $href . '">' . $label . '</a></li>';
    }
}
?>
 </ul>
</div>

</div>

<?php $registry->get('templates', 'horde') . '/common-footer-mobile.inc';