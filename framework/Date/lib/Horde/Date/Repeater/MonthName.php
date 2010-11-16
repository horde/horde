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
class Horde_Date_Repeater_MonthName extends Horde_Date_Repeater
{
    public $currentMonthStart;
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentMonthStart) {
            $targetMonth = $this->_monthNumber($this->type);
            switch ($pointer) {
            case 'future':
                if ($this->now->month < $targetMonth) {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $targetMonth, 'day' => 1));
                } else {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year + 1, 'month' => $targetMonth, 'day' => 1));
                }
                break;

            case 'none':
                if ($this->now->month <= $targetMonth) {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $targetMonth, 'day' => 1));
                } else {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year + 1, 'month' => $targetMonth, 'day' => 1));
                }
                break;

            case 'past':
                if ($this->now->month > $targetMonth) {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year, 'month' => $targetMonth, 'day' => 1));
                } else {
                    $this->currentMonthStart = new Horde_Date(array('year' => $this->now->year - 1, 'month' => $targetMonth, 'day' => 1));
                }
                break;
            }
        } else {
            switch ($pointer) {
            case 'future':
                $this->currentMonthStart->year++;
                break;

            case 'past':
                $this->currentMonthStart->year--;
                break;
            }
        }

        return new Horde_Date_Span($this->currentMonthStart, $this->currentMonthStart->add(array('month' => 1)));
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'past':
            return $this->next($pointer);

        case 'future':
        case 'none':
            return $this->next('none');
        }
    }

    public function width()
    {
        return Horde_Date_Repeater_Month::MONTH_SECONDS;
    }

    public function index()
    {
        return $this->_monthNumber($this->type);
    }

    public function __toString()
    {
        return parent::__toString() . '-monthname-' . $this->type;
    }

    protected function _monthNumber($monthName)
    {
        $months = array(
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        );
        if (!isset($months[$monthName])) {
            throw new InvalidArgumentException('Invalid month name "' . $monthName . '"');
        }
        return $months[$monthName];
    }

}