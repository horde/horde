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
class Horde_Date_Repeater_Day extends Horde_Date_Repeater
{
    // (24 * 60 * 60)
    const DAY_SECONDS = 86400;

    public $currentDayStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentDayStart) {
            $this->currentDayStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
        }

        $direction = ($pointer == 'future') ? 1 : -1;
        $this->currentDayStart->day += $direction;

        $end = clone $this->currentDayStart;
        $end->day += 1;

        return new Horde_Date_Span($this->currentDayStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $dayBegin = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour + 1));
            $dayEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));
            break;

        case 'past':
            $dayBegin = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            $dayEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour));
            break;

        case 'none':
            $dayBegin = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            $dayEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));
            break;
        }

        return new Horde_Date_Span($dayBegin, $dayEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('day' => $direction * $amount));
    }

    public function width()
    {
        return self::DAY_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-day';
    }

}
