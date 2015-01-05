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
 * Horde_OpenXchange_Tasks is the interface class for the tasks storage
 * of an Open-Xchange server.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   OpenXchange
 */
class Horde_OpenXchange_Tasks extends Horde_OpenXchange_EventsAndTasks
{
    /**
     * Status: not started.
     */
    const STATUS_NOT_STARTED = 1;

    /**
     * Status: in progress.
     */
    const STATUS_IN_PROGRESS = 2;

    /**
     * Status: done.
     */
    const STATUS_DONE = 3;

    /**
     * Status: waiting.
     */
    const STATUS_WAITING = 4;

    /**
     * Status: deferred.
     */
    const STATUS_DEFERRED = 5;

    /**
     * Priority: high.
     */
    const PRIORITY_LOW = 1;

    /**
     * Priority: high.
     */
    const PRIORITY_MEDIUM = 2;

    /**
     * Priority: high.
     */
    const PRIORITY_HIGH = 3;

    /**
     * The folder category.
     *
     * @var string
     */
    protected $_folderType = 'tasks';

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
            300 => 'status',
            301 => 'percent',
            308 => 'duration',
            309 => 'priority',
            315 => 'completed',
        );
    }

    /**
     * Returns a list tasks.
     *
     * @param integer $folder    A folder ID. If empty, returns tasks of all
     *                           visible task lists.
     * @param Horde_Date $start  Start date, defaults to epoch.
     * @param Horde_Date $end    End date, defaults to maximum date possible.
     *
     * @return array  List of task hashes.
     * @throws Horde_OpenXchange_Exception.
     */
    public function listTasks($folder = null, $start = null, $end = null)
    {
        return $this->_listObjects($folder, $start, $end);
    }

    /**
     * Returns an task.
     *
     * @param integer $folder  A folder ID.
     * @param integer $id      An task ID.
     *
     * @return array  The task hash.
     * @throws Horde_OpenXchange_Exception.
     */
    public function getTask($folder, $id)
    {
        return $this->_getObject($folder, $id);
    }
}
