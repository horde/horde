<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
    public $calendarType = 'external';

    /**
     * The API (application) of this event.
     *
     * @var string
     */
    protected $_api;

    /**
     * The link to this event.
     *
     * @var string
     */
    protected $_link;

    /**
     * The link to this event in the ajax interface.
     *
     * @var string
     */
    protected $_ajax_link;

    /**
     * Any parameters to identify the object in the other Horde application.
     *
     * @var array
     */
    protected $_params;

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
        $this->id = '_' . $this->_api . $event['id'];
        $this->icon = !empty($event['icon']) ? $event['icon'] : null;
        $this->title = $event['title'];
        $this->description = isset($event['description']) ? $event['description'] : '';
        $this->start = $eventStart;
        $this->end = $eventEnd;
        $this->status = Kronolith::STATUS_FREE;
        $this->_params = $event['params'];
        $this->_link = !empty($event['link']) ? $event['link'] : null;
        $this->_ajax_link = !empty($event['ajax_link']) ? $event['ajax_link'] : null;
        $this->_backgroundColor = Kronolith::backgroundColor($event);
        $this->_foregroundColor = Kronolith::foregroundColor($event);

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

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for.
     *
     * @return boolean
     */
    public function hasPermission($permission, $user = null)
    {
        switch ($permission) {
        case Horde_Perms::SHOW:
        case Horde_Perms::READ:
            return true;

        default:
            return false;
        }
    }

    /**
     * Returns the title of this event.
     *
     * @param string $user  The current user.
     *
     * @return string  The title of this event.
     */
    public function getTitle($user = null)
    {
        return !empty($this->title) ? $this->title : _("[Unnamed event]");
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getViewUrl($params = array(), $full = false)
    {
        if (empty($this->_link)) {
            return null;
        }
        $url = clone $this->_link;
        return $this->_link->setRaw($full);
    }

    /**
     * Returns a simple object suitable for json transport representing this
     * event.
     *
     * @param boolean $allDay      If not null, overrides whether the event is
     *                             an all-day event.
     * @param boolean $full        Whether to return all event details.
     * @param string $time_format  The date() format to use for time formatting.
     *
     * @return object  A simple object.
     */
    public function toJson($allDay = null, $full = false, $time_format = 'H:i')
    {
        $json = parent::toJson($allDay, $full, $time_format);
        if ($this->_ajax_link) {
            $json->aj = $this->_ajax_link;
        } else {
            $json->ln = (string)$this->getViewUrl(array(), true);
        }
        return $json;
    }

    /**
     * @return string  A tooltip for quick descriptions of this event.
     */
    public function getTooltip()
    {
        return Horde_String::wrap($this->description);
    }

}
