<?php
/**
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Date
 */

/**
 * @category Horde
 * @package  Date
 */
class Horde_Date_Repeater_Minute extends Horde_Date_Repeater
{
    public $currentMinuteStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentMinuteStart) {
            $this->currentMinuteStart = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min));
        }
        $direction = ($pointer == 'future') ? 1 : -1;
        $this->currentMinuteStart->min += $direction;

        $end = clone $this->currentMinuteStart;
        $end->min++;
        return new Horde_Date_Span($this->currentMinuteStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $minuteBegin = clone $this->now;
            $minuteEnd = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min));
            break;

        case 'past':
            $minuteBegin = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min));
            $minuteEnd = clone $this->now;
            break;

        case 'none':
            $minuteBegin = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min));
            $minuteEnd = new Horde_Date(array('month' => $this->now->month, 'year' => $this->now->year, 'day' => $this->now->day, 'hour' => $this->now->hour, 'min' => $this->now->min + 1));
            break;
        }

        return new Horde_Date_Span($minuteBegin, $minuteEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('min' => $direction * $amount));
    }

    public function width()
    {
        return 60;
    }

    public function __toString()
    {
        return parent::__toString() . '-minute';
    }

}
