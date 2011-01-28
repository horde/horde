<?php
/**
 * A SQL based history driver.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * The Horde_History_Sql:: class provides a method of tracking changes in
 * Horde objects, stored in a SQL table.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */
class Horde_History_Sql extends Horde_History
{
    /**
     * Horde_Db_Adapter instance to manage the history.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param Horde_Db_Adapter $db  The database connection.
     *
     * @throws Horde_History_Exception
     */
    public function __construct(Horde_Db_Adapter $db)
    {
        $this->_db = $db;
    }

    /**
     * Gets the timestamp of the most recent change to $guid.
     *
     * @param string $guid   The name of the history entry to retrieve.
     * @param string $action An action: 'add', 'modify', 'delete', etc.
     *
     * @return integer  The timestamp, or 0 if no matching entry is found.
     *
     * @throws Horde_History_Exception If the input parameters are not of type string.
     */
    public function getActionTimestamp($guid, $action)
    {
        if (!is_string($guid) || !is_string($action)) {
            throw new Horde_History_Exception('$guid and $action need to be strings!');
        }

        try {
            $result = $this->_db->selectValue('SELECT MAX(history_ts) FROM horde_histories WHERE history_action = ? AND object_uid = ?', array($action, $guid));
        } catch (Horde_Db_Exception $e) {
            return 0;
        }

        return (int)$result;
    }

    /**
     * Logs an event to an item's history log. Any other details about the
     * event are passed in $attributes.
     *
     * @param Horde_History_Log $history       The history item to add to.
     * @param array             $attributes    The hash of name => value
     *                                         entries that describe this
     *                                         event.
     * @param boolean           $replaceAction If $attributes['action'] is
     *                                         already present in the item's
     *                                         history log, update that entry
     *                                         instead of creating a new one.
     *
     * @throws Horde_History_Exception
     */
    protected function _log(Horde_History_Log $history, array $attributes,
                            $replaceAction = false)
    {
        /* If we want to replace an entry with the same action, try and find
         * one. Track whether or not we succeed in $done, so we know whether or
         * not to add the entry later. */
        $done = false;
        if ($replaceAction && !empty($attributes['action'])) {
            foreach ($history as $entry) {
                if (!empty($entry['action']) &&
                    $entry['action'] == $attributes['action']) {
                    $values = array(
                        $attributes['ts'],
                        $attributes['who'],
                        isset($attributes['desc']) ? $attributes['desc'] : null
                    );

                    unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

                    $values[] = $attributes
                        ? serialize($attributes)
                        : null;
                    $values[] = $entry['id'];

                    try {
                        $r = $this->_db->update(
                            'UPDATE horde_histories SET history_ts = ?,' .
                            ' history_who = ?,' .
                            ' history_desc = ?,' .
                            ' history_extra = ? WHERE history_id = ?', $values
                        );
                    } catch (Horde_Db_Exception $e) {
                        throw new Horde_History_Exception($e);
                    }

                    $done = true;
                    break;
                }
            }
        }

        /* If we're not replacing by action, or if we didn't find an entry to
         * replace, insert a new row. */
        if (!$done) {
            $values = array(
                $history->uid,
                $attributes['ts'],
                $attributes['who'],
                isset($attributes['desc']) ? $attributes['desc'] : null,
                isset($attributes['action']) ? $attributes['action'] : null
            );

            unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

            $values[] = $attributes
                ? serialize($attributes)
                : null;

            try {
                $this->_db->insert(
                    'INSERT INTO horde_histories (object_uid, history_ts, history_who, history_desc, history_action, history_extra)' .
                    ' VALUES (?, ?, ?, ?, ?, ?)', $values
                );
            } catch (Horde_Db_Exception $e) {
                throw new Horde_History_Exception($e);
            }
        }
    }

    /**
     * Returns a Horde_History_Log corresponding to the named history entry,
     * with the data retrieved appropriately.
     *
     * @param string $guid The name of the history entry to retrieve.
     *
     * @return Horde_History_Log  A Horde_History_Log object.
     *
     * @throws Horde_History_Exception
     */
    public function _getHistory($guid)
    {
        $rows = $this->_db->selectAll('SELECT * FROM horde_histories WHERE object_uid = ?', array($guid));
        return new Horde_History_Log($guid, $rows);
    }

    /**
     * Finds history objects by timestamp, and optionally filter on other
     * fields as well.
     *
     * @param string  $cmp     The comparison operator (<, >, <=, >=, or =) to
     *                         check the timestamps with.
     * @param integer $ts      The timestamp to compare against.
     * @param array   $filters An array of additional (ANDed) criteria.
     *                         Each array value should be an array with 3
     *                         entries:
     *                         - field: the history field being compared (i.e.
     *                           'action').
     *                         - op: the operator to compare this field with.
     *                         - value: the value to check for (i.e. 'add').
     * @param string  $parent  The parent history to start searching at. If
     *                         non-empty, will be searched for with a LIKE
     *                         '$parent:%' clause.
     *
     * @return array  An array of history object ids, or an empty array if
     *                none matched the criteria.
     *
     * @throws Horde_History_Exception
     */
    public function _getByTimestamp($cmp, $ts, array $filters = array(),
                                    $parent = null)
    {
        /* Build the timestamp test. */
        $where = array("history_ts $cmp $ts");

        /* Add additional filters, if there are any. */
        if ($filters) {
            foreach ($filters as $filter) {
                $where[] = 'history_' . $filter['field'] . ' ' . $filter['op'] . ' ' . $this->_db->quote($filter['value']);
            }
        }

        if ($parent) {
            $where[] = 'object_uid LIKE ' . $this->_db->quote($parent . ':%');
        }

        return $this->_db->selectAssoc('SELECT DISTINCT object_uid, history_id FROM horde_histories WHERE ' . implode(' AND ', $where));
    }

    /**
     * Removes one or more history entries by name.
     *
     * @param array $names  The history entries to remove.
     *
     * @throws Horde_History_Exception
     */
    public function removeByNames(array $names)
    {
        if (!count($names)) {
            return;
        }

        $ids = array();
        foreach ($names as $name) {
            $ids[] = $this->_db->quote($name);
        }

        $this->_db->delete('DELETE FROM horde_histories WHERE object_uid IN (' . implode(',', $ids) . ')');
    }
}
