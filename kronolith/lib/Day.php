<?php
/**
 * The Kronolith_Day:: class provides an API for dealing with days.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Day extends Horde_Date
{
    /**
     * How many time slots are we dividing each hour into? Set from user
     * preferences.
     *
     * @var integer
     */
    public $slotsPerHour;

    /**
     * How many slots do we have per day? Calculated from $_slotsPerHour.
     *
     * @see $_slotsPerHour
     * @var integer
     */
    public $slotsPerDay;

    /**
     * How many minutes are in each slot? Calculated from $_slotsPerHour.
     *
     * @see $_slotsPerHour
     * @var integer
     */
    public $slotLength;

    /**
     * Array of slots holding hours and minutes for each piece of this day.
     *
     * @var array
     */
    public $slots = array();

    /**
     * Constructor.
     *
     * @param integer $month
     * @param integer $day
     * @param integer $year
     */
    public function __construct($month = null, $day = null, $year = null)
    {
        if (is_null($month)) {
            $month = date('n');
        }
        if (is_null($year)) {
            $year = date('Y');
        }
        if (is_null($day)) {
            $day = date('j');
        }
        parent::__construct(array('year' => $year, 'month' => $month, 'mday' => $day));

        $this->slotsPerHour = $GLOBALS['prefs']->getValue('slots_per_hour');
        if (!$this->slotsPerHour) {
            $this->slotsPerHour = 1;
        }
        $this->slotsPerDay = $this->slotsPerHour * 24;
        $this->slotLength = 60 / $this->slotsPerHour;

        for ($i = 0; $i < $this->slotsPerDay; $i++) {
            $minutes = $i * $this->slotLength;
            $this->slots[$i]['hour'] = (int)($minutes / 60);
            $this->slots[$i]['min'] = $minutes % 60;
        }
    }

    public function getTime($format, $offset = 0)
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday + $offset,
                                     'year' => $this->year));
        return $date->strftime($format);
    }

    public function getTomorrow()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday + 1,
                                     'year' => $this->year));
        return $date;
    }

    public function getYesterday()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday - 1,
                                     'year' => $this->year));
        return $date;
    }

    public function isToday()
    {
        return $this->compareDate(new Horde_Date(mktime(0, 0, 0))) == 0;
    }

    public function isTomorrow()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday - 1,
                                     'year' => $this->year));
        return $date->compareDate(new Horde_Date(mktime(0, 0, 0))) == 0;
    }

    public function diff()
    {
        $day2 = new Kronolith_Day();
        return Date_Calc::dateDiff($this->mday, $this->month, $this->year,
                                   $day2->mday, $day2->month, $day2->year);
    }

}
