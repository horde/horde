<?php
/**
 * Kronolith_Calendar_Internal defines an API for single internal (share)
 * calendars.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_Internal extends Kronolith_Calendar
{
    /**
     * The share of this calendar.
     *
     * @var Horde_Share_Object
     */
    protected $_share;

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
        if (!isset($params['share'])) {
            throw new BadMethodCallException('share parameter is missing');
        }
        if (!($params['share'] instanceof Horde_Share_Object)) {
            throw new InvalidArgumentException('share parameter is not a Horde_Share_Object');
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
        return $this->_share->get('owner');
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    public function name()
    {
        return $this->_share->get('name');
    }

    /**
     * Returns the description of this calendar.
     *
     * @return string  This calendar's description.
     */
    public function description()
    {
        return $this->_share->get('description');
    }

    /**
     * Returns the background color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function background()
    {
        $color = $this->_share->get('color');
        return empty($color) ? parent::background() : $color;
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
        if ($user === null) {
            $user = $GLOBALS['registry']->getAuth();
        }
        return $this->_share->hasPermission($user, $permission, $creator);
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    public function display()
    {
        return $this->owner() == $GLOBALS['registry']->getAuth() ||
            empty($GLOBALS['conf']['share']['hidden']) ||
            in_array($this->_share->getName(), $GLOBALS['display_calendars']);
    }

    /**
     * Returns the share of this calendar.
     *
     * @return Horde_Share_Object  This calendar's share.
     */
    public function share()
    {
        return $this->_share;
    }
}
