<?php
class Horde_Date_Parser_Locale_Base_Repeater_DayName extends Horde_Date_Parser_Locale_Base_Repeater
{
    // (24 * 60 * 60)
    const DAY_SECONDS = 86400;

    public $currentDayStart;

    public function next($pointer)
    {
        parent::next($pointer);

        $direction = ($pointer == 'future') ? 1 : -1;

        if (!$this->currentDayStart) {
            $this->currentDayStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + $direction));

            $dayNum = $this->type;
            while ($this->currentDayStart->dayOfWeek() != $dayNum) {
                $this->currentDayStart->day += $direction;
            }
        } else {
            $this->currentDayStart->day += $direction * 7;
        }

        $end = clone($this->currentDayStart);
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
        $dayStrings = array(
            Horde_Date::DATE_MONDAY => 'monday',
            Horde_Date::DATE_TUESDAY => 'tuesday',
            Horde_Date::DATE_WEDNESDAY => 'wednesday',
            Horde_Date::DATE_THURSDAY => 'thursday',
            Horde_Date::DATE_FRIDAY => 'friday',
            Horde_Date::DATE_SATURDAY => 'saturday',
            Horde_Date::DATE_SUNDAY => 'sunday',
        );
        return parent::__toString() . '-dayname-' . $dayStrings[$this->type];
    }

}
