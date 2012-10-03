<?php
/**
 * Kronolith_Calendar_ResourceGroup defines an API for single internal
 * resourcegroup calendars.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_ResourceGroup extends Kronolith_Calendar
{
    /**
     * The share of this calendar.
     *
     * @var Kronolith_Resource_Group
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
        if (!($params['resource'] instanceof Kronolith_Resource_Group)) {
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
        return in_array($this->_resource->get('calendar'), $GLOBALS['display_calendars']);
    }

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        $owner = $GLOBALS['registry']->isAdmin();
        $hash = parent::toHash();

        $hash['id']    = $this->_resource->getId();
        $hash['name']  = $this->name();
        $hash['owner'] = $owner;
        $hash['show']  = $this->display();
        $hash['edit']  = $this->hasPermission(Horde_Perms::EDIT);
        $hash['sub']   = null;
        $hash['feed']  = null;
        $hash['embed'] = null;
        $hash['members'] = $this->_resource->get('members');
        if ($owner) {
            $hash['perms'] = array(
                'type' => 'matrix',
                'default' => 0,
                'guest' => 0,
                'creator' => 0);
        }
        return $hash;
    }

}
