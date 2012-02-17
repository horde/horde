<?php
/**
 * Kronolith_Calendar_Resource defines an API for single internal resource
 * calendars.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_Resource extends Kronolith_Calendar
{
    /**
     * The share of this calendar.
     *
     * @var Kronolith_Resource_Single
     */
    protected $_resource;

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
        if (!isset($params['resource'])) {
            throw new BadMethodCallException('resource parameter is missing.');
        }
        if (!($params['resource'] instanceof Kronolith_Resource_Single)) {
            throw new InvalidArgumentException('resource parameter is not a Kronolith_Resource_Single object.');
        }
        parent::__construct($params);
    }

    /**
     * Returns the owner of this calendar.
     *
     * @return string  This calendar's owner.
     */
    public function owner()
    {
        // @TODO: what to return here? Resources do not currently have owners.
        return '';
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    public function name()
    {
        return $this->_resource->get('name');
    }

    /**
     * Returns the description of this calendar.
     *
     * @return string  This calendar's description.
     */
    public function description()
    {
        return $this->_resource->get('description');
    }

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for. Defaults
     *                             to the current user.
     * @param string $creator      An event creator, to check for creator
     *                             permissions.
     *
     * @return boolean  Whether the user has the permission on this calendar.
     */
    public function hasPermission($permission, $user = null, $creator = null)
    {
        return $this->_resource->hasPermission($user, $permission, $creator);
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    public function display()
    {
        // @TODO Will have to revisit this when resource management is
        // added to dynamic view.
        return false;
    }

}
