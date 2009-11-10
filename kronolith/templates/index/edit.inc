<div id="kronolithEventDialog" style="display:none">
<form id="kronolithEventForm" action="">
<input id="kronolithEventId" type="hidden" name="id" />

<div>
  <label for="kronolithEventTitle"><?php echo _("Event title") ?>:</label><br />
  <input type="text" name="title" id="kronolithEventTitle" class="kronolithLongField" />
</div>

<table cellspacing="0" cellpadding="0" border="0"><tbody><tr>
  <td>
    <label for="kronolithEventCalendar"><?php echo _("Calendar") ?>:</label><br />
    <select name="cal" id="kronolithEventCalendar">
    </select>
  </td>
  <td>
    <label for="kronolithEventLocation"><?php echo _("Location") ?>:</label><br />
    <input type="text" name="location" id="kronolithEventLocation" size="40" />
    &middot; <span class="kronolithEventGeo"><a href="#"><?php echo _("Find in Maps") ?></a></span>
  </td>
</tr></tbody></table>

<table cellspacing="0" cellpadding="0" border="0"><tbody>
  <tr>
    <td><label for="kronolithEventStartDate"><?php echo _("From") ?>:</label></td>
    <td><label for="kronolithEventEndDate"><?php echo _("To") ?>:</label></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>
      <input type="text" name="start_date" id="kronolithEventStartDate" size="10" class="kronolithDatePicker" />
      <label id="kronolithEventStartTimeLabel"><?php echo _("at") ?> <input type="text" name="start_time" id="kronolithEventStartTime" size="8" /></label>
    </td>
    <td>
      <input type="text" name="end_date" id="kronolithEventEndDate" size="10" class="kronolithDatePicker" />
      <label id="kronolithEventEndTimeLabel"><?php echo _("at") ?> <input type="text" name="end_time" id="kronolithEventEndTime" size="8" /></label>
    </td>
    <td><label><input type="checkbox" name="whole_day" id="kronolithEventAllday"  /> <?php echo _("all-day event") ?></label></td>
  </tr>
</tbody></table>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" id="kronolithEventLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" id="kronolithEventLinkReminder"><?php echo _("Reminder") ?></a></li>
    <li><a href="#" id="kronolithEventLinkRecur"><?php echo _("Repeat") ?></a></li>
    <li><a href="#" id="kronolithEventLinkUrl"><?php echo _("URL") ?></a></li>
    <li><a href="#" id="kronolithEventLinkAttendees"><?php echo _("Attendees") ?></a></li>
    <li><a href="#" id="kronolithEventLinkTags"><?php echo _("Tags and Keywords") ?></a></li>
  </ul>
</div>
<br class="clear" />

<div id="kronolithEventTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithEventDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithEventTabReminder" class="kronolithTabsOption" style="display:none">
  <label><input type="radio" name="alarm" id="kronolithEventAlarmOff" value="0" checked="checked" /> <?php echo _("don't set") ?></label>
  <?php echo _("or") ?>
  <label><input type="radio" name="alarm" id="kronolithEventAlarmOn" value="1" /> <?php echo _("set") ?></label>
  <input type="text" name="alarm_value" id="kronolithEventAlarmValue" size="2" value="15" class="kronolithEventValue" />
  <label>
    <select name="alarm_unit" id="kronolithEventAlarmUnit">
      <option value="1"><?php echo _("minutes") ?></option>
      <option value="60"><?php echo _("hours") ?></option>
      <option value="1440"><?php echo _("days") ?></option>
      <option value="10080"><?php echo _("weeks") ?></option>
    </select> <?php echo _("before the event begins") ?>
  </label>
  <?php echo _("and alert me") ?>
  <label><?php echo _("via") ?>
    <select name="event_alarms">
      <option value=""><?php echo _("E-mail") ?></option>
      <option value=""><?php echo _("SAPO Messenger") ?></option>
      <option value="" disabled="disabled"><?php echo _("SMS") ?></option>
    </select>
  </label>
</div>

<div id="kronolithEventTabRecur" class="kronolithTabsOption" style="display:none">
  <label><input type="radio" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_NONE ?>" checked="checked" id="kronolithEventLinkNone" /> <?php echo _("Don't repeat") ?></label>
  <?php echo _("or repeat") ?>
  <label><input type="radio" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_DAILY ?>" id="kronolithEventLinkDaily" /> <?php echo _("Daily") ?></label>,
  <label><input type="radio" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_WEEKLY ?>" id="kronolithEventLinkWeekly" /> <?php echo _("Weekly") ?></label>,
  <label><input type="radio" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_MONTHLY_DATE ?>" id="kronolithEventLinkMonthly" /> <?php echo _("Monthly") ?></label>
  <?php echo _("or") ?>
  <label><input type="radio" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_DATE ?>" id="kronolithEventLinkYearly" /> <?php echo _("Yearly") ?></label><br />

  <div id="kronolithEventRepeatDaily" style="display:none">
    <?php echo _("Repeat") ?>
    <label><input type="radio" name="recur_daily" value="1" checked="checked" /> <?php echo _("every day") ?></label>
    <?php echo _("or") ?>
    <label><input type="radio" name="recur_daily" value="0" /> <?php echo _("every") ?></label>
    <label><input type="text" size="3" name="recur_daily_interval" value="" class="kronolithEventValue" /> <?php echo _("days") ?></label>
  </div>
  <div id="kronolithEventRepeatWeekly" style="display:none">
    <?php echo _("Repeat") ?>
    <label><input type="radio" name="recur_weekly" value="1" checked="checked" /> <?php echo _("every week") ?></label>
    <?php echo _("or") ?>
    <label><input type="radio" name="recur_weekly" value="0" /> <?php echo _("every") ?></label>
    <label><input type="text" size="3" name="recur_weekly_interval" value="" class="kronolithEventValue" /> <?php echo _("weeks") ?></label><br />
    <?php echo _("On") ?>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_MONDAY ?>" /> <?php echo substr(_("Monday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_TUESDAY ?>" /> <?php echo substr(_("Tuesday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_WEDNESDAY ?>" /> <?php echo substr(_("Wednesday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_THURSDAY ?>" /> <?php echo substr(_("Thursday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_FRIDAY ?>" /> <?php echo substr(_("Friday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_SATURDAY ?>" /> <?php echo substr(_("Saturday"),0,3) ?></label>
    <label><input type="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_SUNDAY ?>" /> <?php echo substr(_("Sunday"),0,3) ?></label>
  </div>
  <div id="kronolithEventRepeatMonthly" style="display:none">
    <?php echo _("Repeat") ?>
    <label><input type="radio" name="recur_monthly" value="1" checked="checked" /> <?php echo _("every month") ?></label>
    <?php echo _("or") ?>
    <label><input type="radio" name="recur_monthly" value="0" /> <?php echo _("every") ?></label>
    <label><input type="text" size="3" name="recur_monthly_interval" value="" class="kronolithEventValue" /> <?php echo _("months") ?></label>,
    <?php echo _("on the same") ?>
    <label><input type="radio" name="recur_monthly_scheme" value="<?php echo Horde_Date_Recurrence::RECUR_MONTHLY_DATE ?>" /> <?php echo _("date") ?></label>
    <label><input type="radio" name="recur_monthly_scheme" value="<?php echo Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY ?>" /> <?php echo _("weekday") ?></label>
  </div>
  <div id="kronolithEventRepeatYearly" style="display:none">
    <?php echo _("Repeat") ?>
    <label><input type="radio" name="recur_yearly" value="1" checked="checked" /> <?php echo _("every year") ?></label>
    <?php echo _("or") ?>
    <label><input type="radio" name="recur_yearly" value="0" /> <?php echo _("every") ?></label>
    <label><input type="text" size="3" name="recur_yearly_interval" value="" class="kronolithEventValue" /> <?php echo _("years") ?></label>
    <?php echo _("on the same") ?>
    <label><input type="radio" name="recur_yearly_scheme" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_DATE ?>" /> <?php echo _("date of the year") ?></label>
    <label><input type="radio" name="recur_yearly_scheme" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_DAY ?>" /> <?php echo _("day of the year") ?></label>
    <label><input type="radio" name="recur_yearly_scheme" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY ?>" /> <?php echo _("month and weekday") ?></label>
  </div>
  <div id="kronolithEventRepeatLength" style="display:none">
    <?php echo _("Stop") ?>
    <label><input type="radio" name="recur_enddate_type" value="none" checked="checked" /> <?php echo _("never") ?></label>,
    <?php echo _("or") ?>
    <label><input type="radio" name="recur_enddate_type" value="date" /> <?php echo _("at") ?></label>
    <input type="text" name="recur_end_date" id="kronolithEventRecurDate" size="10" class="kronolithDatePicker" />
    <?php echo _(", or") ?>
    <label><input type="radio" name="recur_enddate_type" value="count" /> <?php echo _("after") ?></label>
    <label><input type="text" name="recur_count" id="kronolithEventRecurCount" value="" size="2" class="kronolithEventValue" /> <?php echo _("recurrences") ?></label>
    <br />
  </div>
</div>

<div id="kronolithEventTabUrl" class="kronolithTabsOption" style="display:none">
  <input type="text" name="url" id="kronolithEventUrl" class="kronolithLongField" value="http://" />
</div>

<div id="kronolithEventTabAttendees" class="kronolithTabsOption" style="display:none">
  <input type="text" name="participants" id="kronolithEventAttendees" class="kronolithLongField" value="" /><br />
  <label><input type="checkbox" name="sendupdates" value="" /> <?php echo _("send invites") ?></label> <?php echo _("to all attendees") ?><br />
  <table width="100%" cellspacing="0" cellpadding="0" border="0">
    <thead>
      <tr>
        <th><?php echo _("Availability") ?></th>
        <?php for ($i = 0; $i < 24; $i++): ?>
        <th<?php if ($i < 8 || $i > 19) echo ' class="night"' ?>><?php echo $i ?>h</th>
        <?php endfor; ?>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
</div>

<div id="kronolithEventTabTags" class="kronolithTabsOption" style="display:none">
  <input id="kronolithEventTags" name="tags" />
  <span id="kronolithEventTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div id="kronolithEventTopTags"></div>
</div>

<div id="kronolithEventActions">
  <input id="kronolithEventSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithEventDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="kronolithSep"><?php echo _("or") ?></span> <a id="kronolithEventCancel" class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>

</form>
</div>
