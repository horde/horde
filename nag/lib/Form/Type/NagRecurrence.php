<?php
/**
 * The Horde_Form_Type_NagRecurrence class provides a form field for editing
 * task recurrences.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Form_Type_NagRecurrence extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $recur = $vars->recurrence;
        if (!$recur) {
            return;
        }
        $recurrence = new Horde_Date_Recurrence($this->_getDue($var, $vars));
        if ($vars->recur_end_type == 'date') {
            $recurEnd = Nag::parseDate($vars->recur_end, false);
            $recurEnd->hour = 23;
            $recurEnd->min = $recurEnd->sec = 59;
            $recurrence->setRecurEnd($recurEnd);
        } elseif ($vars->recur_end_type == 'count') {
            $recurrence->setRecurCount($vars->recur_count);
        } elseif ($vars->recur_end_type == 'none') {
            $recurrence->setRecurCount(0);
            $recurrence->setRecurEnd(null);
        }

        $recurrence->setRecurType($recur);
        switch ($recur) {
        case Horde_Date_Recurrence::RECUR_DAILY:
            $recurrence->setRecurInterval($vars->get('recur_daily_interval', 1));
            break;

        case Horde_Date_Recurrence::RECUR_WEEKLY:
            $weekly = $vars->weekly;
            $weekdays = 0;
            if (is_array($weekly)) {
                foreach ($weekly as $day) {
                    $weekdays |= $day;
                }
            }

            if ($weekdays == 0) {
                // Sunday starts at 0.
                switch ($recurrence->start->dayOfWeek()) {
                case 0: $weekdays |= Horde_Date::MASK_SUNDAY; break;
                case 1: $weekdays |= Horde_Date::MASK_MONDAY; break;
                case 2: $weekdays |= Horde_Date::MASK_TUESDAY; break;
                case 3: $weekdays |= Horde_Date::MASK_WEDNESDAY; break;
                case 4: $weekdays |= Horde_Date::MASK_THURSDAY; break;
                case 5: $weekdays |= Horde_Date::MASK_FRIDAY; break;
                case 6: $weekdays |= Horde_Date::MASK_SATURDAY; break;
                }
            }

            $recurrence->setRecurInterval($vars->get('recur_weekly_interval', 1));
            $recurrence->setRecurOnDay($weekdays);
            break;

        case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
            switch ($vars->recur_monthly_scheme) {
            case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
                $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
            case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
                $recurrence->setRecurInterval(
                    $vars->recur_monthly
                        ? 1
                        : $vars->get('recur_monthly_interval', 1)
                );
                break;
            default:
                $recurrence->setRecurInterval($vars->get('recur_day_of_month_interval', 1));
                break;
            }
            break;

        case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
            $recurrence->setRecurInterval($vars->get('recur_week_of_month_interval', 1));
            break;

        case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
            switch ($vars->recur_yearly_scheme) {
            case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
            case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
                $recurrence->setRecurType($vars->recur_yearly_scheme);
            case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
                $recurrence->setRecurInterval(
                    $vars->recur_yearly
                        ? 1
                        : $vars->get('recur_yearly_interval', 1)
                );
                break;
            default:
                $recurrence->setRecurInterval($vars->get('recur_yearly_interval', 1));
                break;
            }
            break;

        case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
            $recurrence->setRecurInterval($vars->get('recur_yearly_day_interval', $yearly_interval));
            break;

        case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
            $recurrence->setRecurInterval($vars->get('recur_yearly_weekday_interval', $yearly_interval));
            break;
        }

        if ($vars->exceptions) {
            foreach ($vars->exceptions as $exception) {
                $recurrence->addException(
                    (int)substr($exception, 0, 4),
                    (int)substr($exception, 4, 2),
                    (int)substr($exception, 6, 2));
            }
        }

        $info = $recurrence;
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        if (!$vars->recurrence || $this->_getDue($var, $vars)) {
            return true;
        }
        $message = _("A due date is necessary to enable recurrences.");
        return false;
    }

    public function _getDue($var, $vars)
    {
        $variables = $var->form->getVariables();
        foreach ($variables as $variable) {
            if ($variable->getVarName() == 'due') {
                $variable->getInfo($vars, $info);
                return $info;
            }
        }
        return 0;
    }

    public function getTypeName()
    {
        return 'NagRecurrence';
    }
}
