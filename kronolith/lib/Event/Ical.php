<?php
/**
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Event_Ical extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    protected $_calendarType = 'remote';

    public function fromDriver($vEvent)
    {
        $this->fromiCalendar($vEvent);
        $this->initialized = true;
        $this->stored = true;
    }

    public function toDriver()
    {
        return $this->toiCalendar();
    }

}
