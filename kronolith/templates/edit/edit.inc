<?php
$showCalSelect = (!$GLOBALS['prefs']->isLocked('default_share') && count($calendars) > 1 && empty($GLOBALS['show_resource_calendars']));
$show_img = Horde::img('plus.png', _("Show"));
$hide_img = Horde::img('minus.png', _("Hide"), 'style="display:none"');
?>

<form action="<?php echo ($event->initialized && $event->hasPermission(Horde_Perms::EDIT)) ? 'edit.php' : 'add.php' ?>" method="post" name="eventform" id="eventform">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="year" value="<?php if (isset($year)) echo htmlspecialchars($year) ?>" />
<input type="hidden" name="month" value="<?php if (isset($month)) echo htmlspecialchars($month) ?>" />
<input type="hidden" name="mday" value="<?php if (isset($day)) echo htmlspecialchars($day) ?>" />
<input type="hidden" name="date" value="<?php if (isset($date)) echo htmlspecialchars($date) ?>" />
<?php if (isset($url)): ?>
<input type="hidden" name="url" value="<?php echo htmlspecialchars($url) ?>" />
<?php endif; ?>
<?php if ($event->initialized && $event->hasPermission(Horde_Perms::EDIT)): ?>
<input type="hidden" name="eventID" value="<?php echo htmlspecialchars($event->id) ?>" />
<input type="hidden" name="existingcalendar" value="<?php echo htmlspecialchars($event->calendarType . '_' . $event->calendar) ?>" />
<?php endif; ?>
<?php if (!$showCalSelect): ?>
<input type="hidden" name="targetcalendar" value="<?php echo htmlspecialchars($calendar_id) ?>" />
<?php endif; ?>

<?php if (!$event->initialized): ?>
<!-- header -->
<h1 class="header">
 <?php echo _("Add Event") ?>
</h1>
<?php endif; ?>

<table class="striped" cellspacing="0">
<?php if ($event->recurs() &&
          ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
           $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())): ?>
<!-- recurrence editing -->
<tr>
 <td></td>
 <td colspan="4" class="warning">
  <?php echo _("This is a recurring event. Edit the current event only, this occurrence<br />and all future occurences, all occurences, or save this event as new?") ?><br /><br />
  <input type="radio" name="edit_recur" value="current" id="edit_current" /> <?php echo Horde::label('edit_current', _("Current")) ?>
  <input type="radio" name="edit_recur" value="future" id="edit_future" /> <?php echo Horde::label('edit_future', _("Future")) ?>
  <input type="radio" name="edit_recur" value="all" id="edit_all" checked="checked" /> <?php echo Horde::label('edit_all', _("All")) ?>
  <input type="radio" name="edit_recur" value="copy" id="edit_copy" /> <?php echo Horde::label('edit_copy', _("Save As New")) ?>
  <input type="hidden" name="recur_ex" id="recur_ex" value="<?php echo Horde_Util::getFormData('datetime') ?>" />
 </td>
</tr>
<?php endif; ?>

<!-- buttons -->
<tr>
 <td></td>
 <td colspan="4">
  <?php echo implode("\n", $buttons) ?>
  <?php echo $cancelurl->link(array('class' => 'button')) . _("Cancel") . '</a>' ?>
  <input type="button" name="resetButton" class="button" value="<?php echo _("Reset to Defaults") ?>" />
 </td>
</tr>

<!-- title -->
<tr>
 <td class="rightAlign"><strong><?php echo Horde::label('title', _("Tit_le")) ?></strong></td>
 <td colspan="4">
  <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event->getTitle()) ?>" size="40" maxlength="255" />
 </td>
</tr>

<!-- calendar -->
<?php if ($showCalSelect): ?>
<tr>
 <td class="rightAlign"><strong><?php echo Horde::label('targetcalendar', _("Cal_endar")) ?></strong></td>
 <td colspan="4">
    <select id="targetcalendar" name="targetcalendar">
    <?php
    if (!empty($GLOBALS['display_resource_calendars'])) {
        // We are editing/viewing resource calendars
        foreach ($GLOBALS['display_resource_calendars'] as $cal) {
            $rd = Kronolith::getDriver('Resource');
            $rc = $rd->getResource($rd->getResourceIdByCalendar($cal));
            printf('<option value="%s"%s>%s</option>',
                    htmlspecialchars($cal), '', $rc->get('name')) . "\n";
        }
    } else {
        // Normal view
        foreach ($calendars as $id => $cal) {
            $delegates = array();
            if ($cal->hasPermission(Horde_Perms::EDIT)) {
                $delegates[$id] = htmlspecialchars($cal->name());
            } else {
                $delegates[$cal->owner() . '\\' . $id] = htmlspecialchars($cal->name(). ' (' . sprintf(_("as %s"), Kronolith::getUserName($cal->owner())) . ')');
            }
            foreach ($delegates as $delegate_id => $cal_name) {
                $sel = ($delegate_id == $calendar_id) ? ' selected="selected"' : '';
                printf('<option value="%s"%s>%s</option>',
                       htmlspecialchars($delegate_id), $sel, $cal_name) . "\n";
            }
        }
    }?>
    </select>

 </td>
</tr>
<?php endif; ?>

<!-- location -->
<tr>
 <td class="rightAlign"><strong><?php echo Horde::label('location', _("Lo_cation")) ?></strong></td>
 <td colspan="4">
  <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($event->location) ?>" size="40" maxlength="255" />
 </td>
</tr>

<!-- status -->
<tr>
 <td class="rightAlign">
    <strong><?php echo Horde::label('status', _("Stat_us")) ?></strong>
 </td>
 <td colspan="4">
  <?php echo Kronolith::buildStatusWidget('status', $event->status) ?>
 </td>
</tr>

<!-- private -->
<tr>
 <td class="rightAlign">
    <strong><?php echo Horde::label('private', _("Private?")) ?></strong>
 </td>
 <td colspan="4">
  <input type="checkbox" class="checkbox" id="private" name="private"<?php if ($event->private) echo ' checked="checked"' ?> />
 </td>
</tr>
<?php require KRONOLITH_TEMPLATES . '/edit/edit_timespan.inc' ?>

<!-- alarm -->
<?php
if ($event->alarm) {
    $alarm_set = true;
    if ($event->alarm % 10080 == 0) {
        $alarm_value = $event->alarm / 10080;
        $alarm_unit = 'week';
        $alarm_unit_title = _("Week(s)");
    } elseif ($event->alarm % 1440 == 0) {
        $alarm_value = $event->alarm / 1440;
        $alarm_unit = 'day';
        $alarm_unit_title = _("Day(s)");
    } elseif ($event->alarm % 60 == 0) {
        $alarm_value = $event->alarm / 60;
        $alarm_unit = 'hour';
        $alarm_unit_title = _("Hour(s)");
    } else {
        $alarm_value = $event->alarm;
        $alarm_unit = 'min';
        $alarm_unit_title = _("Minute(s)");
    }
} else {
    $alarm_value = 15;
    $alarm_unit = 'min';
    $alarm_set = false;
}
?>
<tr>
 <td colspan="5" id="toggle_alarm" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo Horde::label('alarm', _("_Alarm")) ?></strong>
  <span class="extra" id="extra_alarm"><?php if ($alarm_set) printf(_("%d %s before the event starts"), $alarm_value, $alarm_unit_title); ?></span>
</td>
</tr>
<tbody id="section_alarm" style="display:none">
<tr>
 <td class="rightAlign" valign="top">
  <strong><?php echo _("Alarm") ?></strong>
 </td>
 <td valign="top">
  <input id="noalarm" name="alarm" type="radio" class="checkbox" value="0"<?php if (!$alarm_set) echo ' checked="checked"' ?> /><br />
  <input id="yesalarm" name="alarm" type="radio" class="checkbox" value="1"<?php if ($alarm_set) echo ' checked="checked"' ?> />
  <label for="yesalarm" class="hidden"><?php echo _("Alarm enabled") ?></label>
 </td>
 <td>&nbsp;</td>
 <td valign="top" colspan="2">
  <label for="noalarm"> <?php echo _("No alarm") ?></label><br />
  <label for="alarm_value" class="hidden"><?php echo _("Alarm Value") ?></label>
  <input type="text" size="2" id="alarm_value" name="alarm_value" value="<?php echo $alarm_value ?>" onchange="document.eventform.alarm[1].checked=true" />&nbsp;
  <label for="alarm_unit" class="hidden"><?php echo _("Alarm Unit") ?></label>
  <select id="alarm_unit" name="alarm_unit" onchange="document.eventform.alarm[1].checked=true">
   <option value="1"<?php echo ($alarm_unit == 'min') ? ' selected="selected"' : '' ?>><?php echo _("Minute(s)") ?></option>
   <option value="60"<?php echo ($alarm_unit == 'hour') ? ' selected="selected"' : '' ?>><?php echo _("Hour(s)") ?></option>
   <option value="1440"<?php echo ($alarm_unit == 'day') ? ' selected="selected"' : '' ?>><?php echo _("Day(s)") ?></option>
   <option value="10080"<?php echo ($alarm_unit == 'week') ? ' selected="selected"' : '' ?>><?php echo _("Week(s)") ?></option>
  </select>
  <?php echo _("before the event starts") ?>
 </td>
</tr>
<tr>
 <td class="rightAlign" valign="top">
  <strong><?php echo _("Notification") ?></strong>
 </td>
 <td valign="top">
  <input id="nooverwrite" name="alarm_change_method" type="radio" class="checkbox" value="0"<?php if (empty($event->methods)) echo ' checked="checked"' ?> /><br />
  <input id="yesoverwrite" name="alarm_change_method" type="radio" class="checkbox" value="1"<?php if (!empty($event->methods)) echo ' checked="checked"' ?> />
 </td>
 <td>&nbsp;</td>
 <td valign="top" colspan="2">
  <label for="nooverwrite"> <?php echo _("Use default notification method") ?></label><br/>
  <label for="yesoverwrite"> <?php echo _("Use custom notification method") ?></label>
  <div id="notification_options" style="display:<?php echo !empty($event->methods) ? 'block' : 'none' ?>">
   <?php
   // TODO: clean this hack up with H4
   global $registry, $prefs;
   $pref = 'event_alarms';
   $_prefs = array($pref => array('desc' => ''));
   $helplink = '';
   $original_value = $prefs->getValue($pref);
   if (!empty($event->methods)) {
       $prefs->setValue($pref, serialize($event->methods));
   }
   include $GLOBALS['registry']->get('templates', 'horde') . '/prefs/alarm.inc';
   if (!empty($event->methods)) {
       $prefs->setValue($pref, $original_value);
   }
   ?>
  </div>
 </td>
 </tr>
</tbody>

<!-- description -->
<tr>
 <td colspan="5" id="toggle_description" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo Horde::label('description', _("Descri_ption")) ?></strong>
  <span class="extra" id="extra_description"><?php echo htmlspecialchars(substr(str_replace(array("\r", "\n"), '  ', $event->description), 0, 80)) ?></span>
</td>
</tr>
<tr id="section_description" style="display:none">
 <td colspan="5">
  <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($event->description) ?></textarea>
 </td>
</tr>

<!-- url -->
<tr>
 <td colspan="5" id="toggle_url" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo Horde::label('url', _("URL")) ?></strong>
  <span class="extra" id="extra_url"><?php echo htmlspecialchars($event->url) ?></span>
</td>
</tr>
<tr id="section_url" style="display:none">
 <td class="rightAlign"><strong><?php echo Horde::label('url', _("URL")) ?></strong></td>
 <td colspan="4">
  <input type="text" name="eventurl" id="url" value="<?php echo htmlspecialchars($event->url) ?>" size="40" maxlength="2048" />
 </td>
</tr>

<!-- attendees/resources -->
<tr>
 <td colspan="5" id="toggle_attendees" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo _("Attendees and Resources") ?></strong>
  <span class="extra"><?php echo htmlspecialchars(Kronolith::attendeeList()) ?></span>
 </td>
</tr>

<tbody id="section_attendees" style="display:none">
<tr>
 <td></td>
 <td colspan="4">
  <?php echo Horde::url('attendees.php')->link(array('class' => 'button', 'id' => 'attendees_button', 'target' => '_blank')) . _("Edit Attendees and Resources") . '</a>' ?>
 </td>
</tr>
</tbody>

<!-- recurrence -->
<tr>
 <td colspan="5" id="toggle_recurrence" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo _("Recurrence") ?></strong>
  <span class="extra" id="extra_recurrence"><?php echo $event->getRecurName(); if ($event->recurs()) echo $event->recurrence->hasRecurEnd() ? sprintf(_(" until %s"), $event->recurrence->recurEnd->strftime($GLOBALS['prefs']->getValue('date_format')) . ($this->event->recurrence->recurEnd->hour == 23 && $this->event->recurrence->recurEnd->min == 59 ? '' : ' ' . $this->event->recurrence->recurEnd->format($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia'))) : ($event->recurrence->getRecurCount() ? sprintf(_(", %d times"), $event->recurrence->getRecurCount()) : _(" without end")) ?></span>
 </td>
</tr>
<tbody id="section_recurrence" style="display:none">
<tr>
<?php if (!empty($event->baseid)):?>
 <td>&nbsp;</td>
 <td colspan="4"><?php echo sprintf(_("This is an exception to a recurring event originally scheduled on %s at %s"), $event->exceptionoriginaldate->strftime($GLOBALS['prefs']->getValue('date_format')), $this->event->exceptionoriginaldate->strftime(($GLOBALS['prefs']->getValue('twentyFour') ? '%H:%M' : '%I:%M %p')));?></td>
<?php else:?>
 <td class="rightAlign" valign="top"><strong><?php echo Horde::label('recurnone', _("Pattern")) ?></strong></td>
 <td valign="top" colspan="4">
  <table cellspacing="0" width="100%">
   <tr>
    <td class="nowrap">
     <input id="recurnone" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_NONE ?>"<?php if (!$event->recurs()) echo ' checked="checked"' ?> /><label for="recurnone"> <?php echo _("No recurrence") ?></label>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recurdaily" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_DAILY ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY)) echo ' checked="checked"' ?> /><label id="recurdaily_label" for="recurdaily"> <?php echo _("Daily: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_daily_interval" name="recur_daily_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_daily_interval', _("day(s)")) ?>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recurweekly" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_WEEKLY ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_WEEKLY)) echo ' checked="checked"' ?> /><label id="recurweekly_label" for="recurweekly"> <?php echo _("Weekly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_weekly_interval" name="recur_weekly_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_WEEKLY) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;
     <?php echo Horde::label('recur_weekly_interval', _("week(s) on:")) ?><br />
     <label for="mo"><?php echo _("Mo") ?></label><input id="mo" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_MONDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_MONDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="tu"><?php echo _("Tu") ?></label><input id="tu" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_TUESDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_TUESDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="we"><?php echo _("We") ?></label><input id="we" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_WEDNESDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_WEDNESDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="th"><?php echo _("Th") ?></label><input id="th" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_THURSDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_THURSDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="fr"><?php echo _("Fr") ?></label><input id="fr" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_FRIDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_FRIDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="sa"><?php echo _("Sa") ?></label><input id="sa" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_SATURDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_SATURDAY)) echo ' checked="checked"' ?> />&nbsp;
     <label for="su"><?php echo _("Su") ?></label><input id="su" type="checkbox" class="checkbox" name="weekly[]" value="<?php echo Horde_Date::MASK_SUNDAY ?>"<?php if ($event->recurs() && $event->recurrence->recurOnDay(Horde_Date::MASK_SUNDAY)) echo ' checked="checked"' ?> />&nbsp;
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recurmonthday" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_MONTHLY_DATE ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE)) echo ' checked="checked"' ?> /><label id="recurmonthday_label" for="recurmonthday"> <?php echo _("Monthly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_day_of_month_interval" name="recur_day_of_month_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_day_of_month_interval', _("month(s)") . ' ' . _("on the same date")) ?>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recurmonthweek" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY)) echo ' checked="checked"' ?> /><label id="recurmonthweek_label" for="recurmonthweek"> <?php echo _("Monthly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_week_of_month_interval" name="recur_week_of_month_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_week_of_month_interval', _("month(s)") . ' ' . _("on the same weekday")) ?>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recuryear" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_DATE ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE)) echo ' checked="checked"' ?> /><label id="recuryear_label" for="recuryear"> <?php echo _("Yearly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_yearly_interval" name="recur_yearly_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_yearly_interval', _("year(s) on the same date")) ?>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recuryearday" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_DAY ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY)) echo ' checked="checked"' ?> /><label id="recuryearday_label" for="recuryearday"> <?php echo _("Yearly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_yearly_day_interval" name="recur_yearly_day_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_yearly_day_interval', _("year(s) on the same day of the year")) ?>
    </td>
   </tr>
   <tr>
    <td class="nowrap">
     <input id="recuryearweekday" type="radio" class="checkbox" name="recur" value="<?php echo Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY ?>"<?php if ($event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY)) echo ' checked="checked"' ?> /><label id="recuryearweekday_label" for="recuryearweekday"> <?php echo _("Yearly: Recurs every") ?>&nbsp;</label>
     <input type="text" id="recur_yearly_weekday_interval" name="recur_yearly_weekday_interval" size="2" value="<?php echo $event->recurs() && $event->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY) ? $event->recurrence->getRecurInterval() : '' ?>" />&nbsp;<?php echo Horde::label('recur_yearly_weekday_interval', _("year(s) on the same weekday and month of the year")) ?>
    </td>
   </tr>
  </table>
 </td>
</tr>

<!-- recur end date -->
<tr>
 <td class="rightAlign" valign="top">
  <strong><?php echo Horde::label('recur_end_type', _("Recur Until")) ?></strong>
 </td>
 <td colspan="3">
  <input id="recurnoend" type="radio" class="checkbox" name="recur_end_type" value="none"<?php echo ($event->recurs() && ($event->recurrence->hasRecurEnd() || $event->recurrence->hasRecurCount()) ? '' : ' checked="checked"') ?> /><label for="recurnoend"> <?php echo _("No end date") ?></label>
 </td>
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
 <td>&nbsp;</td>
<?php endif; ?>
</tr>
<tr>
 <td>&nbsp;</td>
 <td colspan="3">
  <input type="radio" class="checkbox" id="recur_end_type" name="recur_end_type" value="date"<?php echo ($event->recurs() && $event->recurrence->hasRecurEnd() ? ' checked="checked"' : '') ?> />
  <?php echo $event->html('recur_end[year]') ?> - <?php echo $event->html('recur_end[month]') ?> - <?php echo $event->html('recur_end[day]') ?>
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
  <span id="recur_end_wday"></span>
 </td>
 <td>
  <?php echo Horde::img('calendar.png', _("Set recurrence end date"), 'id="recur_end_img"') ?>
<?php endif; ?>
 </td>
</tr>
<tr>
 <td>&nbsp;</td>
 <td colspan="3">
  <input type="radio" class="checkbox" name="recur_end_type" value="count"<?php echo ($event->recurs() && $event->recurrence->getRecurCount() ? ' checked="checked"' : '') ?> />
  <input type="text" id="recur_count" name="recur_count" size="2" onkeypress="document.eventform.recur_end_type[2].checked = true;" onchange="document.eventform.recur_end_type[2].checked = true;" value="<?php if ($event->recurs() && $event->recurrence->getRecurCount()) echo $event->recurrence->getRecurCount() ?>" />
  <label for="recur_count"><?php echo _("recurrences") ?></label>
 </td>
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
 <td>&nbsp;</td>
<?php endif; ?>
</tr>

<?php if ($event->recurs() && ($exceptions = $event->recurrence->getExceptions())): ?>
<!-- exceptions -->
<tr>
 <td class="rightAlign" valign="top">
  <strong><?php echo _("Exceptions") ?></strong>
 </td>
 <td colspan="4">
 <?php echo $event->exceptionsList(); foreach($exceptions as $exception): ?>
 <input type="hidden" name="exceptions[]" value="<?php echo $exception ?>" />
 <?php endforeach; ?>
 </td>
</tr>
 <?php endif;?>
<?php endif; ?>
</tbody>

<!-- tags  -->
<tr>
<td colspan="5" id="toggle_tags" class="control toggle">
  <?php echo $show_img ?>
  <?php echo $hide_img ?>
  <strong><?php echo _("Tags") ?></strong>
  <span class="extra" id="extra_tags"><?php echo (!empty($tags) ? htmlspecialchars($tags) : '');?></span>
  </td>
</tr>
<tr id="section_tags" style="display:none">
 <td class="rightAlign">
     <strong><?php echo Horde::label('tags', _("Tags"))?></strong>
 </td>
 <td colspan="4">
   <input type="text" name="tags" id="tags" value="<?php echo (!empty($tags) ? htmlspecialchars($tags) : '')?>" size="40" />
   <span id="tags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
   <?php $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('kronolith', 'TagAutoCompleter'), array('triggerId' => 'tags', 'id' => $event->uid)); ?>
 </td>
</tr>
<!-- spacer -->
<tr>
 <td colspan="5" style="line-height:4px;">&nbsp;</td>
</tr>

<!-- invitations/updates and buttons -->
<tr>
 <td></td>
 <td colspan="4">
  <input id="sendupdates" type="checkbox" class="checkbox" name="sendupdates" />
  <label for="sendupdates"><?php echo $event->initialized ? _("Send updates to all attendees?") : _("Send invitations to all attendees?") ?></label>
 </td>
</tr>
<tr>
 <td></td>
 <td colspan="4">
  <?php echo implode("\n", $buttons) ?>
  <?php echo $cancelurl->link(array('class' => 'button')) . _("Cancel") . '</a>' ?>
  <input type="button" name="resetButton" class="button" value="<?php echo _("Reset to Defaults") ?>" />
  </td>
 </tr>
</table>
</form>
