<?php
class Horde_Date_Repeater_Time extends Horde_Date_Repeater
{
    public $currentTime;

    public function __construct($time, $options = array())
    {
        $t = str_replace(':', '', $time);

        switch (strlen($t)) {
        case 1:
        case 2:
            $hours = (int)$t;
            $this->type = ($hours == 12) ?
                new Horde_Date_Tick(0, true) :
                new Horde_Date_Tick($hours * 3600, true);
            break;

        case 3:
            $this->type = new Horde_Date_Tick($t[0] * 3600 + (int)substr($t, 1, 2) * 60, true);
            break;

        case 4:
            $ambiguous = (strpos($time, ':') !== false) && ($t[0] != 0) && ((int)substr($t, 0, 2) <= 12);
            $hours = (int)substr($t, 0, 2);
            $this->type = ($hours == 12) ?
                new Horde_Date_Tick((int)substr($t, 2, 2) * 60, $ambiguous) :
                new Horde_Date_Tick($hours * 60 * 60 + (int)substr($t, 2, 2) * 60, $ambiguous);
            break;

        case 5:
            $this->type = new Horde_Date_Tick($t[0] * 3600 + (int)substr($t, 1, 2) * 60 + (int)substr($t, 3, 2), true);
            break;

        case 6:
            $ambiguous = (strpos($time, ':') !== false) && ($t[0] != 0) && ((int)substr($t, 0, 2) <= 12);
            $hours = (int)substr($t, 0, 2);
            $this->type = ($hours == 12) ?
                new Horde_Date_Tick((int)substr($t, 2, 2) * 60 + (int)substr($t, 4, 2), $ambiguous) :
                new Horde_Date_Tick($hours * 60 * 60 + (int)substr($t, 2, 2) * 60 + (int)substr($t, 4, 2), $ambiguous);
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
    public function next($pointer)
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
                if ($this->type->ambiguous) {
                    foreach (array($midnight + $this->type, $midnight + $halfDay + $this->type, $tomorrowMidnight + $this->type) as $t) {
                        if ($t >= $this->now) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                } else {
                    foreach (array($midnight + $this->type, $tomorrowMidnight + $this->type) as $t) {
                        if ($t >= $this->now) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                }
            } else {
                if ($this->type->ambiguous) {
                    foreach (array($midnight + $halfDay + $this->type, $midnight + $this->type, $yesterdayMidnight + $this->type * 2) as $t) {
                        if ($t <= $this->now) {
                            $this->currentTime = $t;
                            break;
                        }
                    }
                } else {
                    foreach (array($midnight + $this->type, $yesterdayMidnight + $this->type) as $t) {
                        if ($t <= $this->now) {
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
            $increment = $this->type->ambiguous ? $halfday : $fullDay;
            $this->currentTime += ($pointer == 'future') ? $increment : -$increment;
        }

        return new Horde_Date_Span($this->currentTime, $this->currentTime + $this->width());
    }

    public function this($context = 'future')
    {
        parent::this($context);

        if ($context == 'none') { $context = 'future'; }
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

class Horde_Date_Tick
{
    public $time;
    public $ambiguous;

    public function __construct($time, $ambiguous = false)
    {
        $this->time = $time;
        $this->ambiguous = $ambiguous;
    }

    public function mult($other)
    {
        return new Horde_Date_Tick($this->time * $other, $this->ambiguous);
    }

    /*
    def to_f
      @time.to_f
    end
    */

    public function __toString()
    {
        return $this->time . ($this->ambiguous ? '?' : '');
    }

}