<?php
/**
 * Class representing a set of "Rule" timezone database entries of the
 * same name.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Timezone
 */
class Horde_Timezone_Zone
{
    /**
     * The timezone ID.
     *
     * @var string
     */
    protected $_name;

    /**
     * A Horde_Timezone object.
     *
     * @va Horde_Timezone
     */
    protected $_tz;

    /**
     * Zone lines of this zone object.
     *
     * @var array
     */
    protected $_info = array();

    /**
     * Constructor.
     *
     * @param string $name        A timezone ID.
     * @param Horde_Timezone $tz  A Horde_Timezone object. Used to retrieve
     *                            rules for this timezone.
     */
    public function __construct($name, Horde_Timezone $tz)
    {
        $this->setTzid($name);
        $this->_tz = $tz;
    }

    /**
     * Sets the timezone ID.
     *
     * There are aliases for timezone IDs, it might be necessary to
     * use one of those.
     *
     * @param string $name        A timezone ID.
     */
    public function setTzid($name)
    {
        $this->_name = $name;
    }

    /**
     * Adds a Zone line to this zone object.
     *
     * @param array $info  A parsed Zone line or continuation line.
     */
    public function add($info)
    {
        $this->_info[] = $info;
    }

    /**
     * Exports this zone to a VTIMEZONE component.
     *
     * @return Horde_Icalendar_Vtimezone  A VTIMEZONE component representing
     *                                    this timezone.
     * @throws Horde_Timezone_Exception
     */
    public function toVtimezone()
    {
        if (!count($this->_info)) {
            throw new Horde_Timezone_Exception('No rules found for timezone ' . $this->_name);
        }

        $tz = new Horde_Icalendar_Vtimezone();
        $tz->setAttribute('TZID', $this->_name);

        $startDate = $this->_getDate(0);
        $startOffset = $this->_getOffset(0);
        for ($i = 1, $c = count($this->_info); $i < $c; $i++) {
            $name = $this->_info[$i][2];
            $endDate = count($this->_info[$i]) > 3 ? $this->_getDate($i) : null;
            if ($this->_info[$i][1] == '-') {
                // Standard time.
                $component = new Horde_Icalendar_Standard();
            } elseif (preg_match('/\d+(:(\d+))?/', $this->_info[$i][1], $offset)) {
                // Indiviual rule not matching any ruleset.
                $component = new Horde_Icalendar_Daylight();
            } else {
                // Represented by a ruleset.
                $this->_tz->getRule($this->_info[$i][1])->addRules($tz, $this->_name, $name, $startOffset, $startDate, $endDate);
                // Continue, because addRules() already adds the
                // component to $tz.
                continue;
            }
            $component->setAttribute('DTSTART', $startDate);
            $component->setAttribute('TZOFFSETFROM', $startOffset);
            $startOffset = $this->_getOffset($i);
            $component->setAttribute('TZOFFSETTO', $startOffset);
            $component->setAttribute('TZNAME', $name);
            $startDate = $endDate;
            $tz->addComponent($component);
        }

        return $tz;
    }

    /**
     * Calculates a date from the date columns of a Zone line.
     *
     * @param integer $line  A line number.
     *
     * @return Horde_Date  The date of this line.
     */
    protected function _getDate($line)
    {
        $date = array_slice($this->_info[$line], 3);
        $year = $date[0];
        $month = isset($date[1]) ? Horde_Timezone::getMonth($date[1]) : 1;
        $day = isset($date[2]) ? $date[2] : 1;
        $time = isset($date[3]) && $date[3] != '-' ? $date[3] : 0;
        preg_match('/(\d+)(?::(\d+))?(?::(\d+))?(w|s|u)?/', $time, $match);
        if (!isset($match[2])) {
            $match[2] = 0;
        }
        if (!isset($match[3])) {
            $match[3] = 0;
        }
        switch (substr($time, -1)) {
        case 's':
            // Standard time. Not sure what to do about this.
            break;
        case 'u':
            // UTC, add offset.
            $offset = $this->_getOffset($line);
            $factor = $offset['ahead'] ? 1 : -1;
            $match[1] += $factor * $offset['hour'];
            $match[2] += $factor * $offset['minute'];
        case 'w':
        default:
            // Wall time, nothing to do.
            break;
        }
        return new Horde_Date(array('year'  => $year,
                                    'month' => $month,
                                    'mday'  => $day,
                                    'hour'  => $match[1],
                                    'min'   => $match[2],
                                    'sec'   => $match[3]));
    }

    /**
     * Calculates an offset from the offset column of a Zone line.
     *
     * @param integer $line  A line number.
     *
     * @return array  A hash describing the offset.
     */
    protected function _getOffset($line)
    {
        $offset = $this->_info[$line][0];
        preg_match('/(-)?(\d+):(\d+)/', $offset, $match);
        return array('ahead'  => $match[1] != '-',
                     'hour'   => $match[2],
                     'minute' => $match[3]);
    }
}
