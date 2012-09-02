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
        $on = !empty($varvalue) &&
            (!isset($varvalue['on']) || !empty($varvalue['on']));

        printf('<input id="%soff" type="radio" class="radio" name="%s[on]" value="0"%s %s/><label for="%soff">&nbsp;%s</label><br />',
               $varname,
               $varname,
               $on ? '' : ' checked="checked"',
               $this->_getActionScripts($form, $var),
               $varname,
               _("Use default notification method"));
        printf('<input type="radio" class="radio" name="%s[on]" value="1"%s %s/><label for="%soff">&nbsp;%s</label>',
               $varname,
               $on ? ' checked="checked"' : '',
               $this->_getActionScripts($form, $var),
               $varname,
               _("Use custom notification method"));

        if ($on) {
            echo '<br />';
            Horde_Core_Prefs_Ui_Widgets::alarmInit();
            $params = array('pref' => 'task_alarms', 'label' => '');
            if ((!empty($varvalue) && !isset($varvalue['on'])) ||
                $form->isSubmitted()) {
                $params['value'] = $varvalue;
            }
            echo Horde_Core_Prefs_Ui_Widgets::alarm($params);
        }
    }

    protected function _renderVarInput_NagStart($form, $var, $vars)
    {
        $var->type->getInfo($vars, $var, $task_start);
        $start_dt = ($task_start == 0)
            // About a week from now
            ? time() + 604800
            : $task_start;

        $start_date = strftime('%x', $start_dt);

        /* Set up the radio buttons. */
        $no_start_checked = ($task_start == 0) ? 'checked="checked" ' : '';
        $specified_start_checked = ($task_start > 0) ? 'checked="checked" ' : '';
?>
<input id="start_date_none" name="start_date" type="radio" class="radio" value="none" <?php echo $no_start_checked ?> />
<?php echo Horde::label('start_date_none', _("No delay")) ?>
<br />

<input id="start_date_specified" name="start_date" type="radio" class="radio" value="specified" <?php echo $specified_start_checked ?> />
<label for="start_date_specified" class="hidden"><?php echo _("Start date specified.") ?></label>
<label for="start_date" class="hidden"><?php echo _("Date") ?></label>
<input type="text" name="start[date]" id="start_date" size="10" value="<?php echo htmlspecialchars($start_date) ?>">
<?php
        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            $GLOBALS['page_output']->addScriptFile('calendar.js');
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
            $due_dt = strtotime($date . $time);

            // Default to having a due date for new tasks if the
            // default_due preference is set.
            if (!$vars->exists('task_id') && $GLOBALS['prefs']->getValue('default_due')) {
                $task_due = strtotime($date . $time);
            }
        } else {
            $due_dt = $task_due;
        }

        $due_date = strftime('%x', $due_dt);
        $time_format = $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i a';
        $due_time = date($time_format, $due_dt);

        /* Set up the radio buttons. */
        $none_checked = ($task_due == 0) ? 'checked="checked" ' : '';
        $specified_checked = ($task_due > 0) ? 'checked="checked" ' : '';
?>
<input id="due_type_none" name="due_type" type="radio" class="radio" value="none" <?php echo $none_checked ?> />
<?php echo Horde::label('due_type_none', _("No due date.")) ?>
<br />

<input id="due_type_specified" name="due_type" type="radio" class="radio" value="specified" <?php echo $specified_checked ?> />
<label for="due_type_specified" class="hidden"><?php echo _("Due date specified.") ?></label>
<label for="due_date" class="hidden"><?php echo _("Date") ?></label>
<input type="text" name="due[date]" id="due_date" size="10" value="<?php echo htmlspecialchars($due_date) ?>">

<?php
        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'full_weekdays' => true
            ));
            $GLOBALS['page_output']->addScriptFile('calendar.js');
            echo '<span id="due_wday"></span>' .
                Horde::img('calendar.png', _("Calendar"), 'id="dueimg"');
        }
?>

<?php echo _("at") ?>
<label for="due_time" class="hidden"><?php echo _("Time") ?></label>
<input type="text" name="due[time]" id="due_time" size="8" value="<?php echo htmlspecialchars($due_time) ?>">

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
     * Render tag field.
     */
    protected function _renderVarInput_NagTags($form, $var, $vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
        $value = $var->getValue($vars);

        $html = sprintf('<input id="%s" type="text" name="%s" value="%s" />', $varname, $varname, $value);
        $html .= sprintf('<span id="%s_loading_img" style="display:none;">%s</span>',
            $varname,
            Horde::img('loading.gif', _("Loading...")));

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('Nag_Ajax_Imple_TagAutoCompleter', array('id' => $varname));
        return $html;
    }

    /**
     * Render the search due date fields
     */
    public function _renderVarInput_NagSearchDue($form, $var, $vars)
    {
        $html = sprintf(_("%s <input id=\"due_within\" name=\"due_within\" type=\"number\" size=\"2\" value=\"%s\" /> days of <input id=\"due_of\" name=\"due_of\" type=\"text\" value=\"%s\" />"),
            Horde::label('due_within', _("Is due within")), $vars->get('due_within'), $vars->get('due_of'))
            . '<div class="horde-form-field-description">' . _("E.g., Is due within 2 days of today") . '</div>';

        return $html;
    }

}
