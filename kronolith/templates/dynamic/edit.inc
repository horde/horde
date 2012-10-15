<div id="kronolithEventDialog" class="kronolithDialog" style="display:none">
<form id="kronolithEventForm" action="">
<input id="kronolithEventId" type="hidden" name="event" />
<input id="kronolithEventCalendar" type="hidden" name="cal" />
<input id="kronolithEventLocationLon" type="hidden" name="lon" />
<input id="kronolithEventLocationLat" type="hidden" name="lat" />
<input id="kronolithEventMapZoom" type="hidden" name="zoom" />
<input id="kronolithEventRecurStart" type="hidden" />
<input id="kronolithEventRecurEnd" type="hidden" />
<input id="kronolithEventRecurOStart" type="hidden" name="rstart" />
<input id="kronolithEventRecurOEnd" type="hidden" name="rend" />
<input type="hidden" name="isajax" value="1" />
<input type="hidden" id="kronolithEventSendUpdates" name="sendupdates" value="0" />

<div id="kronolithEventDiv">
  <div id="kronolithEventEditRecur" style="display:none;">
    <div class="kronolithDialogWarning">
      <?php echo _("This is a recurring event") ?>
    </div>
    <?php echo sprintf(_("Edit the %s Entire Series %s, the %s Current Event %s or this and all %s Future Events %s?"),
    '<label for="kronolithEditRecurAll"><input type="radio" name="recur_edit" value="all" id="kronolithEditRecurAll" checked="checked" />', '</label>',
    '<label for="kronolithEditRecurCurrent"><input type="radio" name="recur_edit" value="current" id="kronolithEditRecurCurrent" />', '</label>',
    '<label for="kronolithEditRecurFuture"><input type="radio" name="recur_edit" value="future" id="kronolithEditRecurFuture" />', '</label>'
    ); ?>
  </div>
<div>
  <label for="kronolithEventTitle">
    <?php echo _("Event title") ?>:<br />
    <input type="text" name="title" id="kronolithEventTitle" class="kronolithLongField" tabindex="1" />
  </label>
</div>

<div id="kronolithEventDates">
  <label for="kronolithEventStartDate">
    <?php echo _("From") ?>
    <input type="text" name="start_date" id="kronolithEventStartDate" size="10" tabindex="2" />
    <?php echo Horde::img('picker.png', _("Set start date"), 'id="kronolithEventStartPicker" class="kronolithDatePicker"') ?>
  </label>
  <label id="kronolithEventStartTimeLabel">
    <?php echo _("at") ?>
    <input type="text" name="start_time" id="kronolithEventStartTime" size="8" tabindex="3" />
  </label>
  <label for="kronolithEventEndDate">
    <?php echo _("to") ?>
    <input type="text" name="end_date" id="kronolithEventEndDate" size="10" tabindex="4" />
    <?php echo Horde::img('picker.png', _("Set end date"), 'id="kronolithEventEndPicker" class="kronolithDatePicker"') ?>
  </label>
  <label id="kronolithEventEndTimeLabel">
    <?php echo _("at") ?>
    <input type="text" name="end_time" id="kronolithEventEndTime" size="8" tabindex="5" />
  </label>
  <span class="kronolithSeparator">|</span>
  <label for="kronolithEventAllday">
    <input type="checkbox" name="whole_day" id="kronolithEventAllday" tabindex="6" />
    <?php echo _("All-day event") ?>
  </label>
  <span class="kronolithSeparator">|</span>
  <label for="kronolithEventTimezone"><?php echo _("Timezone") ?>:</label>
  <select name="timezone" id="kronolithEventTimezone" tabindex="7">
    <option value=""><?php echo _("Default") ?></option>
    <?php foreach (Horde_Nls::getTimezones() as $timezone): ?>
    <option value="<?php echo $timezone ?>"><?php echo $timezone ?></option>
    <?php endforeach ?>
  </select>
</div>

<div id="kronolithEventBasics">
  <label for="kronolithEventStatus"><?php echo _("Status") ?>:</label>
  <select name="status" id="kronolithEventStatus" tabindex="8">
    <option value="<?php echo Kronolith::STATUS_FREE ?>"><?php echo _("Free") ?></option>
    <option value="<?php echo Kronolith::STATUS_TENTATIVE ?>"><?php echo _("Tentative") ?></option>
    <option value="<?php echo Kronolith::STATUS_CONFIRMED ?>" selected="selected"><?php echo _("Confirmed") ?></option>
    <option value="<?php echo Kronolith::STATUS_CANCELLED ?>"><?php echo _("Cancelled") ?></option>
  </select>
  <span class="kronolithSeparator">|</span>
  <label for="kronolithEventLocation"><?php echo _("Location") ?>:</label>
  <input type="text" name="location" id="kronolithEventLocation" size="25" tabindex="9" />
  <?php if ($GLOBALS['conf']['maps']['driver']): ?>
  <span id="kronolithEventMapLink" style="display:none">
    <span class="kronolithSeparator">|</span> <span><a href="#" class="kronolithEventGeo"><?php echo _("Find in Maps") ?></a></span>&nbsp;<span id="kronolithEventGeo_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  </span>
  <?php endif; ?>
</div>

<div>
  <label for="kronolithEventTarget"><?php echo _("Add event to") ?>:</label>
  <span id="kronolithEventTargetRO" style="display:none"></span>
  <select name="targetcalendar" id="kronolithEventTarget" tabindex="10"></select>
  <span class="kronolithSeparator">|</span>
  <?php printf(_("%s Private %s &mdash; hides details if calendar is public %s"), '<input type="checkbox" name="private" id="kronolithEventPrivate" tabindex="11" /> <label for="kronolithEventPrivate">', '</label> <span class="kronolithSeparator">', '</span>') ?>
</div>

<div class="tabset">
  <ul>
    <li class="horde-active"><a href="#" class="kronolithTabLink" id="kronolithEventLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkRecur"><?php echo _("Repeat") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkUrl"><?php echo _("URL") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkAttendees"><?php echo _("Attendees") ?></a></li>
    <?php if (count(Kronolith::getDriver('Resource')->listResources())):?>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkResources"><?php echo _("Resources") ?></a></li>
    <?php endif; ?>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkTags"><?php echo _("Tags") ?></a></li>
    <?php if ($GLOBALS['conf']['maps']['driver']): ?>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkMap"><?php echo _("Map") ?></a></li>
    <?php endif; ?>
  </ul>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkExport"><?php echo _("Export") ?></a></li>
    </ul>
  </span>
</div>
<br class="clear" />

<div id="kronolithEventTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithEventDescription" rows="5" cols="40" class="kronolithLongField" tabindex="12"></textarea>
</div>

<div id="kronolithEventTabReminder" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php printf(_("You can change the default settings in the %sNotification options%s"), '<a id="kronolithEventAlarmPrefs" href="#">', '</a>') ?></div>
  <?php printf(_("%s Don't set %s or %s set %s before the event %s"),
  '<label for="kronolithEventAlarmOff"><input type="radio" name="alarm" id="kronolithEventAlarmOff" value="0" checked="checked" />', '</label>',
  '<label for="kronolithEventAlarmOn"><input type="radio" name="alarm" id="kronolithEventAlarmOn" value="1" />', '</label>
  <input type="text" name="alarm_value" id="kronolithEventAlarmValue" size="3" value="15" class="kronolithEventValue" />
  <select name="alarm_unit" id="kronolithEventAlarmUnit">
    <option value="1">' . _("minutes") . '</option>
    <option value="60">' . _("hours") . '</option>
    <option value="1440">' . _("days") . '</option>
    <option value="10080">' . _("weeks") . '</option>
  </select>
  <label for="kronolithEventAlarmValue">',
  '</label>') ?>
  <br />
  <?php printf(_("Alert me %s as default %s or %s using:"),
  '<input type="radio" name="alarm_change_method" id="kronolithEventAlarmDefaultOn" value="0" checked="checked" />
  <label for="kronolithEventAlarmDefaultOn">', '</label>',
  '<input type="radio" name="alarm_change_method" id="kronolithEventAlarmDefaultOff" value="1" style="display:none" />') ?>
  <?php echo $eventAlarmMethods . $eventAlarmParams ?>
</div>

<div id="kronolithEventTabRecur" class="kronolithTabsOption" style="display:none">
  <div id="kronolithEventRepeatType" style="display:none">
    <?php printf(_("%s Don't repeat %s or repeat %s daily, %s weekly, %s monthly %s or %s yearly %s"),
    '<label for="kronolithEventLinkNone"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_NONE . '" checked="checked" id="kronolithEventLinkNone" />', '</label>',
    '<label for="kronolithEventLinkDaily"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_DAILY . '" id="kronolithEventLinkDaily" />', '</label>
    <label for="kronolithEventLinkWeekly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_WEEKLY . '" id="kronolithEventLinkWeekly" />', '</label>
    <label for="kronolithEventLinkMonthly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_DATE . '" id="kronolithEventLinkMonthly" />', '</label>',
    '<label for="kronolithEventLinkYearly"><input type="radio" name="recur" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DATE . '" id="kronolithEventLinkYearly" />', '</label><br />') ?>
  </div>
  <div id="kronolithEventRepeatDaily" style="display:none">
    <?php printf(_("Repeat %s every day %s or %s every %s days %s"),
    '<label><input type="radio" name="recur_daily" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_daily" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_daily_interval" value="" class="kronolithEventValue" />', '</label>') ?>
  </div>
  <div id="kronolithEventRepeatWeekly" style="display:none">
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
  <div id="kronolithEventRepeatMonthly" style="display:none">
    <?php printf(_("Repeat %s every month %s or %s every %s months, %s on the same %s date %s or %s weekday %s"),
    '<label><input type="radio" name="recur_monthly" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_monthly" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_monthly_interval" value="" class="kronolithEventValue" />', '</label>',
    '<label><input type="radio" name="recur_monthly_scheme" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_DATE . '" />', '</label>',
    '<label><input type="radio" name="recur_monthly_scheme" value="' . Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY . '" />', '</label>') ?>
  </div>
  <div id="kronolithEventRepeatYearly" style="display:none">
    <?php printf(_("Repeat %s every year %s or %s every %s years %s on the same %s date %s day of the year %s month and weekday %s"),
    '<label><input type="radio" name="recur_yearly" value="1" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_yearly" value="0" />', '</label>
    <label><input type="text" size="3" name="recur_yearly_interval" value="" class="kronolithEventValue" />', '</label>',
    '<label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DATE . '" />', '</label>
    <label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_DAY . '" />', '</label>
    <label><input type="radio" name="recur_yearly_scheme" value="' . Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY . '" />', '</label>') ?>
  </div>
  <div id="kronolithEventRepeatLength" style="display:none">
    <?php printf(_("Stop %s never%s, %s at %s or %s after %s recurrences %s"),
    '<label><input type="radio" name="recur_end_type" value="none" checked="checked" />', '</label>',
    '<label><input type="radio" name="recur_end_type" value="date" />', '</label>
    <input type="text" name="recur_end_date" id="kronolithEventRecurDate" size="10" /> '
    . Horde::img('picker.png', _("Set end date"), 'id="kronolithEventRecurPicker" class="kronolithDatePicker"'),
    '<label><input type="radio" name="recur_end_type" value="count" />', '</label>
    <label><input type="text" name="recur_count" id="kronolithEventRecurCount" value="" size="3" class="kronolithEventValue" />', '</label>') ?>
    <br />
  </div>
  <div id="kronolithEventRepeatException" style="display:none">
    <?php printf(_("This is an exception to a recurring event originally scheduled on %s"), '<span id="recur_exception_original"></span>')?>
  </div>
</div>

<div id="kronolithEventTabUrl" class="kronolithTabsOption" style="display:none">
  <input type="text" name="eventurl" id="kronolithEventUrl" class="kronolithLongField" value="http://" />
</div>

<div id="kronolithEventTabAttendees" class="kronolithTabsOption" style="display:none">
  <label for="kronolithEventAttendees"><?php echo _("Add attendees e-mail addresses") ?>:</label> <span class="kronolithSeparator">&mdash; <?php echo _("separate e-mail addresses with a comma") ?></span><br />
  <input id="kronolithEventAttendees" name="attendees" />
  <span id="kronolithEventAttendees_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <br class="clear" />
  <div id="kronolithFBLoading" style="display:none"></div>
  <table cellspacing="0" cellpadding="0" border="0">
    <thead>
      <tr>
        <th><?php echo _("Availability") ?></th>
        <?php for ($i = 0; $i < 24; $i++): ?>
        <th<?php if ($i < 8 || $i > 19) echo ' class="night"' ?>><?php echo $i ?>h</th>
        <?php endfor; ?>
      </tr>
    </thead>
  </table>
  <div id="kronolithEventAttendeesList">
    <table cellspacing="0" cellpadding="0" border="0">
      <tbody></tbody>
    </table>
  </div>
</div>

<div id="kronolithEventTabResources" class="kronolithTabsOption" style="display:none">
  <input type="hidden" name="resources" id="kronolithEventResourceIds" />
  <label for="kronolithEventResources"><?php echo _("Add resources") ?>:</label><br />
  <input id="kronolithEventResources" />
  <span id="kronolithEventResources_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <br class="clear" /><br />
  <div id="kronolithResourceFBLoading" style="display:none"></div>
  <table cellspacing="0" cellpadding="0" border="0">
    <thead>
      <tr>
        <th><?php echo _("Availability") ?></th>
        <?php for ($i = 0; $i < 24; $i++): ?>
        <th<?php if ($i < 8 || $i > 19) echo ' class="night"' ?>><?php echo $i ?>h</th>
        <?php endfor; ?>
      </tr>
    </thead>
  </table>
  <div id="kronolithEventResourcesList">
    <table cellspacing="0" cellpadding="0" border="0">
      <tbody></tbody>
    </table>
  </div>
</div>

<div id="kronolithEventTabTags" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("To make it easier to find, you can enter comma separated tags related to the event subject.") ?></div>
  <input id="kronolithEventTags" name="tags" />
  <label for="kronolithEventTopTags"><?php echo _("Previously used tags") ?>:</label><br />
  <span id="kronolithEventTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="kronolithTopTags" id="kronolithEventTopTags"></div>
</div>

<div id="kronolithEventTabMap" class="kronolithTabsOption" style="display:none;">
  <div id="kronolithEventMap"></div>
</div>

<div id="kronolithEventTabExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></div>
  <label><?php echo _("Export ICS file") ?>:</label>
  <a id="kronolithEventExport"><?php echo _("Event ICS file") ?></a>
</div>

<div class="kronolithFormActions">
  <input id="kronolithEventSave" type="button" value="<?php echo _("Save") ?>" class="horde-default" tabindex="13" />
  <input id="kronolithEventSaveAsNew" type="button" value="<?php echo _("Save As New") ?>" />
  <input id="kronolithEventDelete" type="button" value="<?php echo _("Delete") ?>" class="horde-delete" tabindex="14" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span>
  <input type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</div>
</div>

<div id="kronolithDeleteDiv" style="display:none">
<p><?php echo _("Permanently delete this event?") ?></p>
<div class="kronolithFormActions">
  <span id="kronolithRecurDelete" style="display:none">
    <input id="kronolithRecurDeleteAll" type="button" value="<?php echo _("Delete All") ?>" class="horde-delete" />
    <input id="kronolithRecurDeleteCurrent" type="button" value="<?php echo _("Delete This Event Only") ?>" class="horde-delete" />
    <input id="kronolithRecurDeleteFuture" type="button" value="<?php echo _("Delete This and Future Events") ?>" class="horde-delete" />
  </span>
  <span id="kronolithNoRecurDelete" style="display:none">
    <input id="kronolithEventDeleteConfirm" type="button" value="<?php echo _("Delete") ?>" class="horde-delete" />
  </span>
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a id="kronolithEventDeleteCancel" class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>
<div id="kronolithUpdateDiv" style="display:none">
<p><?php echo _("Send updates to attendees?") ?></p>
<div class="kronolithFormActions">
  <input id="kronolithEventSendUpdateYes" type="button" value="<?php echo _("Yes") ?>" class="horde-default" />
  <input id="kronolithEventSendUpdateNo" type="button" value="<?php echo _("No") ?>" />
</div>
</div>
</form>
</div>
