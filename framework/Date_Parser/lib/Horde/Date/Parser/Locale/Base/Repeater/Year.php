<?php
class Horde_Date_Parser_Locale_Base_Repeater_Year extends Horde_Date_Parser_Locale_Base_Repeater
{
    public $currentYearStart;

    public function next($pointer)
    {
        parent::next($pointer);

        if (!$this->currentYearStart) {
            switch ($pointer) {
            case 'future':
                $this->currentYearStart = new Horde_Date(array('year' => $this->now->year + 1));
                break;

            case 'past':
                $this->currentYearStart = new Horde_Date(array('year' => $this->now->year - 1));
                break;
            }
        } else {
            $diff = ($pointer == 'future') ? 1 : -1;
            $this->currentYearStart->year += $diff;
        }

        return new Horde_Date_Span($this->currentYearStart, new Horde_Date(array('year' => $this->currentYearStart->year + 1)));
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year + 1));
            break;

        case 'past':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            break;

        case 'none':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year + 1));
            break;
        }

        return new Horde_Date_Span($thisYearStart, $thisYearEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;

        $sb = clone($span->begin);
        $sb->year += ($amount * $direction);

        $se = clone($span->end);
        $se->year += ($amount * $direction);

        return new Horde_Date_Span($se, $sb);
    }

    public function width()
    {
        return (365 * 24 * 60 * 60);
    }

    public function __toString()
    {
        return parent::__toString() . '-year';
    }

}
