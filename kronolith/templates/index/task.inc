<div id="kronolithTaskDialog" style="display:none">
<form id="kronolithTaskForm" action="">
<input id="kronolithTaskId" type="hidden" name="task_id" />

<div>
  <label for="kronolithTaskTitle"><?php echo _("Name") ?>:</label><br />
  <input type="text" name="name" id="kronolithTaskTitle" class="kronolithLongField" />
</div>

<div>
  <label><input type="checkbox" name="completed" id="kronolithTaskCompleted" value="" /> <?php echo _("completed") ?></label>
</div>

<table cellspacing="" cellpadding="0" border="0"><tbody><tr>
  <td>
    <div>
      <label for="kronolithTaskPriority"><?php echo _("Priority") ?>:</label><br />
      <select name="priority" id="kronolithTaskPriority">
        <?php foreach (array(1 => '1 ' . _("(highest)"), 2 => 2, 3 => 3, 4 => 4, 5 => '5 ' . _("(lowest)")) as $prio => $label): ?>
        <option value="<?php echo $prio ?>"><?php echo $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </td>
  <td>
    <div>
      <label for="kronolithTaskList"><?php echo _("Task List") ?>:</label><br />
      <select name="tasklist_id" id="kronolithTaskList">
      </select>
    </div>
  </td>
  <td>
    <div>
      <label><?php echo _("Due date") ?>:</label><br />
      <input type="text" name="due_date" id="kronolithTaskDueDate" size="10" class="kronolithDatePicker" />
      <?php echo _("at") ?>
      <input type="text" name="due_time" id="kronolithTaskDueTime" size="8" />
    </div>
  </td>
</tr></tbody></table>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" id="kronolithTaskLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" id="kronolithTaskLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" id="kronolithTaskLinkUrl"><?php echo _("URL") ?></a></li>
  </ul>
</div>
<br class="clear" />

<div id="kronolithTaskTabDescription" class="kronolithTabsOption">
  <textarea name="desc" id="kronolithTaskDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithTaskTabReminder" class="kronolithTabsOption" style="display:none">
   <label><input type="radio" name="nag_alarm[on]" id="kronolithTaskAlarmOff" value="0" checked="checked" /> <?php echo _("don't set") ?></label>
   <?php echo _("or") ?>
   <label><input type="radio" name="nag_alarm[on]" id="kronolithTaskAlarmOn" value="1" /> <?php echo _("set") ?></label>
   <label>
     <input type="text" name="nag_alarm[value]" id="kronolithTaskAlarmValue" size="3" value="15" class="kronolithEventValue" />
     <select name="nag_alarm[unit]" id="kronolithTaskAlarmUnit">
       <option value="1"><?php echo _("minutes") ?></option>
       <option value="60"><?php echo _("hours") ?></option>
       <option value="1440"><?php echo _("days") ?></option>
       <option value="10080"><?php echo _("weeks") ?></option>
    </select>
    <?php echo _("before due date") ?>
  </label>
</div>

<div id="kronolithTaskTabUrl" class="kronolithTabsOption" style="display:none">
  <input type="text" name="url" id="taskUrl" class="kronolithLongField" value="http://" />
</div>

<div id="kronolithTaskActions">
  <input id="kronolithTaskSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithTaskDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="kronolithSep"><?php echo _("or") ?></span> <a id="kronolithTaskCancel" class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>

</form>
</div>
