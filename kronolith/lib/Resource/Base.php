<?php
/**
 * Base class for Kronolith resources. Partially presents a Horde_Share_Object
 * interface.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Resource_Base
{
    /**
     * Instance copy of parameters
     * Contains:
     *<pre>
     *   -:name          - Display name of resource.
     *   -:calendar      - The calendar associated with this resource.
     *   -:description   - Resource description.
     *   -:email         - An email address for the resource. (Currently not used)
     *   -:members       - Member resources, if this is a group.
     *   -:response_type - A RESPONSETYPE_* constant
     *</pre>
     * @var array
     */
    protected $_params = array();

    /**
     * Resource's internal id
     *
     * @var integer
     */
    protected $_id = '';

    /**
     * Const'r
     *
     * @param array $params
     *
     * @return Kronolith_Resource_Base
     */
    public function __construct(array $params = array())
    {
        if (!empty($params['id'])) {
            // Existing resource
            $this->_id = $params['id'];
        }

        // Names are required.
        if (empty($params['name'])) {
            throw new Horde_Exception('Required \'name\' attribute missing from resource calendar');
        }
        $this->_params = array_merge(
            array('description' => '',
                  'response_type' => Kronolith_Resource::RESPONSETYPE_MANUAL,
                  'members' => '',
                  'calendar' => '',
                  'email' => ''
            ),
            $params
        );
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
        $this->_params[$property] = $value;
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
        if (($permission & (Horde_Perms::EDIT | Horde_Perms::DELETE)) &&
            !$GLOBALS['registry']->isAdmin()) {
            return false;
        }

        return true;
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
        if (!array_key_exists($property, $this->_params)) {
            throw new Horde_Exception(sprintf('The property \'%s\' does not exist', $property));
        }
        return $this->_params[$property];
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
     * Sets the current resource's id. Must not be an existing resource.
     *
     * @param integer $id  The id for this resource
     *
     * @throws Kronolith_Exception
     */
    abstract public function setId($id);

    /**
     * Get ResponseType for this resource.
     *
     * @return integer  The response type for this resource. A
     *                  Kronolith_Resource::RESPONSE_TYPE_* constant.
     */
    abstract public function getResponseType();

}
