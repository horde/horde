<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */

/**
 * Base class for Kronolith resources.
 *
 * Partially presents a Horde_Share_Object interface.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Resource_Base
{
    /**
     * Instance copy of parameters.
     *
     * Contains:
     *   - name:          Display name of resource.
     *   - calendar:      The calendar associated with this resource.
     *   - description:   Resource description.
     *   - email:         An email address for the resource. (Currently not
     *                    used)
     *   - members:       Member resources, if this is a group.
     *   - response_type: A RESPONSETYPE_* constant
     *
     * @var array
     */
    protected $_params = array();

    /**
     *
     * @var Horde_Share_Object
     */
    protected $_share;

    /**
     * Resource's internal id
     *
     * @var integer
     */
    protected $_id = '';

    /**
     * Cache the lock of this resource. If not locked, is false.
     *
     * @var boolean|integer
     */
    protected $_lock;

    /**
     * Const'r
     *
     * @param array $params
     *
     * @return Kronolith_Resource_Base
     */
    public function __construct(array $params = array())
    {
        $this->_share = $params['share'];
        $this->_id = $this->_share->getId();
    }

    /**
     * Locks the resource.
     *
     * @return boolean  True if lock succeeded, otherwise false.
     */
    public function lock()
    {
        $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        $principle = 'calendar/' . $this->_id;
        $this->_lock = $locks->setLock(
            $GLOBALS['registry']->getAuth(),
            'kronolith',
            $principle, 5, Horde_Lock::TYPE_EXCLUSIVE);

        return !empty($this->_lock);
    }

    /**
     * Remove a previous lock.
     *
     */
    public function unlock()
    {
        if ($this->_lock) {
            $GLOBALS['injector']->getInstance('Horde_Lock')
                ->clearLock($this->_lock);
        }
        $this->_lock = false;
    }

    /**
     * Obtain the resource's internal identifier.
     *
     * @return string The id.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Allow setting of properties
     *
     * @param string $property  The property to set
     * @param mixed $value      The value to set to
     *
     * @return void
     */
    public function set($property, $value)
    {
        if ($property == 'members') {
            $value = serialize($value);
        }
        $this->_share->set($property, $value);
    }

    /**
     * Return permission for the specified user for this Resource.
     *
     * @param string $user         The user to check for.
     * @param integer $permission  The permission to check.
     * @param $restrict
     *
     * @return boolean
     */
    public function hasPermission($user, $permission = Horde_Perms::READ, $restrict = null)
    {
        if ($user === null) {
            $user = $GLOBALS['registry']->getAuth();
        }
        return $this->_share->hasPermission($user, $permission);
    }

    public function getPermission()
    {
        return $this->_share->getPermission();
    }

    public function setPermission($perm)
    {
        $this->_share->setPermission($perm);
    }

    /**
     * Implemented to stand in as a share object.
     *
     * @param string $property  The property to get
     *
     * @return mixed  The value of $property
     */
    public function get($property)
    {
        $property = str_replace('resource_', '', $property);
        if ($property == 'type' && empty($this->_params['type'])) {
            return ($this instanceof Kronolith_Resource_Single) ? 'Single' : 'Group';
        }
        $value = $this->_share->get($property);

        return $property == 'members'
            ? unserialize($value)
            : $value;
    }

    /**
     * Save resource to storage.
     *
     * @return Kronolith_Resource_Base
     */
    public function save()
    {
        return $this->getDriver()->save($this);

    }

    public function share()
    {
        return $this->_share;
    }

    /**
     * Get a storage driver instance for the resource.
     *
     * @return Kronolith_Driver_Resource_* object.
     */
    public function getDriver()
    {
        if (!$this->get('calendar')) {
            return Kronolith::getDriver('Resource');
        } else {
            return Kronolith::getDriver('Resource', $this->get('calendar'));
        }
    }

    /**
     * Check availability and return an appropriate Kronolith response code.
     *
     * @param Kronolith_Event $event  The event to check on
     *
     * @return integer Kronolith::RESPONSE* constant
     */
    public function getResponse(Kronolith_Event $event)
    {
        switch($this->getResponseType()) {
        case Kronolith_Resource::RESPONSETYPE_ALWAYS_ACCEPT:
            return Kronolith::RESPONSE_ACCEPTED;
        case Kronolith_Resource::RESPONSETYPE_AUTO:
            if ($this->isFree($event)) {
                return Kronolith::RESPONSE_ACCEPTED;
            } else {
                return Kronolith::RESPONSE_DECLINED;
            }
        case Kronolith_Resource::RESPONSETYPE_ALWAYS_DECLINE:
            return Kronolith::RESPONSE_DECLINED;
        case Kronolith_Resource::RESPONSETYPE_NONE:
        case Kronolith_Resource::RESPONSETYPE_MANUAL:
            return Kronolith::RESPONSE_NONE;
        }
    }

    /**
     * Return this resource's parameters in a hash.
     *
     * @return array  A hash suitable for JSON encoding.
     */
    public function toJson()
    {
        return $this->_params;
    }

    /**
     * Determine if event is free for specified time
     *
     * @param Kronolith_Event $event  The event we want to check the
     *                                resource's availability for.
     *
     * @return boolean  True if the resource is free, false if not.
     */
    abstract public function isFree(Kronolith_Event $event);

    /**
     * Adds $event to this resource's calendar - thus blocking the time
     * for any other event.
     *
     * @param Kronolith_Event $event  The event to add to this resource's
     *                                calendar, thus blocking it's availability.
     *
     * @throws Kronolith_Exception
     */
    abstract public function addEvent(Kronolith_Event $event);

    /**
     * Remove this event from resource's calendar
     *
     * @param Kronolith_Event $event  The event to remove from the resource's
     *                                calendar.
     */
    abstract public function removeEvent(Kronolith_Event $event);

    /**
     * Obtain the freebusy information for this resource.  Takes into account
     * if this is a group of resources or not. (Returns the cumulative FB info
     * for all the resources in the group.
     *
     * @param integer $startstamp  The starting timestamp of the fb interval.
     * @param integer $endstamp    The ending timestamp of the fb interval.
     * @param boolean $asObject    Return the fb info as an object?
     * @param boolean $json        Return the fb info as JSON?
     *
     * @return mixed string|Horde_Icalendar_Vfreebusy  The Freebusy object or
     *                                                 the iCalendar text.
     */
    abstract public function getFreeBusy($startstamp = null, $endstamp = null, $asObject = false, $json = false);

    /**
     * Get ResponseType for this resource.
     *
     * @return integer  The response type for this resource. A
     *                  Kronolith_Resource::RESPONSE_TYPE_* constant.
     */
    abstract public function getResponseType();

}
