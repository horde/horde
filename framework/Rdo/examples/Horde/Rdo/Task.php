<?php
/**
 * @package Horde_Rdo
 */

@include './conf.php';
if (empty($conf)) {
    die('No configuration found.');
}

require_once 'Horde/Autoloader.php';

/**
 */
class Task extends Horde_Rdo_Base
{
}

/**
 */
class TaskMapper extends Horde_Rdo_Mapper
{
    protected $_table = 'nag_tasks';
}

$tm = new TaskMapper($conf['adapter']);

// Count all tasks.
$count = $tm->count();
echo "# tasks: $count\n";

// List all tasks.
echo "Looking for all tasks:\n";
foreach ($tm->find(Horde_Rdo::FIND_ALL) as $task) {
    echo "  " . $task->task_name . "\n";
}

// List all of Chuck's tasks.
$chuck = $tm->find(Horde_Rdo::FIND_ALL, array('task_owner' => 'chuck'));
echo "\nChuck's tasks:\n";
foreach ($chuck as $task) {
    echo "  " . $task->task_name . "\n";
}
