<?php

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

$vars = Horde_Variables::getDefaultVariables();
$actionID = $vars->actionID;

/* Page variables. */
$title = _("My Tasks");

/* Get the full, sorted task list. */
$tasks = Nag::listTasks($prefs->getValue('sortby'),
                        $prefs->getValue('sortdir'),
                        $prefs->getValue('altsortby'));
if (is_a($tasks, 'PEAR_Error')) {
    $notification->push($tasks, 'horde.error');
    $tasks = new Nag_Task();
}

?>
<!DOCTYPE html>
<html>
    <head>
    <title><?php echo htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
    <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
</head>
<body>

<div data-role="page">

<div data-role="header"><h1>My Tasks</h1></div>

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

        if (!empty($task->completed)) {
            $style = 'linedRow closed';
        } elseif (!empty($task->due) && $task->due < time()) {
            $style = 'linedRow overdue';
        } else {
            $style = 'linedRow';
        }

        if ($task->tasklist == '**EXTERNAL**') {
            // Just use a new share that this user owns for tasks from
            // external calls - if the API gives them back, we'll trust it.
            $share = $GLOBALS['nag_shares']->newShare('**EXTERNAL**');
            $owner = $task->tasklist_name;
        } else {
            try {
                $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
                $owner = $share->get('name');
            } catch (Horde_Share_Exception $e) {
                $owner = $task->tasklist;
            }
        }

        echo '<li><a href="#">' . htmlspecialchars($task->name) . '</a></li>';
    }
}
?>
 </ul>
</div>

<div data-role="footer"></div>

</div>

</body>
</html>
