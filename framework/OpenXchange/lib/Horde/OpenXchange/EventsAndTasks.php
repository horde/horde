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
 * Horde_OpenXchange_EventsAndTasks is the base class for the events and tasks
 * storage of an Open-Xchange server.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   OpenXchange
 */
abstract class Horde_OpenXchange_EventsAndTasks extends Horde_OpenXchange_Base
{
    /**
     * Column IDs mapped to column names.
     *
     * @var array
     */
    protected $_columns = array(
        1 => 'id',
        20 => 'folder_id',
        100 => 'categories',
        101 => 'private',
        200 => 'title',
        201 => 'start',
        202 => 'end',
        203 => 'description',
        204 => 'alarm',
        209 => 'recur_type',
        212 => 'recur_days',
        213 => 'recur_day_in_month',
        214 => 'recur_month',
        215 => 'recur_interval',
        216 => 'recur_end',
        220 => 'attendees',
        221 => 'users',
        222 => 'recur_count',
        223 => 'uid',
    );

    /**
     * Returns a list of events or tasks.
     *
     * @param integer $folder    A folder ID. If empty, returns objects of all
     *                           visible resources.
     * @param Horde_Date $start  Start date, defaults to epoch.
     * @param Horde_Date $end    End date, defaults to maximum date possible.
     *
     * @return array  List of object hashes.
     * @throws Horde_OpenXchange_Exception.
     */
    protected function _listObjects($folder = null, $start = null, $end = null)
    {
        $this->_login();

        $data = array(
            'session' => $this->_session,
            'columns' => implode(',', array_keys($this->_columns)),
            'start' => $start ? $start->timestamp() * 1000 : 0,
            'end' => $end ? $end->timestamp() * 1000 : PHP_INT_MAX,
            // Doesn't work for some reason.
            'recurrence_master' => true,
        );
        if ($folder) {
            $data['folder'] = $folder;
        }

        $response = $this->_request(
            'GET',
            $this->_folderType,
            array('action' => 'all'),
            $data
        );

        $events = array();
        foreach ($response['data'] as $event) {
            $map = array();
            foreach (array_values($this->_columns) as $key => $column) {
                $map[$column] = $event[$key];
            }
            $events[] = $map;
        }

        return $events;
    }

    /**
     * Returns an event or task.
     *
     * @param integer $folder  A folder ID.
     * @param integer $id      An object ID.
     *
     * @return array  The object hash.
     * @throws Horde_OpenXchange_Exception.
     */
    protected function _getObject($folder, $id)
    {
        $this->_login();

        $data = array(
            'session' => $this->_session,
            'id' => $id,
            'folder' => $folder,
        );

        $response = $this->_request(
            'GET',
            $this->_folderType,
            array('action' => 'get'),
            $data
        );

        return $response['data'];
    }
}
