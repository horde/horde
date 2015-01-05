<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  OpenXchange
 */

/**
 * Horde_OpenXchange_Events is the interface class for the events storage
 * of an Open-Xchange server.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   OpenXchange
 */
class Horde_OpenXchange_Events extends Horde_OpenXchange_EventsAndTasks
{
    /**
     * The folder category.
     *
     * @var string
     */
    protected $_folderType = 'calendar';

    /**
     * Constructor.
     *
     * @param array $params  List of optional parameters:
     *                       - client: (Horde_Http_Client) An HTTP client.
     *                       - endpoint: (string) The URI of the OX API
     *                         endpoint.
     *                       - user: (string) Authentication user.
     *                       - password: (string) Authentication password.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        $this->_columns += array(
            206 => 'recur_id',
            207 => 'recur_position',
            210 => 'recur_change_exceptions',
            211 => 'recur_delete_exceptions',
            224 => 'organizer',
            225 => 'sequence',
            226 => 'confirmations',
            400 => 'location',
            401 => 'allday',
            402 => 'status',
            408 => 'timezone',
        );
    }

    /**
     * Returns a list events.
     *
     * @param integer $folder    A folder ID. If empty, returns events of all
     *                           visible calendars.
     * @param Horde_Date $start  Start date, defaults to epoch.
     * @param Horde_Date $end    End date, defaults to maximum date possible.
     *
     * @return array  List of event hashes.
     * @throws Horde_OpenXchange_Exception.
     */
    public function listEvents($folder = null, $start = null, $end = null)
    {
        return $this->_listObjects($folder, $start, $end);
    }

    /**
     * Returns an event.
     *
     * @param integer $folder  A folder ID.
     * @param integer $id      An event ID.
     *
     * @return array  The event hash.
     * @throws Horde_OpenXchange_Exception.
     */
    public function getEvent($folder, $id)
    {
        return $this->_getObject($folder, $id);
    }
}
