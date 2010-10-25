<div id="kronolithEventDialog" class="kronolithDialog" style="display:none">
<form id="kronolithEventForm" action="">
<input id="kronolithEventId" type="hidden" name="event" />
<input id="kronolithEventCalendar" type="hidden" name="cal" />
<input id="kronolithEventLocationLon" type="hidden" name="lon" />
<input id="kronolithEventLocationLat" type="hidden" name="lat" />
<input id="kronolithEventMapZoom" type="hidden" name="zoom" />

<div>
  <label for="kronolithEventTitle">
    <?php echo _("Event title") ?>:<br />
    <input type="text" name="title" id="kronolithEventTitle" class="kronolithLongField" />
  </label>
</div>

<div id="kronolithEventDates">
  <label for="kronolithEventStartDate">
    <?php echo _("From") ?>
    <input type="text" name="start_date" id="kronolithEventStartDate" size="10" />
    <?php echo Horde::img('picker.png', _("Set start date"), 'id="kronolithEventStartPicker" class="kronolithDatePicker"') ?>
  </label>
  <label id="kronolithEventStartTimeLabel">
    <?php echo _("at") ?>
    <input type="text" name="start_time" id="kronolithEventStartTime" size="8" />
  </label>
  <label for="kronolithEventEndDate">
    <?php echo _("to") ?>
    <input type="text" name="end_date" id="kronolithEventEndDate" size="10" />
    <?php echo Horde::img('picker.png', _("Set end date"), 'id="kronolithEventEndPicker" class="kronolithDatePicker"') ?>
  </label>
  <label id="kronolithEventEndTimeLabel">
    <?php echo _("at") ?>
    <input type="text" name="end_time" id="kronolithEventEndTime" size="8" />
  </label>
  <span class="kronolithSeparator">|</span>
  <label for="kronolithEventAllday">
    <input type="checkbox" name="whole_day" id="kronolithEventAllday" />
    <?php echo _("All-day event") ?>
  </label>
</div>

<div id="kronolithEventBasics">
  <label for="kronolithEventStatus"><?php echo _("Status") ?>:</label>
  <select name="status" id="kronolithEventStatus">
    <option value="<?php echo Kronolith::STATUS_FREE ?>"><?php echo _("Free") ?></option>
    <option value="<?php echo Kronolith::STATUS_TENTATIVE ?>"><?php echo _("Tentative") ?></option>
    <option value="<?php echo Kronolith::STATUS_CONFIRMED ?>" selected="selected"><?php echo _("Confirmed") ?></option>
    <option value="<?php echo Kronolith::STATUS_CANCELLED ?>"><?php echo _("Cancelled") ?></option>
  </select>
  <span class="kronolithSeparator">|</span>
  <label for="kronolithEventLocation"><?php echo _("Location") ?>:</label>
  <input type="text" name="location" id="kronolithEventLocation" size="25" />
  <?php if ($GLOBALS['conf']['maps']['driver']): ?>
  <span id="kronolithEventMapLink" style="display:none">
    <span class="kronolithSeparator">|</span> <span><a href="#" class="kronolithEventGeo"><?php echo _("Find in Maps") ?></a></span>&nbsp;<span id="kronolithEventGeo_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  </span>
  <?php endif; ?>
</div>

<div>
  <label for="kronolithEventTarget"><?php echo _("Add event to") ?>:</label>
  <select name="targetcalendar" id="kronolithEventTarget"></select>
  <span class="kronolithSeparator">|</span>
  <?php printf(_("%s Private %s &mdash; hides details if calendar is public %s"), '<input type="checkbox" name="private" id="kronolithEventPrivate" /> <label for="kronolithEventPrivate">', '</label> <span class="kronolithSeparator">', '</span>') ?>
</div>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" class="kronolithTabLink" id="kronolithEventLinkDescription"><?php echo _("Notes") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkRecur"><?php echo _("Repeat") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkUrl"><?php echo _("URL") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithEventLinkAttendees"><?php echo _("Attendees") ?></a></li>
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
  <textarea name="description" id="kronolithEventDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
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
    '<label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_MONDAY . '" /> ' . substr(_("Monday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_TUESDAY . '" /> ' . substr(_("Tuesday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_WEDNESDAY . '" /> ' . substr(_("Wednesday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_THURSDAY . '" /> ' . substr(_("Thursday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_FRIDAY . '" /> ' . substr(_("Friday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_SATURDAY . '" /> ' . substr(_("Saturday"), 0, 3) . '</label>
    <label><input type="checkbox" name="weekly[]" value="' . Horde_Date::MASK_SUNDAY . '" /> ' . substr(_("Sunday"), 0, 3) . '</label>') ?>
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
  <label>
    <input type="checkbox" name="sendupdates" value="1" />
    <?php printf(_("Send invites%s to all attendees"), '</label>') ?><br />
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
      <tbody>
	<tr id="kronolithNoAttendees">
	  <td><em><?php echo _("No attendees defined") ?></em></td>
	</tr>
      </tbody>
    </table>
  </div>
</div>

<div id="kronolithEventTabTags" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("To make it easier to find, you can enter comma separated tags related to the event subject.") ?><br /><?php echo _("More information at") ?> <a href="http://ajuda.sapo.pt/">ajuda.sapo.pt</a></div>
  <input id="kronolithEventTags" name="tags" />
  <label for="kronolithEventTopTags"><?php echo _("Previously used tags") ?>:</label><br />
  <span id="kronolithEventTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="kronolithTopTags" id="kronolithEventTopTags"></div>
</div>

<div id="kronolithEventTabMap" class="kronolithTabsOption" style="display:none;">
  <div id="kronolithEventMap"></div>
</div>

<div id="kronolithEventTabExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?> <?php echo _("More information at") ?> <a href="http://ajuda.sapo.pt/">ajuda.sapo.pt</a></div>
  <label><?php echo _("Export ICS file") ?>:</label>
  <a id="kronolithEventExport"><?php echo _("Event ICS file") ?></a>
</div>

<div class="kronolithFormActions">
  <input id="kronolithEventSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithEventSaveAsNew" type="button" value="<?php echo _("Save As New") ?>" class="button ok" />
  <input id="kronolithEventDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
  <span class="kronolithDialogHelp"><a href="http://ajuda.sapo.pt/" target="help"><?php echo _("Need help? See ajuda.sapo.pt") ?></a></span>
</div>

</form>
</div>
