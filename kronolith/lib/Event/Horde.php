<?php
/**
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Event_Horde extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    protected $_calendarType = 'external';

    /**
     * The API (application) of this event.
     *
     * @var string
     */
    private $_api;

    /**
     * Constructor.
     *
     * @param Kronolith_Driver $driver  The backend driver that this event is
     *                                  stored in.
     * @param mixed $eventObject        Backend specific event object
     *                                  that this will represent.
     */
    public function __construct($driver, $eventObject = null)
    {
        $this->_api = $driver->api;
        parent::__construct($driver, $eventObject);
    }

    public function fromDriver($event)
    {
        $eventStart = new Horde_Date($event['start']);
        $eventEnd = new Horde_Date($event['end']);
        $this->eventID = '_' . $this->_api . $event['id'];
        $this->external = $this->_api;
        $this->external_params = $event['params'];
        $this->external_icon = !empty($event['icon']) ? $event['icon'] : null;
        $this->title = $event['title'];
        $this->description = isset($event['description']) ? $event['description'] : '';
        $this->start = $eventStart;
        $this->end = $eventEnd;
        $this->status = Kronolith::STATUS_FREE;

        if (isset($event['recurrence'])) {
            $recurrence = new Horde_Date_Recurrence($eventStart);

            $recurrence->setRecurType($event['recurrence']['type']);
            if (isset($event['recurrence']['end'])) {
                $recurrence->setRecurEnd($event['recurrence']['end']);
            }
            if (isset($event['recurrence']['interval'])) {
                $recurrence->setRecurInterval($event['recurrence']['interval']);
            }
            if (isset($event['recurrence']['count'])) {
                $recurrence->setRecurCount($event['recurrence']['count']);
            }
            if (isset($event['recurrence']['days'])) {
                $recurrence->setRecurOnDay($event['recurrence']['days']);
            }
            if (isset($event['recurrence']['exceptions'])) {
                foreach ($event['recurrence']['exceptions'] as $exception) {
                    $recurrence->addException(new Horde_Date($exception));
                }
            }
            $this->recurrence = $recurrence;
        }

        $this->initialized = true;
        $this->stored = true;
    }

    public function toJson($allDay = null, $full = false, $time_format = 'H:i')
    {
        $json = parent::toJson($allDay, $full, $time_format);
        $json->icn = $this->external_icon;
        // @TODO: What is expected for external calendar links? This is currently
        // broken in the UI.
        //$json->link = $this->getLink();
        return $json;
    }

}
