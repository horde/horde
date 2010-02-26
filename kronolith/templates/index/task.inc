<div id="kronolithTaskDialog" class="kronolithDialog" style="display:none">
<form id="kronolithTaskForm" action="">
<input id="kronolithTaskId" type="hidden" name="task_id" />
<input id="kronolithTaskOldList" type="hidden" name="old_tasklist" />

<div>
  <label for="kronolithTaskTitle"><?php echo _("Task title") ?>:</label><br />
  <input type="text" name="task[name]" id="kronolithTaskTitle" class="kronolithLongField" />
</div>

<div>
  <label>
    <?php echo _("Priority") ?>:
    <select name="task[priority]" id="kronolithTaskPriority">
      <?php foreach (array(1 => _("Highest"), 2 => _("High"), 3 => _("Normal"), 4 => _("Low"), 5 => _("Lowest")) as $prio => $label): ?>
      <option value="<?php echo $prio ?>"><?php echo $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <span class="kronolithSeparator">|</span>
  <label>
    <?php echo _("Due date") ?>:
    <input type="text" name="task[due_date]" id="kronolithTaskDueDate" size="10" />
    <?php echo Horde::img('picker.png', _("Set due date"), 'id="kronolithTaskDuePicker" class="kronolithDatePicker"') ?>
  </label>
  <label>
    <?php echo _("at") ?>
    <input type="text" name="task[due_time]" id="kronolithTaskDueTime" size="8" />
  </label>
  <span class="kronolithSeparator">|</span>
  <label>
    <?php echo _("Add to") ?>
    <select name="task[tasklist]" id="kronolithTaskList">
    </select>
  </label>
</div>

<div>
  <?php echo _("Mark task as") ?>
  <input type="checkbox" name="task[completed]" id="kronolithTaskCompleted" value="1" />
  <label for="kronolithTaskCompleted"><?php echo _("completed") ?></label>
</div>

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
  <div class="kronolithDialogInfo"><?php printf(_("You can change the default settings in the %sNotification options%s"), '<a id="kronolithTaskAlarmPrefs" href="#">', '</a>') ?></div>
  <?php printf(_("%s don't set %s or %s set %s before due date %s"),
  '<label><input type="radio" name="task[alarm][on]" id="kronolithTaskAlarmOff" value="0" checked="checked" />', '</label>',
  '<label><input type="radio" name="task[alarm][on]" id="kronolithTaskAlarmOn" value="1" />', '</label>
  <input type="text" name="task[alarm][value]" id="kronolithTaskAlarmValue" size="3" value="15" class="kronolithEventValue" />
  <select name="task[alarm][unit]" id="kronolithTaskAlarmUnit">
    <option value="1">' . _("minutes") . '</option>
    <option value="60">' . _("hours") . '</option>
    <option value="1440">' . _("days") . '</option>
    <option value="10080">' . _("weeks") . '</option>
  </select>
  <label for="kronolithTaskAlarmValue">',
  '</label>') ?>
  <br />
  <?php printf(_("Alert me %s as default %s or %s using:"),
  '<input type="radio" name="alarm_change_method" id="kronolithTaskAlarmDefaultOn" value="0" checked="checked" />
  <label for="kronolithTaskAlarmDefaultOn">', '</label>',
  '<input type="radio" name="alarm_change_method" id="kronolithTaskAlarmDefaultOff" value="1" style="display:none" />') ?>
  <?php echo $taskAlarmMethods . $taskAlarmParams ?>
</div>

<div id="kronolithTaskTabUrl" class="kronolithTabsOption" style="display:none">
  <input type="text" name="task[url]" id="taskUrl" class="kronolithLongField" value="http://" />
</div>

<div class="kronolithFormActions">
  <input id="kronolithTaskSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithTaskDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
  <span class="kronolithDialogHelp"><a href="http://ajuda.sapo.pt/" target="help"><?php echo _("Need help? See ajuda.sapo.pt") ?></a></span>
</div>

</form>
</div>
