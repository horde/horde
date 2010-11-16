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
class Horde_Date_Repeater_Fortnight extends Horde_Date_Repeater
{
    // (14 * 24 * 60 * 60)
    const FORTNIGHT_SECONDS = 1209600;

    public $currentFortnightStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        if (!$this->currentFortnightStart) {
            switch ($pointer) {
            case 'future':
                $sundayRepeater = new Horde_Date_Repeater_DayName('sunday');
                $sundayRepeater->now = $this->now;
                $nextSundaySpan = $sundayRepeater->next('future');
                $this->currentFortnightStart = $nextSundaySpan->begin;
                break;

            case 'past':
                $sundayRepeater = new Horde_Date_Repeater_DayName('sunday');
                $sundayRepeater->now = clone $this->now;
                $sundayRepeater->now->day++;
                $sundayRepeater->next('past');
                $sundayRepeater->next('past');
                $lastSundaySpan = $sundayRepeater->next('past');
                $this->currentFortnightStart = $lastSundaySpan->begin;
                break;
            }
        } else {
            $direction = ($pointer == 'future') ? 1 : -1;
            $this->currentFortnightStart->add($direction * self::FORTNIGHT_SECONDS);
        }

        return new Horde_Date_Span($this->currentFortnightStart, $this->currentFortnightStart->add(self::FORTNIGHT_SECONDS));
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'future':
        case 'none':
            $thisFortnightStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour + 1));
            $sundayRepeater = new Horde_Date_Repeater_DayName('sunday');
            $sundayRepeater->now = $this->now;
            $sundayRepeater->this('future');
            $thisSundaySpan = $sundayRepeater->this('future');
            $thisFortnightEnd = $thisSundaySpan->begin;
            return new Horde_Date_Span($thisFortnightStart, $thisFortnightEnd);

        case 'past':
            $thisFortnightEnd = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'hour' => $this->now->hour));
            $sundayRepeater = new Horde_Date_Repeater_DayName('sunday');
            $sundayRepeater->now = $this->now;
            $lastSundaySpan = $sundayRepeater->next('past');
            $thisFortnightStart = $lastSundaySpan->begin;
            return new Horde_Date_Span($thisFortnightStart, $thisFortnightEnd);
        }
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add($direction * $amount * self::FORTNIGHT_SECONDS);
    }

    public function width()
    {
        return self::FORTNIGHT_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-fortnight';
    }

}
