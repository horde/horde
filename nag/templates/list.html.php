<script type="text/javascript">

var PREFS_UPDATE_TIMEOUT;

function table_sortCallback(tableId, column, sortDown)
{
    if (typeof PREFS_UPDATE_TIMEOUT != "undefined") {
        window.clearTimeout(PREFS_UPDATE_TIMEOUT);
    }

    PREFS_UPDATE_TIMEOUT = window.setTimeout('doPrefsUpdate("' + column + '", "' + sortDown + '")', 300);
}

function doPrefsUpdate(column, sortDown)
{
    baseurl = '<?php echo $GLOBALS['registry']->getServiceLink('prefs', 'nag') ?>';
    try {
        new Ajax.Request(baseurl, { parameters: { pref: 'sortby', value: encodeURIComponent(column.substring(1)) } });
        new Ajax.Request(baseurl, { parameters: { pref: 'sortdir', value: encodeURIComponent(sortDown) } });
    } catch (e) {}
}

</script>
<div id="page">
  <?php echo $this->tabs; ?>
  <?php echo $this->browser; ?>
  <?php echo $this->render('list/header'); ?>
  <?php if (!$this->tasks->hasTasks()): ?>

    <p class="text"><em><?php echo _("There are no tasks matching the current criteria.") ?></em></p>

  <?php else: ?>

    <table id="tasks" cellspacing="0" class="sortable nowrap">
      <?php echo $this->render('list/task_headers'); ?>
      <tbody id="tasks-body">
        <?php while ($task = $this->tasks->each()):
            if (!empty($task->completed)) {
                $style = 'linedRow closed';
            } elseif (!empty($task->due) && $task->due < time()) {
                $style = 'linedRow overdue';
            } else {
                $style = 'linedRow';
            }
            if ($task->tasklist == '**EXTERNAL**') {
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
            $locals = array(
              'style' => $style,
              'have_read' => $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ),
              'have_edit' => $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT),
              'owner' => $owner,
              'task' => $task,
              'columns' => $this->columns,
              'dynamic_sort' => $this->dynamic_sort &= !$task->hasSubTasks(),
              'dateFormat' => $GLOBALS['prefs']->getValue('date_format')); ?>
            <?php echo $this->renderPartial('list/task', array('locals' => $locals)) ?>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php if ($this->dynamic_sort) $GLOBALS['page_output']->addScriptFile('tables.js', 'horde') ?>

  <?php endif; ?>

<div id="tasks_empty">
 <?php echo _("No tasks match") ?>
</div>
</div>