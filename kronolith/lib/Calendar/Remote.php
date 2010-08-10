<?php
/**
 * Kronolith_Calendar_Remote defines an API for single external WebDAV or
 * CalDAV calendars.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_Remote extends Kronolith_Calendar
{
    /**
     * The URL of this calendar.
     *
     * @var string
     */
    protected $_url;

    /**
     * The name of this calendar.
     *
     * @var string
     */
    protected $_name;

    /**
     * The description of this calendar.
     *
     * @var string
     */
    protected $_desc = '';

    /**
     * The HTTP user name for this calendar.
     *
     * @var string
     */
    protected $_user;

    /**
     * The HTTP password for this calendar.
     *
     * @var string
     */
    protected $_password;

    /**
     * The color of this calendar.
     *
     * @var string
     */
    protected $_color;

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
        if (!isset($params['url'])) {
            throw new BadMethodCallException('url parameter is missing');
        }
        if (!isset($params['name'])) {
            throw new BadMethodCallException('name parameter is missing');
        }
        $key = $GLOBALS['registry']->getAuthCredential('password');
        if ($key) {
            $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
            if (!empty($params['user'])) {
                $params['user'] = $secret->read($key, base64_decode($params['user']));
            }
            if (!empty($params['password'])) {
                $params['password'] = $secret->read($key, base64_decode($params['password']));
            }
        }
        parent::__construct($params);
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Returns the description of this calendar.
     *
     * @return string  This calendar's description.
     */
    public function description()
    {
        return $this->_desc;
    }

    /**
     * Returns the background color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function background()
    {
        return empty($this->_color) ? parent::background() : $this->_color;
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
        return Kronolith::getDriver('Ical', $this->_url)->getPermission() & $permission;
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    public function display()
    {
        return true;
    }

    /**
     * Returns the URL of this calendar.
     *
     * @return string  This calendar's URL.
     */
    public function url()
    {
        return $this->_url;
    }

    /**
     * Returns the authentication credentials for this calendar.
     *
     * @return array  This calendar's credentials.
     */
    public function credentials()
    {
        if (!empty($this->_user)) {
            return array('user' => $this->_user, 'password' => $this->_password);
        }
        return array();
    }
}
