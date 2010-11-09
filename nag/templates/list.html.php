<?php

require NAG_TEMPLATES . '/list/header.inc';

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

    require NAG_TEMPLATES . '/list/task_headers.inc';

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

        require NAG_TEMPLATES . '/list/task_summaries.inc';
    }

    require NAG_TEMPLATES . '/list/task_footers.inc';

    if ($dynamic_sort) {
        Horde::addScriptFile('tables.js', 'horde');
    }
} else {
    require NAG_TEMPLATES . '/list/empty.inc';
}
