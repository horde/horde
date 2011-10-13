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
    protected function _renderVarInput_NagMethod($form, $var, $vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
        $varvalue = $var->getValue($vars);
        if (isset($varvalue['on'])) {
            // Submitted form.
            $methods = array();
            $types = $vars->get('task_alarms');
            if (!empty($varvalue['on']) && !empty($types)) {
                foreach ($types as $type) {
                    $methods[$type] = array();
                    switch ($type){
                        case 'notify':
                            $methods[$type]['sound'] = $vars->get('task_alarms_sound');
                            break;
                        case 'mail':
                            $methods[$type]['email'] = $vars->get('task_alarms_email');
                            break;
                        case 'popup':
                            break;
                    }
                }
            }
        } else {
            // Prefilled form.
            $methods = $varvalue;
        }

        printf('<input id="%soff" type="radio" class="radio" name="%s[on]" value="0"%s %s/><label for="%soff">&nbsp;%s</label><br />',
               $varname,
               $varname,
               !empty($methods) ? '' : ' checked="checked"',
               $this->_getActionScripts($form, $var),
               $varname,
               _("Use default notification method"));
        printf('<input type="radio" class="radio" name="%s[on]" value="1"%s %s/><label for="%soff">&nbsp;%s</label>',
               $varname,
               !empty($methods) ? ' checked="checked"' : '',
               $this->_getActionScripts($form, $var),
               $varname,
               _("Use custom notification method"));

        if (!empty($methods)) {
            echo '<br />';

            global $registry, $prefs;
            $pref = 'task_alarms';
            $_prefs = array($pref => array('desc' => ''));
            $helplink = '';
            $original_value = $prefs->getValue($pref);
            if (!empty($methods)) {
                $prefs->setValue($pref, serialize($methods));
            }
            include $GLOBALS['registry']->get('templates', 'horde') . '/prefs/alarm.inc';
            if (!empty($methods)) {
                $prefs->setValue($pref, $original_value);
            }
        }
    }

    protected function _renderVarInput_NagStart($form, $var, $vars)
    {
        $var->type->getInfo($vars, $var, $task_start);
        $start_date = ($task_start == 0)
            // About a week from now
            ? getdate(time() + 604800)
            : getdate($task_start);

        /* Set up the radio buttons. */
        $no_start_checked = ($task_start == 0) ? 'checked="checked" ' : '';
        $specified_start_checked = ($task_start > 0) ? 'checked="checked" ' : '';
?>
<input id="start_date_none" name="start_date" type="radio" class="radio" value="none" <?php echo $no_start_checked ?> />
<?php echo Horde::label('start_date_none', _("No delay")) ?>
<br />

<input id="start_date_specified" name="start_date" type="radio" class="radio" value="specified" <?php echo $specified_start_checked ?> />
<label for="start_date_specified" class="hidden"><?php echo _("Start date specified.") ?></label>
<label for="start_day" class="hidden"><?php echo _("Day") ?></label>
<label for="start_month" class="hidden"><?php echo _("Month") ?></label>
<label for="start_year" class="hidden"><?php echo _("Year") ?></label>
<?php echo $this->buildDayWidget('start[day]', $start_date['mday']) . ' ' . $this->buildMonthWidget('start[month]', $start_date['mon']) . ' ' . $this->buildYearWidget('start[year]', 3, $start_date['year']) ?>
<?php
        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            Horde::addScriptFile('calendar.js', 'nag');
            echo '<span id="start_wday"></span>' .
                Horde::img('calendar.png', _("Calendar"), 'id="startimg"');
        }
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
            $due_date = getdate(strtotime($date . $time));

            // Default to having a due date for new tasks if the
            // default_due preference is set.
            if (!$vars->exists('task_id') && $GLOBALS['prefs']->getValue('default_due')) {
                $task_due = strtotime($date . $time);
            }
        } else {
            $due_date = getdate($task_due);
        }

        $hour_widget = $this->buildHourWidget('due_hour', $due_date['hours']);
        $minute_widget = $this->buildMinuteWidget('due_minute', 15, $due_date['minutes']);
        $am_pm_widget = $this->buildAmPmWidget('due_am_pm', $due_date['hours']);

        /* Set up the radio buttons. */
        $none_checked = ($task_due == 0) ? 'checked="checked" ' : '';
        $specified_checked = ($task_due > 0) ? 'checked="checked" ' : '';
?>
<input id="due_type_none" name="due_type" type="radio" class="radio" value="none" <?php echo $none_checked ?> />
<?php echo Horde::label('due_type_none', _("No due date.")) ?>
<br />

<input id="due_type_specified" name="due_type" type="radio" class="radio" value="specified" <?php echo $specified_checked ?> />
<label for="due_type_specified" class="hidden"><?php echo _("Due date specified.") ?></label>
<label for="due_day" class="hidden"><?php echo _("Day") ?></label>
<label for="due_month" class="hidden"><?php echo _("Month") ?></label>
<label for="due_year" class="hidden"><?php echo _("Year") ?></label>
<?php echo $this->buildDayWidget('due[day]', $due_date['mday']) . ' ' . $this->buildMonthWidget('due[month]', $due_date['mon']) . ' ' . $this->buildYearWidget('due[year]', 3, $due_date['year']) ?>
<?php
        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            Horde::addScriptFile('calendar.js', 'nag');
            echo '<span id="due_wday"></span>' .
                Horde::img('calendar.png', _("Calendar"), 'id="dueimg"');
        }
?>
<br />

<input type="radio" class="radio" style="visibility:hidden;" />
<label for="due_hour" class="hidden"><?php echo _("Hour") ?></label>
<label for="due_minute" class="hidden"><?php echo _("Minute") ?></label>
<?php echo $hour_widget . ' ' . $minute_widget . ' ' . $am_pm_widget ?>
<?php
    }

    protected function _renderVarInput_NagAlarm($form, $var, $vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
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
            }
        }
        $units = array(1 => _("Minute(s)"), 60 => _("Hour(s)"),
                       1440 => _("Day(s)"), 10080 => _("Week(s)"));
        $options = '';
        foreach ($units as $unit => $label) {
            $options .= '<option value="' . $unit;
            if ($value['on'] && $value['unit'] == $unit) {
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

    /**
     * Generates the HTML for a day selection widget.
     *
     * @param string $name      The name of the widget.
     * @param integer $default  The value to select by default. Range: 1-31
     *
     * @return string  The HTML <select> widget.
     */
    public function buildDayWidget($name, $default = null)
    {
        $id = str_replace(array('[', ']'), array('_', ''), $name);

        $html = '<select id="' . $id . '" name="' . $name. '">';

        for ($day = 1; $day <= 31; $day++) {
            $html .= '<option value="' . $day . '"';
            $html .= ($day == $default) ? ' selected="selected">' : '>';
            $html .= $day . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Generates the HTML for a month selection widget.
     *
     * @param string $name      The name of the widget.
     * @param integer $default  The value to select by default.
     *
     * @return string  The HTML <select> widget.
     */
    public function buildMonthWidget($name, $default = null)
    {
        $id = str_replace(array('[', ']'), array('_', ''), $name);

        $html = '<select id="' . $id . '" name="' . $name. '">';

        for ($month = 1; $month <= 12; $month++) {
            $html .= '<option value="' . $month . '"';
            $html .= ($month == $default) ? ' selected="selected">' : '>';
            $html .= strftime('%B', mktime(0, 0, 0, $month, 1)) . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Generates the HTML for a year selection widget.
     *
     * @param integer $name    The name of the widget.
     * @param integer $years   The number of years to include.
     *                         If (+): future years
     *                         If (-): past years
     * @param string $default  The timestamp to select by default.
     *
     * @return string  The HTML <select> widget.
     */
    public function buildYearWidget($name, $years, $default = null)
    {
        $curr_year = date('Y');
        $yearlist = array();

        $startyear = (!is_null($default) && ($default < $curr_year) && ($years > 0)) ? $default : $curr_year;
        $startyear = min($startyear, $startyear + $years);
        for ($i = 0; $i <= abs($years); $i++) {
            $yearlist[] = $startyear++;
        }
        if ($years < 0) {
            $yearlist = array_reverse($yearlist);
        }

        $id = str_replace(array('[', ']'), array('_', ''), $name);

        $html = '<select id="' . $id . '" name="' . $name. '">';

        foreach ($yearlist as $year) {
            $html .= '<option value="' . $year . '"';
            $html .= ($year == $default) ? ' selected="selected">' : '>';
            $html .= $year . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Generates the HTML for an hour selection widget.
     *
     * @param string $name      The name of the widget.
     * @param integer $default  The timestamp to select by default.
     *
     * @return string  The HTML <select> widget.
     */
    public function buildHourWidget($name, $default = null)
    {
        global $prefs;
        if (!$prefs->getValue('twentyFour')) {
            $default = ($default + 24) % 12;
        }

        $html = '<select id="' . $name . '" name="' . $name. '">';

        $min = $prefs->getValue('twentyFour') ? 0 : 1;
        $max = $prefs->getValue('twentyFour') ? 23 : 12;
        for ($hour = $min; $hour <= $max; $hour++) {
            $html .= '<option value="' . $hour . '"';
            $html .= ($hour == $default) ? ' selected="selected">' : '>';
            $html .= $hour . '</option>';
        }

        return $html . '</select>';
    }

    /**
     * TODO
     */
    public function buildAmPmWidget($name, $default = 'am')
    {
        if ($GLOBALS['prefs']->getValue('twentyFour')) {
            return;
        }

        if (is_numeric($default)) {
            $default = date('a', mktime($default));
        }
        if ($default == 'am') {
            $am = ' checked="checked"';
            $pm = '';
        } else {
            $am = '';
            $pm = ' checked="checked"';
        }

        $html  = '<input id="' . $name . '_am" type="radio" class="radio" name="' . $name . '" value="am"' . $am . ' /><label id="' . $name . '_am_label" for="' . $name . '_am">AM</label>&nbsp;&nbsp;';
        $html .= '<input id="' . $name . '_pm" type="radio" class="radio" name="' . $name . '" value="pm"' . $pm . ' /><label id="' . $name . '_pm_label" for="' . $name . '_pm">PM</label>';

        return $html;
    }

    /**
     * Generates the HTML for a minute selection widget.
     *
     * @param string $name        The name of the widget.
     * @param integer $increment  The increment between minutes.
     * @param integer $default    The timestamp to select by default.
     *
     * @return string  The HTML <select> widget.
     */
    public function buildMinuteWidget($name, $increment = 1, $default = null)
    {
        $html = '<select id="' . $name . '" name="' . $name. '">';

        for ($minute = 0; $minute < 60; $minute += $increment) {
            $html .= '<option value="' . $minute . '"';
            $html .= ($minute == $default) ? ' selected="selected">' : '>';
            $html .= sprintf("%02d", $minute) . '</option>';
        }

        return $html . "</select>\n";
    }
}
