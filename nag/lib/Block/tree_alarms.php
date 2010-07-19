<?php

$block_name = _("Menu Alarms");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_nag_tree_alarms extends Horde_Block
{
    protected$_app = 'nag';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');

        // Get any alarms in the next hour.
        $now = time();
        $alarms = Nag::listAlarms($now);
        if (is_a($alarms, 'PEAR_Error')) {
            return;
        }

        $alarmCount = 0;
        foreach ($alarms as $taskId => $task) {
            if ($horde_alarm->isSnoozed($task->uid, $GLOBALS['registry']->getAuth())) {
                continue;
            }
            $alarmCount++;
            $differential = $task->due - $now;
            if ($differential >= 60) {
                $title = sprintf(_("%s is due in %s"), $task->name, Nag::secondsToString($differential));
            } else {
                $title = sprintf(_("%s is due now."), $task->name);
            }

            $url = Horde_Util::addParameter(Horde::applicationUrl('view.php'),
                                      array('task' => $task->id,
                                            'tasklist' => $task->tasklist));
            $tree->addNode($parent . $taskId,
                           $parent,
                           $task->name,
                           $indent + 1,
                           false,
                           array('icon' => 'alarm.png',
                                 'icondir' => (string)Horde_Themes::img(),
                                 'title' => $title,
                                 'url' => $url));
        }

        if ($GLOBALS['registry']->get('url', $parent)) {
            $purl = $GLOBALS['registry']->get('url', $parent);
        } elseif ($GLOBALS['registry']->get('status', $parent) == 'heading' ||
                  !$GLOBALS['registry']->get('webroot')) {
            $purl = null;
        } else {
            $purl = Horde::url($GLOBALS['registry']->getInitialPage($parent));
        }
        $pnode_params = array('url' => $purl,
                              'icon' => (string)$GLOBALS['registry']->get('icon', $parent),
                              'icondir' => '');

        $pnode_params = array('url' => $purl,
                              'icon' => (string)$GLOBALS['registry']->get('icon', $parent),
                              'icondir' => '');
        $pnode_name = $GLOBALS['registry']->get('name', $parent);
        if ($alarmCount) {
            $pnode_name = '<strong>' . $pnode_name . '</strong>';
        }

        $tree->addNode($parent,
                       $GLOBALS['registry']->get('menu_parent', $parent),
                       $pnode_name,
                       $indent,
                       false,
                       $pnode_params);
    }

}
