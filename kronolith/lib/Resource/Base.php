<?php
/**
 * Base class for Kronolith resources. Partially presents a Horde_Share_Object
 * interface.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Resource_Base
{
    /**
     * Instance copy of parameters
     *
     *   name        - Display name of resource.
     *   calendar    - The calendar associated with this resource.
     *   description -
     *   email       -
     *   response_type - a RESPONSETYPE_* constant
     *
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
     * @return Kronolith_Resource object
     */
    public function __construct($params = array())
    {
        if (!empty($params['id'])) {
            // Existing resource
            $this->_id = $params['id'];
        }

        // Names are required.
        if (empty($params['name'])) {
            throw new Horde_Exception('Required \'name\' attribute missing from resource calendar');
        }
        $this->_params = array_merge(array('description' => '',
                                           'response_type' => Kronolith_Resource::RESPONSETYPE_MANUAL,
                                           'members' => '',
                                           'calendar' => '',
                                           'email' => ''),
                                     $params);

    }

    /**
     * Obtain the resource's internal identifier.
     *
     * @return mixed The id.
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
     * @TODO: need to fine tune this
     *
     * @param $user
     * @param $permission
     * @param $restrict
     * @return unknown_type
     */
    public function hasPermission($user, $permission = Horde_Perms::READ, $restrict = null)
    {
        return $GLOBALS['registry']->isAdmin();
    }

    /**
     * Implemented to stand in as a share object.
     *
     * @param $property
     * @return unknown_type
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
     * @return Kronolith_Resource object
     * @throws Kronolith_Exception
     */
    public function save()
    {
        return $this->getDriver()->save($this);
    }

    /**
     * Get a storage driver instance for the resource. For now, just instantiate
     * it here, in future, probably inject it in the const'r.
     *
     * @return Kronolith_Driver_Resource
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
    public function getResponse($event)
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
     * Determine if event is free for specified time
     *
     * @param $startTime
     * @param $endTime
     * @return unknown_type
     */
    abstract public function isFree($event);

    /**
     * Adds $event to this resource's calendar - thus blocking the time
     * for any other event.
     *
     * @param $event
     * @return unknown_type
     */
    abstract public function addEvent($event);

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     * @return unknown_type
     */
    abstract public function removeEvent($event);

    /**
     * Obtain the freebusy information for this resource.  Takes into account
     * if this is a group of resources or not. (Returns the cumulative FB info
     * for all the resources in the group.
     * @return unknown_type
     */
    abstract public function getFreeBusy();

    /**
     * Sets the current resource's id. Must not be an existing resource.
     *
     * @param int $id  The id for this resource
     *
     * @return unknown_type
     */
    abstract public function setId($id);

    /**
     * Get ResponseType for this resource.
     * @return unknown_type
     */
    abstract public function getResponseType();

}
