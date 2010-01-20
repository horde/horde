<?php
class Horde_Date_Repeater_DayPortion extends Horde_Date_Repeater
{
    /**
     * 6am-12am (6 * 60 * 60, 12 * 60 * 60)
     */
    public static $morning = array(21600, 43200);

    /**
     * 1pm-5pm (13 * 60 * 60, 17 * 60 * 60)
     */
    public static $afternoon = array(46800, 61200);

    /**
     * 5pm-8pm (17 * 60 * 60, 20 * 60 * 60)
     */
    public static $evening = array(61200, 72000);

    /**
     * 8pm-12pm (20 * 60 * 60, 24 * 60 * 60)
     */
    public static $night = array(72000, 86400);

    public $range;
    public $currentSpan;
    public $type;

    public function __construct($type)
    {
        $this->type = $type;

        if (is_int($type)) {
            $this->range = array(($type * 3600), (($type + 12) * 3600));
        } else {
            $lookup = array(
                'am' => array(0, (12 * 3600 - 1)),
                'pm' => array((12 * 3600), (24 * 3600 - 1)),
                'morning' => self::$morning,
                'afternoon' => self::$afternoon,
                'evening' => self::$evening,
                'night' => self::$night,
            );
            if (!isset($lookup[$type])) {
                throw new InvalidArgumentException("Invalid type '$type' for Repeater_DayPortion");
            }
            $this->range = $lookup[$type];
        }
    }

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        $fullDay = 60 * 60 * 24;

        if (!$this->currentSpan) {
            $nowSeconds = $this->now->hour * 3600 + $this->now->min * 60 + $this->now->sec;
            if ($nowSeconds < $this->range[0]) {
                switch ($pointer) {
                case 'future':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'sec' => $this->range[0]));
                    break;

                case 'past':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day - 1, 'sec' => $this->range[0]));
                    break;
                }
            } elseif ($nowSeconds > $this->range[1]) {
                switch ($pointer) {
                case 'future':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1, 'sec' => $this->range[0]));
                    break;

                case 'past':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'sec' => $this->range[0]));
                    break;
                }
            } else {
                switch ($pointer) {
                case 'future':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day + 1, 'sec' => $this->range[0]));
                    break;

                case 'past':
                    $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day - 1, 'sec' => $this->range[0]));
                    break;
                }
            }

            $rangeEnd = $rangeStart->add($this->range[1] - $this->range[0]);
            $this->currentSpan = new Horde_Date_Span($rangeStart, $rangeEnd);
        } else {
            switch ($pointer) {
            case 'future':
                $this->currentSpan = $this->currentSpan->add(array('day' => 1));
                break;

            case 'past':
                $this->currentSpan = $this->currentSpan->add(array('day' => -1));
                break;
            }
        }

        return $this->currentSpan;
    }

    public function this($context = 'future')
    {
        parent::this($context);

        $rangeStart = new Horde_Date(array('year' => $this->now->year, 'month' => $this->now->month, 'day' => $this->now->day, 'sec' => $this->range[0]));
        $this->currentSpan = new Horde_Date_Span($rangeStart, $rangeStart->add($this->range[1] - $this->range[0]));
        return $this->currentSpan;
    }

    public function offset($span, $amount, $pointer)
    {
        $this->now = $span->begin;
        $portionSpan = $this->next($pointer);
        $direction = ($pointer == 'future') ? 1 : -1;
        return $portionSpan->add(array('day' => $direction * ($amount - 1)));
    }

    public function width()
    {
        if (!$this->range) {
            throw new Horde_Date_Repeater_Exception('Range has not been set');
        }

        if ($this->currentSpan) {
            return $this->currentSpan->width();
        }

        if (is_int($this->type)) {
            return (12 * 3600);
        } else {
            return $this->range[1] - $this->range[0];
        }
    }

    public function __toString()
    {
        return parent::__toString() . '-dayportion-' . $this->type;
    }

}
