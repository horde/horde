<?php
class Horde_Date_Repeater_Hour extends Horde_Date_Repeater
{
    public $currentHourStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        $direction = ($pointer == 'future') ? 1 : -1;
        if (!$this->currentHourStart) {
            $this->currentHourStart = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour));
        }
        $this->currentHourStart->hour += $direction;

        $end = clone $this->currentHourStart;
        $end->hour++;
        return new Horde_Date_Span($this->currentHourStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $hourStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min + 1));
            $hourEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour + 1));
            break;

        case 'past':
            $hourStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour));
            $hourEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min));
            break;

        case 'none':
            $hourStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour));
            $hourEnd = $hourStart->add(array('hour' => 1));
            break;
        }

        return new Horde_Date_Span($hourStart, $hourEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('hour' => $direction * $amount));
    }

    public function width()
    {
        return 3600;
    }

    public function __toString()
    {
        return parent::__toString() . '-hour';
    }

}
