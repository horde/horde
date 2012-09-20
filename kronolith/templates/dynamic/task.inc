<div id="kronolithTaskDialog" class="kronolithDialog" style="display:none">
<form id="kronolithTaskForm" action="">
<input id="kronolithTaskId" type="hidden" name="task_id" />
<input id="kronolithTaskOldList" type="hidden" name="old_tasklist" />

<div>
  <label for="kronolithTaskTitle"><?php echo _("Task title") ?>:</label><br />
  <input type="text" name="task[name]" id="kronolithTaskTitle" class="kronolithLongField" tabindex="1" />
</div>

<div>
  <label>
    <?php echo _("Priority") ?>:
    <select name="task[priority]" id="kronolithTaskPriority" tabindex="2">
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
    <select name="task[tasklist]" id="kronolithTaskList" tabindex="3">
    </select>
  </label>
</div>

<div>
  <?php echo _("Mark task as") ?>
  <input type="checkbox" name="task[completed]" id="kronolithTaskCompleted" value="1" tabindex="4" />
  <label for="kronolithTaskCompleted"><?php echo _("completed") ?></label>
</div>

<div class="tabset">
  <ul>
    <li class="horde-active"><a href="#" class="kronolithTabLink" id="kronolithTaskLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithTaskLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithTaskLinkRecur"><?php echo _("Repeat") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithTaskLinkTags"><?php echo _("Tags") ?></a></li>
  </ul>
</div>
<br class="clear" />

<div id="kronolithTaskTabDescription" class="kronolithTabsOption">
  <textarea name="task[desc]" id="kronolithTaskDescription" rows="5" cols="40" class="kronolithLongField" tabindex="5"></textarea>
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

<div id="kronolithTaskTabRecur" class="kronolithTabsOption" style="display:none">
  <div id="kronolithTaskRepeatType" style="display:none">
    <?php printf(_("%s Don't repeat %s or repeat %s daily, %s weekly, %s monthly %s or %s yearly %s"),
    '<label for="kronolithTaskLinkNone"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_NONE . '" checked="checked" id="kronolithTaskLinkNone" />', '</label>',
    '<label for="kronolithTaskLinkDaily"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_DAILY . '" id="kronolithTaskLinkDaily" />', '</label>
    <label for="kronolithTaskLinkWeekly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_WEEKLY . '" id="kronolithTaskLinkWeekly" />', '</label>
    <label for="kronolithTaskLinkMonthly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_DATE . '" id="kronolithTaskLinkMonthly" />', '</label>',
    '<label for="kronolithTaskLinkYearly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DATE . '" id="kronolithTaskLinkYearly" />', '</label><br />') ?>
  </div>
  <div id="kronolithTaskRepeatDaily" style="display:none">
    <?php printf(_("Repeat %s every day %s or %s every %s days %s"),
    '<label><input type="radio" name="recur_daily" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_daily" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_daily_interval" value="" class="kronolithEventValue" />', '</label>') ?>
  </div>
  <div id="kronolithTaskRepeatWeekly" style="display:none">
    <?php printf(_("Repeat %s every week %s or %s every %s weeks %s On %s"),
    '<label><input type="radio" name="recur_weekly" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_weekly" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_weekly_interval" value="" class="kronolithEventValue" />', '</label><br />',
    '<label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_MONDAY . '" /> ' . Horde_String::substr(_("Monday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_TUESDAY . '" /> ' . Horde_String::substr(_("Tuesday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_WEDNESDAY . '" /> ' . Horde_String::substr(_("Wednesday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_THURSDAY . '" /> ' . Horde_String::substr(_("Thursday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_FRIDAY . '" /> ' . Horde_String::substr(_("Friday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_SATURDAY . '" /> ' . Horde_String::substr(_("Saturday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_SUNDAY . '" /> ' . Horde_String::substr(_("Sunday"), 0, 3) . '</label>') ?>
  </div>
  <div id="kronolithTaskRepeatMonthly" style="display:none">
    <?php printf(_("Repeat %s every month %s or %s every %s months, %s on the same %s date %s or %s weekday %s"),
    '<label><input type="radio" name="recur_monthly" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_monthly" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_monthly_interval" value="" class="kronolithEventValue" />', '</label>',
    '<label><input type="radio" name="recur_monthly_scheme" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_DATE . '" />', '</label>',
    '<label><input type="radio" name="recur_monthly_scheme" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY . '" />', '</label>') ?>
  </div>
  <div id="kronolithTaskRepeatYearly" style="display:none">
    <?php printf(_("Repeat %s every year %s or %s every %s years %s on the same %s date %s day of the year %s month and weekday %s"),
    '<label><input type="radio" name="recur_yearly" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_yearly" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_yearly_interval" value="" class="kronolithEventValue" />', '</label>',
    '<label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DATE . '" />', '</label>
    <label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DAY . '" />', '</label>
    <label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY . '" />', '</label>') ?>
  </div>
  <div id="kronolithTaskRepeatLength" style="display:none">
    <?php printf(_("Stop %s never%s, %s at %s or %s after %s recurrences %s"),
    '<label><input type="radio" name="recur_end_type" value="none" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_end_type" value="date" />', '</label>
    <input type="text" name="recur_end_date" id="kronolithTaskRecurDate" size="10" /> '
    . Horde::img('picker.png', _("Set end date"), 'id="kronolithTaskRecurPicker" class="kronolithDatePicker"'),
    '<label><input type="radio" name="recur_end_type" value="count" />', '</label>
    <label><input type="text" name="recur_count" id="kronolithTaskRecurCount" value="" size="3" class="kronolithEventValue" />', '</label>') ?>
    <br />
  </div>
</div>

<div id="kronolithTaskTabTags" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("To make it easier to find, you can enter comma separated tags related to the task subject.") ?></div>
  <input id="kronolithTaskTags" name="tags" />
  <label for="kronolithTaskTopTags"><?php echo _("Previously used tags") ?>:</label><br />
  <span id="kronolithTaskTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="kronolithTopTags" id="kronolithTaskTopTags"></div>
</div>

<div class="kronolithFormActions">
  <input id="kronolithTaskSave" type="button" value="<?php echo _("Save") ?>" class="horde-default" tabindex="6" />
  <input id="kronolithTaskDelete" type="button" value="<?php echo _("Delete") ?>" class="horde-delete" tabindex="7" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>

</form>
</div>

<div id="kronolithQuicktask" class="kronolithQuick" style="display:none;">
 <h2><?php echo _("Quick Task Creation") ?></h2>
 <form id="kronolithQuicktaskForm" method="post" action="">
  <p><?php echo _("Enter one task per line. Create child tasks by indenting them below their parent task. Include due dates like \"laundry tomorrow\" or \"get dry cleaning next Thursday\".") ?></p>
  <textarea id="kronolithQuicktaskQ"></textarea>
  <p>
   <input type="button" id="kronolithQuicktaskSave" class="horde-default" value="<?php echo _("Create") ?>" />
   <span class="kronolithSeparator"><?php echo _("or") ?></span>
   <a href="#" id="kronolithQuicktaskCancel" class="horde-cancel"><?php echo _("Cancel") ?></a><br />
  </p>
 </form>
</div>
