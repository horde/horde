<?php
/**
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
     * The link to edit this event.
     *
     * @var string
     */
    protected $_editLink;

    /**
     * The link to delete this event.
     *
     * @var string
     */
    protected $_deleteLink;

    /**
     * The link to this event in the ajax interface.
     *
     * @var string
     */
    protected $_ajaxLink;

    /**
     * Any parameters to identify the object in the other Horde application.
     *
     * @var array
     */
    protected $_params;

    /**
     * The event's owner.
     *
     * @var string
     */
    protected $_owner;

    /**
     * A bitmask of permissions the current user has on this object.
     *
     * @var integer
     */
    protected $_permissions;

    /**
     * Whether this event has a variable length.
     *
     * @boolean
     */
    protected $_variableLength;

    /**
     * Time object hash.
     *
     * @array
     */
    public $timeobject;

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

    /**
     * Imports a backend specific event object.
     *
     * @param array $event  Backend specific event object that this object
     *                      will represent.
     */
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
        $this->_editLink = !empty($event['edit_link']) ? $event['edit_link'] : null;
        $this->_deleteLink = !empty($event['delete_link']) ? $event['delete_link'] : null;
        $this->_ajaxLink = !empty($event['ajax_link']) ? $event['ajax_link'] : null;
        $this->_backgroundColor = Kronolith::backgroundColor($event);
        $this->_foregroundColor = Kronolith::foregroundColor($event);

        if (isset($event['recurrence'])) {
            $recurrence = new Horde_Date_Recurrence($eventStart);

            $recurrence->setRecurType($event['recurrence']['type']);
            if (isset($event['recurrence']['end'])) {
                $recurrence->setRecurEnd(new Horde_Date($event['recurrence']['end']));
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
                    $recurrence->addException($exception);
                }
            }
            $this->recurrence = $recurrence;
        }

        if (isset($event['owner'])) {
            $this->_owner = $event['owner'];
        }
        if (isset($event['permissions'])) {
            $this->_permissions = $event['permissions'];
        }
        if (isset($event['variable_length'])) {
            $this->_variableLength = $event['variable_length'];
        }

        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this event to be saved to the backend.
     */
    public function toDriver()
    {
        $this->timeobject = array(
            'id' => substr($this->id, strlen($this->_api) + 1),
            'icon' => $this->icon,
            'title' => $this->title,
            'description' => $this->description,
            'start' => $this->start->format('Y-m-d\TH:i:s'),
            'end' => $this->end->format('Y-m-d\TH:i:s'),
            'params' => $this->_params,
            'link' => $this->_link,
            'ajax_link' => $this->_ajaxLink,
            'permissions' => $this->_permissions,
            'variable_length' => $this->_variableLength);
        if ($this->recurs()) {
            $this->timeobject['recurrence'] = array('type' => $this->recurrence->getRecurType());
            if ($end = $this->recurrence->getRecurEnd()) {
                $this->timeobject['recurrence']['end'] = $end->format('Y-m-d\TH:i:s');
            }
            if ($interval = $this->recurrence->getRecurInterval()) {
                $this->timeobject['recurrence']['interval'] = $interval;
            }
            if ($count = $this->recurrence->getRecurCount()) {
                $this->timeobject['recurrence']['count'] = $count;
            }
            if ($days = $this->recurrence->getRecurOnDays()) {
                $this->timeobject['recurrence']['days'] = $days;
            }
            if ($count = $this->recurrence->getRecurCount()) {
                $this->timeobject['recurrence']['count'] = $count;
            }
            if ($exceptions = $this->recurrence->getExceptions()) {
                $this->timeobject['recurrence']['exceptions'] = $exceptions;
            }
        }
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
        if ($user === null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if (isset($this->_owner) && $this->_owner == $user) {
            return true;
        }

        if (isset($this->_permissions)) {
            return (bool)($this->_permissions & $permission);
        }

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
    public function getViewUrl($params = array(), $full = false, $encoded = true)
    {
        if (empty($this->_link)) {
            return null;
        }
        $url = clone $this->_link;
        return $url->setRaw(!$encoded);
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getEditUrl($params = array(), $full = false)
    {
        if (empty($this->_editLink)) {
            return null;
        }
        $url = clone $this->_editLink;
        if (isset($params['url'])) {
            $url->add('url', $params['url']);
        }
        return $url->setRaw($full);
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getDeleteUrl($params = array(), $full = false)
    {
        if (empty($this->_deleteLink)) {
            return null;
        }
        $url = clone $this->_deleteLink;
        if (isset($params['url'])) {
            $url->add('url', $params['url']);
        }
        return $url->setRaw($full);
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
        if ($this->_ajaxLink) {
            $json->aj = $this->_ajaxLink;
        } else {
            $json->ln = (string)$this->getViewUrl(array(), true, false);
        }
        if (isset($this->_variableLength)) {
            $json->vl = $this->_variableLength;
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
