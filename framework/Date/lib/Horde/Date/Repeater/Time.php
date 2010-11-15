<?php
class Horde_Date_Repeater_Time extends Horde_Date_Repeater
{
    public $currentTime;
    public $type;
    public $ambiguous;

    public function __construct($time, $options = array())
    {
        $t = str_replace(':', '', $time);

        switch (strlen($t)) {
        case 1:
        case 2:
            $hours = (int)$t;
            $this->ambiguous = true;
            $this->type = ($hours == 12) ? 0 : $hours * 3600;
            break;

        case 3:
            $this->ambiguous = true;
            $this->type = $t[0] * 3600 + (int)substr($t, 1, 2) * 60;
            break;

        case 4:
            $this->ambiguous = (strpos($time, ':') !== false) && ($t[0] != 0) && ((int)substr($t, 0, 2) <= 12);
            $hours = (int)substr($t, 0, 2);
            $this->type = ($hours == 12) ?
                ((int)substr($t, 2, 2) * 60) :
                ($hours * 60 * 60 + (int)substr($t, 2, 2) * 60);
            break;

        case 5:
            $this->ambiguous = true;
            $this->type = $t[0] * 3600 + (int)substr($t, 1, 2) * 60 + (int)substr($t, 3, 2);
            break;

        case 6:
            $this->ambiguous = (strpos($time, ':') !== false) && ($t[0] != 0) && ((int)substr($t, 0, 2) <= 12);
            $hours = (int)substr($t, 0, 2);
            $this->type = ($hours == 12) ?
                ((int)substr($t, 2, 2) * 60 + (int)substr($t, 4, 2)) :
                ($hours * 60 * 60 + (int)substr($t, 2, 2) * 60 + (int)substr($t, 4, 2));
            break;

        default:
            throw new Horde_Date_Repeater_Exception('Time cannot exceed six digits');
        }
    }

    /**
     * Return the next past or future Span for the time that this Repeater represents
     *   pointer - Symbol representing which temporal direction to fetch the next day
     *             must be either :past or :future
     */
    public function next($pointer = 'future')
    {
        parent::next($pointer);

        $halfDay = 3600 * 12;
        $fullDay = 3600 * 24;

        $first = false;

        if (!$this->currentTime) {
            $first = true;
            $midnight = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day));
            $yesterdayMidnight = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day - 1));
            $tomorrowMidnight = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1));

            if ($pointer == 'future') {
                if ($this->ambiguous) {
                    foreach (array($midnight->add($this->type), $midnight->add($halfDay + $this->type), $tomorrowMidnight->add($this->type)) as $t) {
                        if ($t->compareDateTime($this->now) >= 0) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                } else {
                    foreach (array($midnight->add($this->type), $tomorrowMidnight->add($this->type)) as $t) {
                        if ($t->compareDateTime($this->now) >= 0) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                }
            } elseif ($pointer == 'past') {
                if ($this->ambiguous) {
                    foreach (array($midnight->add($halfDay + $this->type), $midnight->add($this->type), $yesterdayMidnight->add($this->type * 2)) as $t) {
                        if ($t->compareDateTime($this->now) <= 0) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                } else {
                    foreach (array($midnight->add($this->type), $yesterdayMidnight->add($this->type)) as $t) {
                        if ($t->compareDateTime($this->now) <= 0) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                }
            }

            if (!$this->currentTime) {
                throw new Horde_Date_Repeater_Exception('Current time cannot be null at this point');
            }
        }

        if (!$first) {
            $increment = $this->ambiguous ? $halfDay : $fullDay;
            $this->currentTime->sec += ($pointer == 'future') ? $increment : -$increment;
        }

        return new Horde_Date_Span($this->currentTime, $this->currentTime->add(1));
    }

    public function this($context = 'future')
    {
        parent::this($context);

        if ($context == 'none') {
            $context = 'future';
        }
        return $this->next($context);
    }

    public function width()
    {
        return 1;
    }

    public function __toString()
    {
        return parent::__toString() . '-time-' . $this->type;
    }

}
