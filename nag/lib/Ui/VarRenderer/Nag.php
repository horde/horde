<?php
/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions required for
 * editing tasks.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Nag
 */

/**
 * The Horde_Core_Ui_VarRenderer_Nag class provides additional methods for
 * rendering Nag specific fields.
 *
 * @todo    Clean this hack up with Horde_Form/H4
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Horde_Core_Ui_VarRenderer_Nag extends Horde_Core_Ui_VarRenderer_Html
{
    protected function _renderVarInput_NagAlarm($form, $var, $vars)
    {
        $varname = htmlspecialchars($var->getVarName());
        $value = $var->getValue($vars);
        if (!is_array($value)) {
            if ($value) {
                if ($value % 10080 == 0) {
                    $value = array('value' => $value / 10080, 'unit' => 10080);
                } elseif ($value % 1440 == 0) {
                    $value = array('value' => $value / 1440, 'unit' => 1440);
                } elseif ($value % 60 == 0) {
                    $value = array('value' => $value / 60, 'unit' => 60);
                } else {
                    $value = array('value' => $value, 'unit' => 1);
                }
                $value['on'] = true;
            } else {
                $value = array('on' => false);
            }
        }
        $units = array(1 => _("Minute(s)"), 60 => _("Hour(s)"),
                       1440 => _("Day(s)"), 10080 => _("Week(s)"));
        $options = '';
        foreach ($units as $unit => $label) {
            $options .= '<option value="' . $unit;
            if ($value && $value['on'] && $value['unit'] == $unit) {
                $options .= '" selected="selected';
            }
            $options .= '">' . $label . '</option>';
        }

        return sprintf('<input id="%soff" type="radio" class="radio" name="%s[on]" value="0"%s /><label for="%soff">&nbsp;%s</label><br />',
                       $varname,
                       $varname,
                       $value['on'] ? '' : ' checked="checked"',
                       $varname,
                       _("None"))
            . sprintf('<input id="%son" type="radio" class="radio" name="%s[on]" value="1"%s />',
                      $varname,
                      $varname,
                      $value['on'] ? ' checked="checked"' : '')
            . sprintf('<input type="text" size="2" name="%s[value]" id="%s_value" value="%s" />',
                      $varname,
                      $varname,
                      $value['on'] ? htmlspecialchars($value['value']) : 15)
            . sprintf(' <select name="%s[unit]" id="%s_unit">%s</select>',
                      $varname,
                      $varname,
                      $options);
    }

    protected function _renderVarInput_NagDue($form, $var, $vars)
    {
        $var->type->getInfo($vars, $var, $task_due);
        if ($task_due == 0) {
            $date = '+' . (int)$GLOBALS['prefs']->getValue('default_due_days') . ' days';
            $time = $GLOBALS['prefs']->getValue('default_due_time');
            if ($time == 'now') {
                $time = '';
            } else {
                $time = ' ' . $time;
            }
            $due_dt = strtotime($date . $time);

            // Default to having a due date for new tasks if the
            // default_due preference is set.
            if (!$vars->exists('task_id') && $GLOBALS['prefs']->getValue('default_due')) {
                $task_due = strtotime($date . $time);
            }
        } else {
            $due_dt = $task_due;
        }

        $on = $task_due > 0;

        /* Set up the radio buttons. */
        $html = sprintf(
            '<input id="due_type_none" name="due_type" type="radio" class="radio" value="none"%s />
%s
<br />

<input id="due_type_specified" name="due_type" type="radio" class="radio" value="specified"%s />
<label for="due_type_specified" class="hidden">%s</label>
<label for="due_date" class="hidden">%s</label>
<input type="text" name="due[date]" id="due_date" size="10" value="%s">',
            $on ? '' : ' checked="checked"',
            Horde::label('due_type_none', _("No due date.")),
            $on ? ' checked="checked"' : '',
            _("Due date specified."),
            _("Date"),
            htmlspecialchars(strftime('%x', $due_dt))
        );

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            $GLOBALS['page_output']->addScriptFile('calendar.js');
            $html .= ' <span id="due_wday"></span>' .
                Horde::img('calendar.png', _("Calendar"), 'id="dueimg"');
        }

        $time_format = $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i a';
        $due_time = date($time_format, $due_dt);
        $html .= _("at")
            . sprintf(
                '<label for="due_time" class="hidden">%s</label>
<input type="text" name="due[time]" id="due_time" size="8" value="%s">',
                _("Time"),
                htmlspecialchars($due_time)
            );

        return $html;
    }

    protected function _renderVarInput_NagMethod($form, $var, $vars)
    {
        $varname = htmlspecialchars($var->getVarName());
        $varvalue = $var->getValue($vars);
        $on = !empty($varvalue) &&
            (!isset($varvalue['on']) || !empty($varvalue['on']));

        $html = sprintf(
            '<input id="%soff" type="radio" class="radio" name="%s[on]" value="0"%s %s/><label for="%soff">&nbsp;%s</label><br />',
            $varname,
            $varname,
            $on ? '' : ' checked="checked"',
            $this->_getActionScripts($form, $var),
            $varname,
            _("Use default notification method")
        )
        . sprintf(
            '<input type="radio" class="radio" name="%s[on]" value="1"%s %s/><label for="%soff">&nbsp;%s</label>',
            $varname,
            $on ? ' checked="checked"' : '',
            $this->_getActionScripts($form, $var),
            $varname,
            _("Use custom notification method")
        );

        if ($on) {
            Horde_Core_Prefs_Ui_Widgets::alarmInit();
            $html .= '<br />';
            $params = array('pref' => 'task_alarms', 'label' => '');
            if ((!empty($varvalue) && !isset($varvalue['on'])) ||
                $form->isSubmitted()) {
                $params['value'] = $varvalue;
            }
            $html .= Horde_Core_Prefs_Ui_Widgets::alarm($params);
        }

        return $html;
    }

    /**
     * Render the recurrence fields
     */
    public function _renderVarInput_NagRecurrence($form, $var, $vars)
    {
        if ($vars->recurrence instanceof Horde_Date_Recurrence) {
            $recur = $var->getValue($vars);
        } else {
            $var->type->getInfo($vars, $var, $recur);
        }

        /* No recurrence. */
        $html = sprintf(
            '<input id="recurnone" type="radio" class="checkbox" name="recurrence" value="%d"%s />
%s
<br />',
            Horde_Date_Recurrence::RECUR_NONE,
            $recur ? '' : ' checked="checked"',
            Horde::label('recurnone', _("No recurrence"))
        );

        /* Daily. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $html .= sprintf(
            '<input id="recurdaily" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recurdaily_label" for="recurdaily">%s</label>
<input type="text" id="recur_daily_interval" name="recur_daily_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_DAILY,
            $on ? ' checked="checked"' : '',
            _("Daily: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_daily_interval', _("day(s)"))
        );


        /* Weekly. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $html .= sprintf(
            '<input id="recurweekly" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recurweekly_label" for="recurweekly">%s</label>
<input type="text" id="recur_weekly_interval" name="recur_weekly_interval" size="2" value="%d" />
%s
<br />
%s<input id="mo" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="tu" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="we" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="th" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="fr" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="sa" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
%s<input id="su" type="checkbox" class="checkbox" name="weekly[]" value="%d"%s />
<br />',
            Horde_Date_Recurrence::RECUR_WEEKLY,
            $on ? ' checked="checked"' : '',
            _("Weekly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_weekly_interval', _("week(s) on:")),
            Horde::label('mo', _("Mo")),
            Horde_Date::MASK_MONDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_MONDAY) ? ' checked="checked"' : '',
            Horde::label('tu', _("Tu")),
            Horde_Date::MASK_TUESDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_TUESDAY) ? ' checked="checked"' : '',
            Horde::label('we', _("We")),
            Horde_Date::MASK_WEDNESDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_WEDNESDAY) ? ' checked="checked"' : '',
            Horde::label('th', _("Th")),
            Horde_Date::MASK_THURSDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_THURSDAY) ? ' checked="checked"' : '',
            Horde::label('fr', _("Fr")),
            Horde_Date::MASK_FRIDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_FRIDAY) ? ' checked="checked"' : '',
            Horde::label('sa', _("Sa")),
            Horde_Date::MASK_SATURDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_SATURDAY) ? ' checked="checked"' : '',
            Horde::label('su', _("Su")),
            Horde_Date::MASK_SUNDAY,
            $recur && $recur->recurOnDay(Horde_Date::MASK_SUNDAY) ? ' checked="checked"' : ''
        );

        /* Monthly on same date. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $html .= sprintf(
            '<input id="recurmonthday" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recurmonthday_label" for="recurmonthday">%s</label>
<input type="text" id="recur_day_of_month_interval" name="recur_day_of_month_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_MONTHLY_DATE,
            $on ? ' checked="checked"' : '',
            _("Monthly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_day_of_month_interval', _("month(s)") . ' ' . _("on the same date"))
        );

        /* Monthly on same weekday. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $html .= sprintf(
            '<input id="recurmonthweek" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recurmonthweek_label" for="recurmonthweek">%s</label>
<input type="text" id="recur_week_of_month_interval" name="recur_week_of_month_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY,
            $on ? ' checked="checked"' : '',
            _("Monthly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_week_of_month_interval', _("month(s)") . ' ' . _("on the same weekday"))
        );

        /* Yearly on same date. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $html .= sprintf(
            '<input id="recuryear" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recuryear_label" for="recuryear">%s</label>
<input type="text" id="recur_yearly_interval" name="recur_yearly_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_YEARLY_DATE,
            $on ? ' checked="checked"' : '',
            _("Yearly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_yearly_interval', _("year(s) on the same date"))
        );

        /* Yearly on same day of year. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $html .= sprintf(
            '<input id="recuryearday" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recuryearday_label" for="recuryearday">%s</label>
<input type="text" id="recur_yearly_day_interval" name="recur_yearly_day_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_YEARLY_DAY,
            $on ? ' checked="checked"' : '',
            _("Yearly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_yearly_day_interval', _("year(s) on the same day of the year"))
        );

        /* Yearly on same week day. */
        $on = $recur && $recur->hasRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $html .= sprintf(
            '<input id="recuryearweekday" type="radio" class="checkbox" name="recurrence" value="%d"%s />
<label id="recuryearweekday_label" for="recuryearweekday">%s</label>
<input type="text" id="recur_yearly_weekday_interval" name="recur_yearly_weekday_interval" size="2" value="%d" />
%s
<br />',
            Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY,
            $on ? ' checked="checked"' : '',
            _("Yearly: Recurs every"),
            $on ? $recur->getRecurInterval() : '',
            Horde::label('recur_yearly_weekday_interval', _("year(s) on the same weekday and month of the year"))
        );

        /* Recurrence end. */
        $html .= sprintf(
            '<br />
%s
<br />

<input id="recurnoend" type="radio" class="checkbox" name="recur_end_type" value="none"%s />
%s
<br />

<input type="radio" class="checkbox" id="recur_end_specified" name="recur_end_type" value="date"%s />
<input type="text" name="recur_end" id="recur_end_date" size="10" value="%s">',
            Horde::label('recur_end_type', _("Recur Until")),
            $recur && ($recur->hasRecurEnd() || $recur->hasRecurCount()) ? '' : ' checked="checked"',
            Horde::label('recurnoend', _("No end date")),
            $recur && $recur->hasRecurEnd() ? ' checked="checked"' : '',
            $recur && $recur->hasRecurEnd() ? $recur->getRecurEnd()->strftime('%x') : ''
        );

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            $GLOBALS['page_output']->addScriptFile('calendar.js');
            $html .= ' <span id="recur_end_wday"></span>' .
                Horde::img('calendar.png', _("Set recurrence end date"), 'id="recur_endimg"');
        }

        $on = $recur && $recur->getRecurCount();
        $html .= sprintf(
            '<br />
<input type="radio" class="checkbox" name="recur_end_type" value="count"%s />
<input type="text" id="recur_count" name="recur_count" size="2" onkeypress="document.eventform.recur_end_type[2].checked = true;" onchange="document.eventform.recur_end_type[2].checked = true;" value="%d" />
%s',
            $on ? ' checked="checked"' : '',
            $on ? $recur->getRecurCount() : '',
            Horde::label('recur_count', _("recurrences"))
        );

        return $html;
    }

    /**
     * Render the search due date fields
     */
    public function _renderVarInput_NagSearchDue($form, $var, $vars)
    {
        $html = sprintf(
            _("%s %s days of %s"),
            Horde::label('due_within', _("Is due within")),
            '<input id="due_within" name="due_within" type="number" size="2" value="' . $vars->get('due_within') . '" />',
            '<input id="due_of" name="due_of" type="text" value="' . $vars->get('due_of') . '" />')
            . '<div class="horde-form-field-description">' . _("E.g., Is due within 2 days of today") . '</div>';

        return $html;
    }

    protected function _renderVarInput_NagStart($form, $var, $vars)
    {
        $var->type->getInfo($vars, $var, $task_start);
        $start_dt = ($task_start == 0)
            // About a week from now
            ? $_SERVER['REQUEST_TIME'] + 604800
            : $task_start;
        $on = $task_start > 0;

        /* Set up the radio buttons. */
        $html = sprintf(
            '<input id="start_date_none" name="start_date" type="radio" class="radio" value="none"%s />
%s
<br />
<input id="start_date_specified" name="start_date" type="radio" class="radio" value="specified"%s />
<label for="start_date_specified" class="hidden">%s</label>
<label for="start_date" class="hidden">%s</label>
<input type="text" name="start[date]" id="start_date" size="10" value="%s">',
            $on ? '' : ' checked="checked"',
            Horde::label('start_date_none', _("No delay")),
            $on ? ' checked="checked"' : '',
            _("Start date specified."),
            _("Date"),
            htmlspecialchars(strftime('%x', $start_dt))
        );

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            $GLOBALS['page_output']->addScriptFile('calendar.js');
            $html .= ' <span id="start_wday"></span>' .
                Horde::img('calendar.png', _("Calendar"), 'id="startimg"');
        }

        return $html;
    }

    /**
     * Render tag field.
     */
    protected function _renderVarInput_NagTags($form, $var, $vars)
    {
        $varname = htmlspecialchars($var->getVarName());
        $value = $var->getValue($vars);

        $html = sprintf('<input id="%s" type="text" name="%s" value="%s" />', $varname, $varname, $value);
        $html .= sprintf('<span id="%s_loading_img" style="display:none;">%s</span>',
            $varname,
            Horde::img('loading.gif', _("Loading...")));

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('Nag_Ajax_Imple_TagAutoCompleter', array('id' => $varname));
        return $html;
    }
}
