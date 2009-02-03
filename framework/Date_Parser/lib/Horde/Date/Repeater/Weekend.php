<?php
class Horde_Date_Repeater_Weekend extends Horde_Date_Repeater
{
    /**
     * (2 * 24 * 60 * 60)
     */
    const WEEKEND_SECONDS = 172800;

    public $currentWeekStart;

    public function next($pointer)
    {
        parent::next($pointer);

        if ($this->currentWeekStart) {
            switch ($pointer) {
            case 'future':
                $saturdayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SATURDAY);
                $saturdayRepeater->now = $this->now;
                $nextSaturdaySpan = $saturdayRepeater->next('future');
                $this->currentWeekStart = $nextSaturdaySpan->begin;
                break;

            case 'past':
                $saturdayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SATURDAY);
                $saturdayRepeater->now = $this->now;
                $saturdayRepeater->now->day++;
                $lastSaturdaySpan = $saturdayRepeater->next('past');
                $this->currentWeekStart = $lastSaturdaySpan->begin;
                break;
            }
        } else {
            $direction = ($pointer == 'future') ? 1 : -1;
            $this->currentWeekStart->day += $direction * 7;
        }

        $currentWeekEnd = clone($this->currentWeekStart);
        $currentWeekEnd->day += 2;
        return new Horde_Date_Span($this->currentWeekStart, $currentWeekEnd);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
        case 'none':
            $saturdayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SATURDAY);
            $saturdayRepeater->now = $this->now;
            $thisSaturdaySpan = $saturdayRepeater->this('future');
            $thisSaturdaySpanEnd = $thisSaturdaySpan->begin;
            $thisSaturdaySpanEnd->day += 2;
            return new Horde_Date_Span($thisSaturdaySpan->begin, $thisSaturdaySpanEnd);

        case 'past':
            $saturdayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SATURDAY);
            $saturdayRepeater->now = $this->now;
            $lastSaturdaySpan = $saturdayRepeater->this('past');
            $lastSaturdaySpanEnd = $lastSaturdaySpan->begin;
            $lastSaturdaySpanEnd->day += 2;
            return new Horde_Date_Span($lastSaturdaySpan->begin, $lastSaturdaySpanEnd);
        }
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        $weekend = new Horde_Date_Repeater_Weekend('weekend');
        $weekend->now = $span->begin;
        $start = $weekend->next($pointer)->begin;
        $start->day += ($amount - 1) * $direction * 7;
        // @FIXME
        return new Horde_Date_Span($start, $start + ($span->end - $span->begin));
    }

    public function width()
    {
        return self::WEEKEND_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-weekend';
    }

}
