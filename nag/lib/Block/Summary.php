<?php
/**
 */
class Nag_Block_Summary extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Tasks Summary");
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
        $cManager = new Horde_Prefs_CategoryManager();
        $categories = array();
        foreach ($cManager->get() as $c) {
            $categories[$c] = $c;
        }
        $categories['unfiled'] = _("Unfiled");

        $tasklists = array();
        foreach (Nag::listTasklists() as $id => $tasklist) {
            if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
                !empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                continue;
            }
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
            'show_category' => array(
                'type' => 'checkbox',
                'name' => _("Show task category?"),
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
            ),
            'show_categories' => array(
                'type' => 'multienum',
                'name' => _("Show tasks from these categories"),
                'default' => array(),
                'values' => $categories
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
                $viewurl = Horde_Util::addParameter(
                    'view.php',
                    array('task' => $task->id,
                          'tasklist' => $task->tasklist));
                $link = Horde::link(
                    htmlspecialchars(Horde::url($viewurl, true)))
                    . (!empty($task->name)
                       ? htmlspecialchars($task->name) : _("[none]"))
                    . '</a>';
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
            $tasks = Nag::listTasks(
                null, null, null,
                isset($this->_params['show_tasklists']) ?
                    $this->_params['show_tasklists'] :
                    array_keys(Nag::listTasklists(false, Horde_Perms::READ)),
                empty($this->_params['show_completed']) ?
                        0 :
                        1
            );
        } catch (Nag_Exception $e) {
            return '<em>' . htmlspecialchars($e->getMessage()) . '</em>';
        }

        $tasks->reset();
        while ($task = $tasks->each()) {
            // Only print tasks due in the past if the show_overdue flag is
            // on. Only display selected categories (possibly unfiled).
            if (($task->due > 0 &&
                 $now > $task->due &&
                 empty($this->_params['show_overdue'])) ||
                (!empty($this->_params['show_categories']) &&
                 (!in_array($task->category, $this->_params['show_categories']) &&
                  !(empty($task->category) &&
                    in_array('unfiled', $this->_params['show_categories']))))) {
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
                $taskurl = Horde_Util::addParameter(
                    'task.php',
                    array('task' => $task->id,
                          'tasklist' => $task->tasklist,
                          'url' => Horde::selfUrl(true)));
                $label = sprintf(_("Edit \"%s\""), $task->name);
                $html .= '<td width="1%">'
                    . Horde::link(htmlspecialchars(Horde::url(Horde_Util::addParameter($taskurl, 'actionID', 'modify_task'), true)), $label)
                    . Horde::img('edit.png', $label)
                    . '</a></td>';
                if ($task->completed) {
                    $html .= '<td width="1%">'
                        . Horde::img('checked.png', _("Completed")) . '</td>';
                } else {
                    $label = sprintf(_("Complete \"%s\""), $task->name);
                    $html .= '<td width="1%">'
                        . Horde::link(htmlspecialchars(Horde::url(Horde_Util::addParameter($taskurl, 'actionID', 'complete_task'), true)), $label)
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

            $viewurl = Horde_Util::addParameter(
                'view.php',
                array('task' => $task->id,
                      'tasklist' => $task->tasklist));
            $html .= $task->treeIcons()
                . Horde::link(
                    htmlspecialchars(Horde::url($viewurl, true)),
                    $task->desc)
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

            if (!empty($this->_params['show_category'])) {
                $html .= '<td width="1%" class="category'
                    . md5($task->category) . '">'
                    . htmlspecialchars($task->category
                                       ? $task->category : _("Unfiled"))
                    . '</td>';
            }
            $html .= "</tr>\n";
        }

        if (empty($html)) {
            return '<em>' . _("No tasks to display") . '</em>';
        }

        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('categoryCSS.php');

        return '<table cellspacing="0" width="100%" class="linedRow">'
            . $html . '</table>';
    }

}
