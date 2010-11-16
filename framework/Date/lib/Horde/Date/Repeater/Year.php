<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Date_Repeater_Year extends Horde_Date_Repeater
{
    public $currentYearStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentYearStart) {
            $this->currentYearStart = new Horde_Date(array('year' => $this->now->year, 'month' => 1, 'day' => 1));
        }

        $diff = ($pointer == 'future') ? 1 : -1;
        $this->currentYearStart->year += $diff;

        return new Horde_Date_Span($this->currentYearStart, $this->currentYearStart->add(array('year' => 1)));
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year + 1, 'month' => 1, 'day' => 1));
            break;

        case 'past':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year, 'month' => 1, 'day' => 1));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            break;

        case 'none':
            $thisYearStart = new Horde_Date(array('year' => $this->now->year, 'month' => 1, 'day' => 1));
            $thisYearEnd = new Horde_Date(array('year' => $this->now->year + 1, 'month' => 1, 'day' => 1));
            break;
        }

        return new Horde_Date_Span($thisYearStart, $thisYearEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('year' => ($amount * $direction)));
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
