<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date
 */

/**
 * Date repeater.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date
 */
class Horde_Date_Repeater_DayName extends Horde_Date_Repeater
{
    // (24 * 60 * 60)
    const DAY_SECONDS = 86400;

    public $currentDayStart;
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        $direction = ($pointer == 'future') ? 1 : -1;

        if (!$this->currentDayStart) {
            $this->currentDayStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + $direction));

            $dayNum = $this->_dayNumber($this->type);
            while ($this->currentDayStart->dayOfWeek() != $dayNum) {
                $this->currentDayStart->day += $direction;
            }
        } else {
            $this->currentDayStart->day += $direction * 7;
        }

        $end = clone $this->currentDayStart;
        $end->day++;
        return new Horde_Date_Span($this->currentDayStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::next($pointer);

        if ($pointer == 'none') {
            $pointer = 'future';
        }
        return $this->next($pointer);
    }

    public function width()
    {
        return self::DAY_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-dayname-' . $this->type;
    }

    protected function _dayNumber($dayName)
    {
        $days = array(
            'monday' => Horde_Date::DATE_MONDAY,
            'tuesday' => Horde_Date::DATE_TUESDAY,
            'wednesday' => Horde_Date::DATE_WEDNESDAY,
            'thursday' => Horde_Date::DATE_THURSDAY,
            'friday' => Horde_Date::DATE_FRIDAY,
            'saturday' => Horde_Date::DATE_SATURDAY,
            'sunday' => Horde_Date::DATE_SUNDAY,
        );
        if (!isset($days[$dayName])) {
            throw new InvalidArgumentException('Invalid day name "' . $dayName . '"');
        }
        return $days[$dayName];
    }

}
