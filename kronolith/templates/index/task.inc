<div id="kronolithTaskDialog" class="kronolithDialog" style="display:none">
<form id="kronolithTaskForm" action="">
<input id="kronolithTaskId" type="hidden" name="task_id" />
<input id="kronolithTaskOldList" type="hidden" name="old_tasklist" />

<div>
  <label for="kronolithTaskTitle"><?php echo _("Name") ?>:</label><br />
  <input type="text" name="task[name]" id="kronolithTaskTitle" class="kronolithLongField" />
</div>

<div>
  <label><input type="checkbox" name="task[completed]" id="kronolithTaskCompleted" value="1" /> <?php echo _("completed") ?></label>
</div>

<table cellspacing="0" cellpadding="0" border="0"><tbody><tr>
  <td>
    <div>
      <label for="kronolithTaskPriority"><?php echo _("Priority") ?>:</label><br />
      <select name="task[priority]" id="kronolithTaskPriority">
        <?php foreach (array(1 => '1 ' . _("(highest)"), 2 => 2, 3 => 3, 4 => 4, 5 => '5 ' . _("(lowest)")) as $prio => $label): ?>
        <option value="<?php echo $prio ?>"><?php echo $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </td>
  <td>
    <div>
      <label for="kronolithTaskList"><?php echo _("Task List") ?>:</label><br />
      <select name="task[tasklist]" id="kronolithTaskList">
      </select>
    </div>
  </td>
  <td>
    <div>
      <label><?php echo _("Due date") ?>:</label><br />
      <input type="text" name="task[due_date]" id="kronolithTaskDueDate" size="10" />
      <?php echo Horde::img('picker.png', _("Set due date"), 'id="kronolithTaskDuePicker" class="kronolithDatePicker"') ?>
      <?php echo _("at") ?>
      <input type="text" name="task[due_time]" id="kronolithTaskDueTime" size="8" />
    </div>
  </td>
</tr></tbody></table>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" class="kronolithTabLink" id="kronolithTaskLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithTaskLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithTaskLinkUrl"><?php echo _("URL") ?></a></li>
  </ul>
</div>
<br class="clear" />

<div id="kronolithTaskTabDescription" class="kronolithTabsOption">
  <textarea name="task[desc]" id="kronolithTaskDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithTaskTabReminder" class="kronolithTabsOption" style="display:none">
  <?php printf(_("%s don't set %s or %s set %s before due date"),
  '<label><input type="radio" name="task[alarm][on]" id="kronolithTaskAlarmOff" value="0" checked="checked" />', '</label>',
  '<label><input type="radio" name="task[alarm][on]" id="kronolithTaskAlarmOn" value="1" />', '</label>
  <input type="text" name="task[alarm][value]" id="kronolithTaskAlarmValue" size="3" value="15" class="kronolithEventValue" />
  <select name="task[alarm][unit]" id="kronolithTaskAlarmUnit">
    <option value="1">' . _("minutes") . '</option>
    <option value="60">' . _("hours") . '</option>
    <option value="1440">' . _("days") . '</option>
    <option value="10080">' . _("weeks") . '</option>
  </select>') ?>
</div>

<div id="kronolithTaskTabUrl" class="kronolithTabsOption" style="display:none">
  <input type="text" name="task[url]" id="taskUrl" class="kronolithLongField" value="http://" />
</div>

<div class="kronolithFormActions">
  <input id="kronolithTaskSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithTaskDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>

</form>
</div>
