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
class Horde_Date_Repeater_Month extends Horde_Date_Repeater
{
    /**
     * 30 * 24 * 60 * 60
     */
    const MONTH_SECONDS = 2592000;

    public $currentMonthStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentMonthStart) {
            $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => 1));
        }
        $direction = ($pointer == 'future') ? 1 : -1;
        $this->currentMonthStart->month += $direction;

        $end = clone $this->currentMonthStart;
        $end->month++;
        return new Horde_Date_Span($this->currentMonthStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
            $monthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));
            $monthEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month + 1, 'day' => 1));
            break;

        case 'past':
            $monthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => 1));
            $monthEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            break;

        case 'none':
            $monthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => 1));
            $monthEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month + 1, 'day' => 1));
            break;
        }

        return new Horde_Date_Span($monthStart, $monthEnd);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add(array('month' => $amount * $direction));
    }

    public function width()
    {
        return self::MONTH_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-month';
    }

}
