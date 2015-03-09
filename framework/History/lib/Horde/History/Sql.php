<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * The Horde_History_Sql class provides a method of tracking changes in Horde
 * objects, stored in a SQL table.
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * @param string $auth  The current user.
     * @param Horde_Db_Adapter $db  The database connection.
     */
    public function __construct($auth, Horde_Db_Adapter $db)
    {
        parent::__construct($auth);
        $this->_db = $db;
    }

    /**
     * Returns the timestamp of the most recent change to $guid.
     *
     * @param string $guid    The name of the history entry to retrieve.
     * @param string $action  An action: 'add', 'modify', 'delete', etc.
     *
     * @return integer  The timestamp, or 0 if no matching entry is found.
     * @throws Horde_History_Exception
     */
    public function getActionTimestamp($guid, $action)
    {
        if (!is_string($guid) || !is_string($action)) {
            throw new InvalidArgumentException('$guid and $action need to be strings!');
        }

        try {
            $result = $this->_db->selectValue('SELECT MAX(history_ts) FROM horde_histories WHERE history_action = ? AND object_uid = ?', array($action, $guid));
        } catch (Horde_Db_Exception $e) {
            return 0;
        }

        return (int)$result;
    }

    /**
     * Logs an event to an item's history log.
     *
     * Any other details about the event are passed in $attributes.
     *
     * @param Horde_History_Log $history  The history item to add to.
     * @param array $attributes           The hash of name => value entries
     *                                    that describe this event.
     * @param boolean $replaceAction      If $attributes['action'] is already
     *                                    present in the item's history log,
     *                                    update that entry instead of creating
     *                                    a new one.
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
                    $values[] = $this->_nextModSeq();
                    $values[] = $entry['id'];
                    try {
                        $this->_db->update(
                            'UPDATE horde_histories SET history_ts = ?,' .
                            ' history_who = ?,' .
                            ' history_desc = ?,' .
                            ' history_extra = ?,' .
                            ' history_modseq = ? WHERE history_id = ?', $values
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
                isset($attributes['action']) ? $attributes['action'] : null,
                $this->_nextModSeq()
            );

            unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

            $values[] = $attributes
                ? serialize($attributes)
                : null;

            try {
                $this->_db->insert(
                    'INSERT INTO horde_histories (object_uid, history_ts, history_who, history_desc, history_action, history_modseq, history_extra)' .
                    ' VALUES (?, ?, ?, ?, ?, ?, ?)', $values
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
     * @param string $guid  The name of the history entry to retrieve.
     *
     * @return Horde_History_Log  A Horde_History_Log object.
     * @throws Horde_History_Exception
     */
    public function _getHistory($guid)
    {
        try {
            $rows = $this->_db->selectAll('SELECT * FROM horde_histories WHERE object_uid = ?', array($guid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }
        return new Horde_History_Log($guid, $rows);
    }

    /**
     * Finds history objects by timestamp, and optionally filtered on other
     * fields as well.
     *
     * Note: For BC reasons, the results are returned keyed by the object UID,
     *       with a (fairly useless) history_id as the value. @todo This
     *       should be changed for Horde 6.
     *
     * @param string $cmp     The comparison operator (<, >, <=, >=, or =) to
     *                        check the timestamps with.
     * @param integer $ts     The timestamp to compare against.
     * @param array $filters  An array of additional (ANDed) criteria.
     *                        Each array value should be an array with 3
     *                        entries:
     *                        - field: the history field being compared (i.e.
     *                          'action').
     *                        - op: the operator to compare this field with.
     *                        - value: the value to check for (i.e. 'add').
     * @param string $parent  The parent history to start searching at. If
     *                        non-empty, will be searched for with a LIKE
     *                        '$parent:%' clause.
     *
     * @return array  An array of history object ids that have had at least one
     *                match for the given $filters. Will return empty array if
     *                none matched the criteria. If the same GUID has multiple
     *                matches withing the range requested, there is no
     *                guarantee which entry will be returned.
     * @throws Horde_History_Exception
     */
    public function _getByTimestamp($cmp, $ts, array $filters = array(),
                                    $parent = null)
    {
        /* Build the timestamp test. */
        $where = array("history_ts $cmp $ts");

        /* Add additional filters, if there are any. */
        try {
            if ($filters) {
                foreach ($filters as $filter) {
                    $where[] = 'history_' . $filter['field'] . ' ' . $filter['op'] . ' ' . $this->_db->quote($filter['value']);
                }
            }

            if ($parent) {
                $where[] = 'object_uid LIKE ' . $this->_db->quote($parent . ':%');
            }

            return $this->_db->selectAssoc('SELECT DISTINCT object_uid, history_id FROM horde_histories WHERE ' . implode(' AND ', $where));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     * Returns history objects with changes during a modseq interval, and
     * optionally filtered on other fields as well.
     *
     * Note: For BC reasons, the results are returned keyed by the object UID,
     *       with a (fairly useless) history_id as the value. @todo This
     *       should be changed for Horde 6.
     *
     * @param integer $start  The (exclusive) start of the modseq range.
     * @param integer $end    The (inclusive) end of the modseq range.
     * @param array $filters  An array of additional (ANDed) criteria.
     *                        Each array value should be an array with 3
     *                        entries:
     *                        - field: the history field being compared (i.e.
     *                          'action').
     *                        - op: the operator to compare this field with.
     *                        - value: the value to check for (i.e. 'add').
     * @param string $parent  The parent history to start searching at. If
     *                        non-empty, will be searched for with a LIKE
     *                        '$parent:%' clause.
     *
     * @return array  An array of history object ids that have had at least one
     *                match for the given $filters. Will return empty array if
     *                none matched the criteria. If the same GUID has multiple
     *                matches withing the range requested, there is no
     *                guarantee which entry will be returned.
     */
    protected function _getByModSeq($start, $end, $filters = array(), $parent = null)
    {
        // Build the modseq test.
        $where = array(
            sprintf(
                'history_modseq > %d AND history_modseq <= %d',
                $start,
                $end)
        );

        // Add additional filters, if there are any.
        try {
            if ($filters) {
                foreach ($filters as $filter) {
                    $where[] = 'history_' . $filter['field'] . ' ' . $filter['op'] . ' ' . $this->_db->quote($filter['value']);
                }
            }

            if ($parent) {
                $where[] = 'object_uid LIKE ' . $this->_db->quote($parent . ':%');
            }

            return $this->_db->selectAssoc('SELECT DISTINCT object_uid, history_id FROM horde_histories WHERE ' . implode(' AND ', $where));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }
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
        try {
            foreach ($names as $name) {
                $ids[] = $this->_db->quote($name);
                if ($this->_cache) {
                    $this->_cache->expire('horde:history:' . $name);
                }
            }

            $this->_db->delete('DELETE FROM horde_histories WHERE object_uid IN (' . implode(',', $ids) . ')');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     * Returns the current value of the modseq.
     *
     * We take the MAX of the horde_histories table instead of the value of the
     * horde_histories_modseq table to ensure we never miss an entry if we
     * query the history system between the time we call nextModSeq() and the
     * time the new entry is written.
     *
     * @param string $parent  Restrict to entries a specific parent.
     *
     * @return integer|boolean  The highest used modseq value, false if no
     *                          history.
     * @throws Horde_History_Exception
     */
    public function getHighestModSeq($parent = null)
    {
        try {
            $sql = 'SELECT history_modseq FROM horde_histories';
            if (!empty($parent)) {
                $sql .= ' WHERE object_uid LIKE ' . $this->_db->quote($parent . ':%');
            }
            $sql .= ' ORDER BY history_modseq DESC';
            $sql = $this->_db->addLimitOffset($sql, array('limit' => 1));

            $modseq = $this->_db->selectValue($sql);
            if (is_null($modseq) || $modseq === false) {
                $modseq = $this->_db->selectValue('SELECT MAX(history_modseq) FROM horde_histories_modseq');
                if (!empty($modseq)) {
                    return $modseq;
                } else {
                    return false;
                }
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }
        return $modseq;
    }

    /**
     * Increments, and returns, the modseq value.
     *
     * @return integer  The new modseq value.
     * @throws Horde_History_Exception
     */
    protected function _nextModSeq()
    {
        try {
            $result = $this->_db->insert('INSERT INTO horde_histories_modseq (history_modseqempty) VALUES(0)');
            // Don't completely empty the table to prevent sequence from being reset
            // when using certain RDBMS, like postgres (see Bug #13876).
            $this->_db->delete('DELETE FROM horde_histories_modseq WHERE history_modseq < (? - 25)', array($result));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }

        return $result;
    }

    /**
     * Returns the latest entry of $guid.
     *
     * @param string  $guid    The name of the history entry to retrieve.
     * @param boolean $use_ts  If false we use the 'modseq' field to determine
     *                         the latest entry. If true we use the timestamp
     *                         instead of modseq to determine the latest entry.
     *                         Note: Only 'modseq' can give a definitive
     *                         answer.
     *
     * @return array|boolean  The latest history entry, or false if $guid does
     *                        not exist.
     * @throws Horde_History_Exception
     * @since 2.2.0
     */
    public function getLatestEntry($guid, $use_ts = false)
    {
        $query = 'SELECT * from horde_histories WHERE object_uid = ? ORDER BY ';
        if ($use_ts) {
            $query .= 'history_ts ';
        } else {
            $query .= 'history_modseq ';
        }
        $query .= 'DESC LIMIT 1';

        try {
            $row = $this->_db->selectOne($query, array($guid));
            if (empty($row['history_id'])) {
                return false;
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_History_Exception($e);
        }

        $log = new Horde_History_Log($guid, array($row));
        return $log[0];
    }

}
