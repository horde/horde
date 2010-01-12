<?php
/**
 * The Kronolith_Day:: class provides an API for dealing with days.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Day extends Horde_Date {

    /**
     * How many time slots are we dividing each hour into? Set from user
     * preferences.
     *
     * @var integer
     */
    var $_slotsPerHour;

    /**
     * How many slots do we have per day? Calculated from $_slotsPerHour.
     *
     * @see $_slotsPerHour
     * @var integer
     */
    var $_slotsPerDay;

    /**
     * How many minutes are in each slot? Calculated from $_slotsPerHour.
     *
     * @see $_slotsPerHour
     * @var integer
     */
    var $_slotLength;

    /**
     * Array of slots holding hours and minutes for each piece of this day.
     *
     * @var array
     */
    var $slots = array();

    /**
     * Constructor.
     *
     * @param integer $month
     * @param integer $day
     * @param integer $year
     */
    function Kronolith_Day($month = null, $day = null, $year = null)
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

        $this->_slotsPerHour = $GLOBALS['prefs']->getValue('slots_per_hour');
        if (!$this->_slotsPerHour) {
            $this->_slotsPerHour = 1;
        }
        $this->_slotsPerDay = $this->_slotsPerHour * 24;
        $this->_slotLength = 60 / $this->_slotsPerHour;

        for ($i = 0; $i < $this->_slotsPerDay; $i++) {
            $minutes = $i * $this->_slotLength;
            $this->slots[$i]['hour'] = (int)($minutes / 60);
            $this->slots[$i]['min'] = $minutes % 60;
        }
    }

    function getTime($format, $offset = 0)
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday + $offset,
                                     'year' => $this->year));
        return $date->strftime($format);
    }

    function getTomorrow()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday + 1,
                                     'year' => $this->year));
        return $date;
    }

    function getYesterday()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday - 1,
                                     'year' => $this->year));
        return $date;
    }

    function isToday()
    {
        return $this->compareDate(new Horde_Date(mktime(0, 0, 0))) == 0;
    }

    function isTomorrow()
    {
        $date = new Horde_Date(array('month' => $this->month,
                                     'mday' => $this->mday - 1,
                                     'year' => $this->year));
        return $date->compareDate(new Horde_Date(mktime(0, 0, 0))) == 0;
    }

    function diff()
    {
        $day2 = new Kronolith_Day();
        return Date_Calc::dateDiff($this->mday, $this->month, $this->year,
                                   $day2->mday, $day2->month, $day2->year);
    }

}
