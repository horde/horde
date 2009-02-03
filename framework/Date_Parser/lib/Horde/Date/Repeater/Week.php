<?php
class Horde_Date_Repeater_Week extends Horde_Date_Repeater
{
    /**
     * (7 * 24 * 60 * 60)
     */
    const WEEK_SECONDS = 604800;

    public $currentWeekStart;

    public function next($pointer)
    {
        parent::next($pointer);

        if (!$this->currentWeekStart) {
            switch ($pointer) {
            case 'future':
                $sundayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SUNDAY);
                $sundayRepeater->now = $this->now;
                $nextSundaySpan = $sundayRepeater->next('future');
                $this->currentWeekStart = $nextSundaySpan->begin;
                break;

            case 'past':
                $sundayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SUNDAY);
                $sundayRepeater->now = clone($this->now);
                $sundayRepeater->now->day++;
                $sundayRepeater->next('past');
                $lastSundaySpan = $sundayRepeater->next('past');
                $currentWeekStart = $lastSundaySpan->begin;
                break;
            }
        } else {
            $direction = ($pointer == 'future') ? 1 : -1;
            $this->currentWeekStart->day += $direction * 7;
        }

        $end = clone($this->currentWeekStart);
        $end->day += 7;
        return new Horde_Date_Span($this->currentWeekStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $thisWeekStart = new Horde_Date(array('year' => $now->year, 'month' => $now->month, 'day' => $now->day, 'hour' => $now->hour + 1));
            $sundayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SUNDAY);
            $sundayRepeater->now = $this->now;
            $thisSundaySpan = $sundayRepeater->this('future');
            $thisWeekEnd = $thisSundaySpan->begin;
            return new Horde_Date_Span($thisWeekStart, $thisWeekEnd);

        case 'past':
            $thisWeekEnd = new Horde_Date(array('year' => $now->year, 'month' => $now->month, 'day' => $now->day, 'hour' => $now->hour));
            $sundayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SUNDAY);
            $sundayRepeater->now = $this->now;
            $lastSundaySpan = $sundayRepeater->next('past');
            $thisWeekStart = $lastSundaySpan->begin;
            return new Horde_Date_Span($thisWeekStart, $thisWeekEnd);

        case 'none':
            $sundayRepeater = new Horde_Date_Repeater_DayName(Horde_Date::DATE_SUNDAY);
            $sundayRepeater->now = $this->now;
            $lastSundaySpan = $sundayRepeater->next('past');
            $thisWeekStart = $lastSundaySpan->begin;
            $thisWeekEnd = clone($thisWeekStart);
            $thisWeekEnd->day += 7;
            return new Horde_Date_Span($thisWeekStart, $thisWeekEnd);
        }
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('day' => $direction * $amount * 7));
    }

    public function width()
    {
        return self::WEEK_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-week';
    }

}
