<tr class="<?php echo $style ?>" style="background-color:<?php echo $task->backgroundColor() ?>;color:<?php echo $task->foregroundColor() ?>">
  <td>
    <?php
    if ($have_edit) {
        if (!$task->completed) {
            if (!$task->childrenCompleted()) {
                $label = _("Incomplete sub tasks, complete them first");
                echo Horde::img('unchecked.png', $label, array('title' => $label));
            } else {
                $label = sprintf(_("Complete \"%s\""), $task->name);
                echo Horde::link($task->complete_link, $label) . Horde::img('unchecked.png', $label) . '</a>';
            }
        } else {
            if ($task->parent && $task->parent->completed) {
                $label = _("Completed parent task, mark it as incomplete first");
                echo Horde::img('checked.png', $label, array('title' => $label));
            } else {
                $label = sprintf(_("Mark \"%s\" as incomplete"), $task->name);
                echo Horde::link($task->complete_link, $label) . Horde::img('checked.png', $label) . '</a>';
            }
        }
    } else {
        echo Nag::formatCompletion($task->completed);
    }
    ?>
  </td>

<?php if (in_array('priority', $columns)): ?>
  <td><?php echo Nag::formatPriority($task->priority) ?></td>
<?php endif; if (in_array('tasklist', $columns)): ?>
  <td><?php echo htmlspecialchars($owner) ?></td>
<?php endif; ?>
  <td>
    <?php
    if ($have_edit &&
        (!$task->private || $task->owner == $GLOBALS['registry']->getAuth())) {
        $label = sprintf(_("Edit \"%s\""), $task->name);
        $params = array('have_search' => $this->haveSearch, 'tab_name' => $this->tab_name, 'url' => Horde::selfUrl(true));
        if ($this->smartShare) {
          $params['list'] = $this->smartShare->getName();
        }
        echo Horde::link($task->edit_link->add($params), $label) . Horde::img('edit-sidebar-' . substr($task->foregroundColor(), 1) . '.png', $label) . '</a>';
    }
    ?>
  </td>
  <td>
    <?php
    echo $task->treeIcons();
    $task_name = strlen($task->name)
        ? htmlspecialchars($task->name)
        : _("[none]");
    if ($have_read) {
        $params = array('have_search' => (int)$this->haveSearch, 'tab_name' => $this->tab_name, 'url' => Horde::selfUrl(true));
        if ($this->smartShare) {
          $params['list'] = $this->smartShare->getName();
        }
        echo Horde::linkTooltip($task->view_link->add($params), '', '', '', '', $task->desc, '', array('style' => 'color:' . $task->foregroundColor()))
            . $task_name . '</a>';
    } else {
        echo $task_name;
    }?>
    <ul class='horde-tags'>
     <?php foreach ($task->tags as $t): ?><li><?php echo $this->h($t) ?></li><?php endforeach;?>
    </ul>
  </td>
  <td><?php echo strlen($task->desc) ? Horde::img('note.png', _("Task Note")) : '&nbsp;' ?></td>
  <td><?php echo ($task->alarm && $due) ?
    Horde::img('alarm.png', _("Task Alarm")) : '&nbsp;' ?>
  </td>
<?php if (in_array('due', $columns)): ?>
  <td class="nowrap" sortval="<?php echo $due ? $due->timestamp() : PHP_INT_MAX ?>">
    <?php echo $due ? $due->strftime($dateFormat) : '&nbsp;' ?>
  </td>
<?php endif; if (in_array('start', $columns)): ?>
  <td class="nowrap" sortval="<?php echo $task->start ? (int)$task->start : PHP_INT_MAX ?>">
    <?php echo $task->start ? strftime($dateFormat, $task->start) : '&nbsp;' ?>
  </td>
<?php endif; if (in_array('estimate', $columns)): ?>
  <td class="nowrap" sortval="<?php echo htmlspecialchars($task->estimation()) ?>">
   <?php echo htmlspecialchars($task->estimation()) ?>
  </td>
<?php endif; if (in_array('assignee', $columns)): ?>
  <td>
    <?php echo Nag::formatAssignee($task->assignee) ?>
  </td>
<?php endif; ?>
</tr>
