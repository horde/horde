<?php
/**
 * Kronolith_Calendar_External defines an API for single timeobject calendars.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_External extends Kronolith_Calendar
{
    /**
     * The application of this timeobject source.
     *
     * @var string
     */
    protected $_api;

    /**
     * The ID of this timeobject source.
     *
     * @var string
     */
    protected $_id;

    /**
     * The name of this timeobject source.
     *
     * @var string
     */
    protected $_name;

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
        if (!isset($params['name'])) {
            throw new BadMethodCallException('name parameter is missing');
        }
        if (!isset($params['id'])) {
            throw new BadMethodCallException('id parameter is missing');
        }
        if (!isset($params['api'])) {
            throw new BadMethodCallException('api parameter is missing');
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
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    public function display()
    {
        return empty($GLOBALS['conf']['share']['hidden']) ||
            in_array($this->_api . '/' . $this->_id, $GLOBALS['display_external_calendars']);
    }

    /**
     * Returns the application of this calendar.
     *
     * @return string  This calendar's timeobject application.
     */
    public function api()
    {
        return $this->_api;
    }

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        $hash = parent::toHash();
        $hash['api']  = $GLOBALS['registry']->get('name', $GLOBALS['registry']->hasInterface($this->api()));
        $hash['show'] = in_array($this->_api . '/' . $this->_id, $GLOBALS['display_external_calendars']);
        return $hash;
    }
}
