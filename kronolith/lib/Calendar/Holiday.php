<?php
/**
 * Kronolith_Calendar_Holiday defines an API for single holiday calendars.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_Holiday extends Kronolith_Calendar
{
    /**
     * The Date_Holidays driver information.
     *
     * @var array
     */
    protected $_driver;

    /**
     * Constructor.
     *
     * @param array $params  A hash with any parameters that this calendar
     *                       might need.
     *                       Required parameters:
     *                       - share: The share of this calendar.
     */
    public function __construct($params = array())
    {
        if (!isset($params['driver'])) {
            throw new BadMethodCallException('driver parameter is missing');
        }
        parent::__construct($params);
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    public function name()
    {
        return $this->_driver['title'];
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    public function display()
    {
        return in_array($this->_driver['id'], $GLOBALS['display_holidays']);
    }

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        $hash = parent::toHash();
        $hash['show'] = in_array($this->_driver['id'], $GLOBALS['display_holidays']);
        return $hash;
    }
}
