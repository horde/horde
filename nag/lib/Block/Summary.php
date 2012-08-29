<?php
/**
 */
class Nag_Block_Summary extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Tasks Summary");
    }

    /**
     */
    protected function _title()
    {
        global $registry;

        $label = !empty($this->_params['block_title'])
            ? $this->_params['block_title']
            : $registry->get('name');

        return Horde::url($registry->getInitialPage(), true)->link()
            . htmlspecialchars($label) . '</a>';
    }

    /**
     */
    protected function _params()
    {
        $tasklists = array();
        foreach (Nag::listTasklists() as $id => $tasklist) {
            $tasklists[$id] = $tasklist->get('name');
        }

        return array(
            'block_title' => array(
                'type' => 'text',
                'name' => _("Block title"),
                'default' => $GLOBALS['registry']->get('name')
            ),
            'show_pri' => array(
                'type' => 'checkbox',
                'name' => _("Show priorities?"),
                'default' => 1
            ),
            'show_actions' => array(
                'type' => 'checkbox',
                'name' => _("Show action buttons?"),
                'default' => 1
            ),
            'show_due' => array(
                'type' => 'checkbox',
                'name' => _("Show due dates?"),
                'default' => 1
            ),
            'show_tasklist' => array(
                'type' => 'checkbox',
                'name' => _("Show tasklist name?"),
                'default' => 1
            ),
            'show_alarms' => array(
                'type' => 'checkbox',
                'name' => _("Show task alarms?"),
                'default' => 1
            ),
            'show_overdue' => array(
                'type' => 'checkbox',
                'name' => _("Always show overdue tasks?"),
                'default' => 1
            ),
            'show_completed' => array(
                'type' => 'checkbox',
                'name' => _("Always show completed and future tasks?"),
                'default' => 1
            ),
            'show_tasklists' => array(
                'type' => 'multienum',
                'name' => _("Show tasks from these tasklists"),
                'default' => array($GLOBALS['registry']->getAuth()),
                'values' => $tasklists
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $registry, $prefs;

        $now = time();
        $html = '';

        if (!empty($this->_params['show_alarms'])) {
            $messages = array();
            try {
                $alarmList = Nag::listAlarms($now);
            } catch (Nag_Exception $e) {
                return '<em>' . htmlspecialchars($e->getMessage())
                    . '</em>';
            }
            foreach ($alarmList as $task) {
                $differential = $task->due - $now;
                $key = $differential;
                while (isset($messages[$key])) {
                    $key++;
                }
                $viewurl = Horde::url('view.php', true)->add(array(
                    'task' => $task->id,
                    'tasklist' => $task->tasklist
                ));
                $link = $viewurl->link() .
                    (!empty($task->name) ? htmlspecialchars($task->name) : _("[none]")) .
                    '</a>';
                if ($differential >= -60 && $differential < 60) {
                    $messages[$key] = sprintf(_("%s is due now."), $link);
                } elseif ($differential >= 60) {
                    $messages[$key] = sprintf(
                        _("%s is due in %s"),
                        $link, Nag::secondsToString($differential));
                }
            }

            ksort($messages);
            foreach ($messages as $message) {
                $html .= '<tr><td class="control">'
                    . Horde::img('alarm_small.png') . '&nbsp;&nbsp;<strong>'
                    . $message . '</strong></td></tr>';
            }

            if (!empty($messages)) {
                $html .= '</table><br /><table cellspacing="0" width="100%" class="linedRow">';
            }
        }

        $i = 0;
        try {
            $tasks = Nag::listTasks(array(
                'tasklists' => isset($this->_params['show_tasklists'])
                    ? $this->_params['show_tasklists']
                    : array_keys(Nag::listTasklists(false, Horde_Perms::READ)),
                'completed' => empty($this->_params['show_completed'])
                    ? Nag::VIEW_INCOMPLETE
                    : Nag::VIEW_ALL,
                'include_tags' => true)
            );
        } catch (Nag_Exception $e) {
            return '<em>' . htmlspecialchars($e->getMessage()) . '</em>';
        }

        $tasks->reset();
        while ($task = $tasks->each()) {
            // Only print tasks due in the past if the show_overdue flag is on.
            if (($task->due > 0 &&
                 $now > $task->due &&
                 empty($this->_params['show_overdue']))) {
                continue;
            }

            if ($task->completed) {
                $style = 'closed';
            } elseif (!empty($task->due) && $task->due < $now) {
                $style = 'overdue';
            } else {
                $style = '';
            }

            $html .= '<tr class="' . $style . '">';

            if (!empty($this->_params['show_actions'])) {
                $taskurl = Horde::url('task.php', true)->add(array(
                    'task' => $task->id,
                    'tasklist' => $task->tasklist,
                    'url' => Horde::selfUrl(true)
                ));
                $label = sprintf(_("Edit \"%s\""), $task->name);
                $html .= '<td width="1%">'
                    . $taskurl->copy()->add('actionID', 'modify_task')->link()
                    . Horde::img('edit.png', $label)
                    . '</a></td>';
                if ($task->completed) {
                    $html .= '<td width="1%">'
                        . Horde::img('checked.png', _("Completed")) . '</td>';
                } else {
                    $label = sprintf(_("Complete \"%s\""), $task->name);
                    $html .= '<td width="1%">'
                        . Horde::url('t/complete')->add(array('task' => $task->id, 'tasklist' => $task->tasklist, 'url' => Horde::selfUrl(true)))->link()
                        . Horde::img('unchecked.png', $label) . '</a></td>';
                }
            }

            if (!empty($this->_params['show_pri'])) {
                $html .= '<td align="center">&nbsp;'
                    . Nag::formatPriority($task->priority) . '&nbsp;</td>';
            }

            if (!empty($this->_params['show_tasklist'])) {
                $owner = $task->tasklist;
                $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
                $share = $shares->getShare($owner);
                $owner = $share->get('name');
                $html .= '<td width="1%" class="nowrap">'
                    . htmlspecialchars($owner) . '&nbsp;</td>';
            }

            $html .= '<td>';

            $viewurl = Horde::url('view.php', true)->add(array(
                'task' => $task->id,
                'tasklist' => $task->tasklist
            ));
            $html .= $task->treeIcons()
                . $viewurl->link(array('title' => $task->desc))
                . (!empty($task->name)
                   ? htmlspecialchars($task->name) : _("[none]"))
                . '</a>';

            if ($task->due > 0 &&
                empty($task->completed) &&
                !empty($this->_params['show_due'])) {
                $html .= ' ('
                    . strftime($prefs->getValue('date_format'), $task->due)
                    . ')';
            }

            $html .= '</td>';
            $html .= "</tr>\n";
        }

        if (empty($html)) {
            return '<em>' . _("No tasks to display") . '</em>';
        }

        return '<table cellspacing="0" width="100%" class="linedRow">'
            . $html . '</table>';
    }

}
