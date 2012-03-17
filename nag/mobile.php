<?php

require_once __DIR__ . '/lib/Application.php';
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

$injector->getInstance('Horde_PageOutput')->addScriptFile('mobile.js');
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
<script>
var NagConf = {
    completeUrl: '<?php echo Horde::url('t/complete?format=json') ?>',
    showCompleted: <?php echo $prefs->getValue('show_completed') ?>
};
</script>
</head>

<body>
<div data-role="page" id="nag-tasklist">

<div data-role="header">
 <h1>My Tasks</h1>
 <a rel="external" href="<?php echo Horde::getServiceLink('portal', 'horde')?>"><?php echo _("Applications")?></a>
 <?php if (Horde::getServiceLink('logout')): ?>
 <a href="<?php echo Horde::getServiceLink('logout')->setRaw(false) ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
 <?php endif ?>
</div>
<div data-role="content">
 <ul data-role="listview">
<?php
if ($tasks->hasTasks()) {
    $dateFormat = $prefs->getValue('date_format');
    $dynamic_sort = true;

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
        if ($style) { $style = ' class="' . $style . '"'; }

        if ($task->tasklist == '**EXTERNAL**') {
            // Just use a new share that this user owns for tasks from
            // external calls - if the API gives them back, we'll trust it.
            $share = $GLOBALS['nag_shares']->newShare($GLOBALS['registry']->getAuth(), '**EXTERNAL**', $task->tasklist_name);
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

        $task_complete_class = '';
        if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
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
        if ($task_complete_class) { $task_complete_class = ' class="' . $task_complete_class . '"'; }

        echo '<li' . $style . '><a data-rel="dialog" data-transition="slideup" href="' . str_replace('view.php', 'mobile-view.php', $task_link) . '"><h3>' . htmlspecialchars($task->name) . '</h3><p>' . htmlspecialchars(substr($task->desc, 0, 1000)) . '</p><p class="ui-li-aside' . ($overdue ? ' overdue' : '') . '"><strong>' . ($task->due ? strftime($dateFormat, $task->due) : '&nbsp;') . '</strong></p></a><a data-task="' . htmlspecialchars($task->id) . '" data-tasklist="' . htmlspecialchars($task->tasklist) . '" data-icon="' . $icon . '" href="#"' . $task_complete_class . '>' . $label . '</a></li>';
    }
}
?>
 </ul>
</div>

<?php require NAG_TEMPLATES . '/mobile-footer.html.php'; ?>

</div>

<?php $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
