<?php
/**
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @see     http://pear.php.net/packages/Date_Holidays
 * @author  Stephan Hohmann <webmaster@dasourcerer.net>
 * @package Kronolith
 */
class Kronolith_Event_Holidays extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType = 'holiday';

    /**
     * The status of this event.
     *
     * @var integer
     */
    public $status = Kronolith::STATUS_FREE;

    /**
     * Whether this is an all-day event.
     *
     * @var boolean
     */
    public $allday = true;

    /**
     * Parse in an event from the driver.
     *
     * @param Date_Holidays_Holiday $dhEvent  A holiday returned
     *                                        from the driver
     */
    public function fromDriver($dhEvent)
    {
        $this->stored = true;
        $this->initialized = true;
        $this->title = $dhEvent->getTitle()
        $this->start = new Horde_Date($dhEvent->_date->getTime());
        $this->end = new Horde_Date($this->start);
        $this->end->mday++;
        $this->id = $dhEvent->getInternalName() . '-' . $this->start->dateString();
    }

    /**
     * Return this events title.
     *
     * @return string The title of this event
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Is this event an all-day event?
     *
     * Since there are no holidays lasting only a few hours, this is always
     * true.
     *
     * @return boolean <code>true</code>
     */
    public function isAllDay()
    {
        return true;
    }

}
