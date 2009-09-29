<?php
/**
 * A sql based history driver.
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
 * The Horde_History_Sql:: class provides a method of tracking changes in Horde
 * objects, stored in a SQL table.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
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
     * Pointer to a DB instance to manage the history.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Constructor.
     *
     * @param DB_common $db The database connection.
     */
    public function __construct(DB_common $db)
    {
        $this->_write_db = $db;
        $this->_db       = $db;
    }

    /**
     * Set a separate read database connection if you want to split read and
     * write access to the db.
     *
     * @param DB_common $db The database connection.
     *
     * @return NULL
     */
    public function setReadDb(DB_common $db)
    {
        $this->_db = $db;
    }

    /**
     * Logs an event to an item's history log. Any other details about the event
     * are passed in $attributes.
     *
     * @param Horde_HistoryObject $history       The history item to add to.
     * @param array               $attributes    The hash of name => value entries
     *                                           that describe this event.
     * @param boolean             $replaceAction If $attributes['action'] is
     *                                           already present in the item's
     *                                           history log, update that entry
     *                                           instead of creating a new one.
     *
     * @return boolean True if the operation succeeded.
     *
     * @throws Horde_Exception
     */
    protected function _log(Horde_HistoryObject $history,
                            array $attributes,
                            $replaceAction = false)
    {
        /* If we want to replace an entry with the same action, try and find
         * one. Track whether or not we succeed in $done, so we know whether
         * or not to add the entry later. */
        $done = false;
        if ($replaceAction && !empty($attributes['action'])) {
            for ($i = 0, $count = count($history->data); $i < $count; ++$i) {
                if (!empty($history->data[$i]['action']) &&
                    $history->data[$i]['action'] == $attributes['action']) {
                    $values = array(
                        $attributes['ts'],
                        $attributes['who'],
                        isset($attributes['desc']) ? $attributes['desc'] : null
                    );

                    unset($attributes['ts'], $attributes['who'],
                          $attributes['desc'], $attributes['action']);

                    $values[] = $attributes
                        ? serialize($attributes)
                        : null;
                    $values[] = $history->data[$i]['id'];

                    $r = $this->_write_db->query(
                        'UPDATE horde_histories SET history_ts = ?,' .
                        ' history_who = ?,' .
                        ' history_desc = ?,' .
                        ' history_extra = ? WHERE history_id = ?', $values
                    );

                    if ($r instanceof PEAR_Error) {
                        Horde::logMessage($r, __FILE__, __LINE__, PEAR_LOG_ERR);
                        throw new Horde_Exception($r->getMessage());
                    }
                    $done = true;
                    break;
                }
            }
        }

        /* If we're not replacing by action, or if we didn't find an entry to
         * replace, insert a new row. */
        if (!$done) {
            $history_id = $this->_write_db->nextId('horde_histories');
            if ($history_id instanceof PEAR_Error) {
                Horde::logMessage($history_id, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Horde_Exception($history_id->getMessage());
            }

            $values = array(
                $history_id,
                $history->uid,
                $attributes['ts'],
                $attributes['who'],
                isset($attributes['desc']) ? $attributes['desc'] : null,
                isset($attributes['action']) ? $attributes['action'] : null
            );

            unset($attributes['ts'], $attributes['who'],
                  $attributes['desc'], $attributes['action']);

            $values[] = $attributes
                ? serialize($attributes)
                : null;

            $r = $this->_write_db->query(
                'INSERT INTO horde_histories (history_id, object_uid, history_ts, history_who, history_desc, history_action, history_extra)' .
                ' VALUES (?, ?, ?, ?, ?, ?, ?)', $values
            );

            if ($r instanceof PEAR_Error) {
                Horde::logMessage($r, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Horde_Exception($r->getMessage());
            }
        }

        return true;
    }

    /**
     * Returns a Horde_HistoryObject corresponding to the named history
     * entry, with the data retrieved appropriately.
     *
     * @param string $guid The name of the history entry to retrieve.
     *
     * @return Horde_HistoryObject  A Horde_HistoryObject
     */
    public function getHistory($guid)
    {
        $rows = $this->_db->getAll('SELECT * FROM horde_histories WHERE object_uid = ?', array($guid), DB_FETCHMODE_ASSOC);
        return new Horde_HistoryObject($guid, $rows);
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
     * <pre>
     * 'field' - the history field being compared (i.e. 'action').
     * 'op'    - the operator to compare this field with.
     * 'value' - the value to check for (i.e. 'add').
     * </pre>
     * @param string  $parent  The parent history to start searching at. If
     *                         non-empty, will be searched for with a LIKE
     *                         '$parent:%' clause.
     *
     * @return array  An array of history object ids, or an empty array if
     *                none matched the criteria.
     *
     * @throws Horde_Exception
     */
    public function getByTimestamp($cmp, $ts, $filters = array(),
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

        return $this->_db->getAssoc('SELECT DISTINCT object_uid, history_id FROM horde_histories WHERE ' . implode(' AND ', $where));
    }

    /**
     * Remove one or more history entries by name.
     *
     * @param array $names The history entries to remove.
     *
     * @return boolean True if the operation succeeded.
     *
     * @throws Horde_Exception
     */
    public function removeByNames($names)
    {
        if (!count($names)) {
            return true;
        }

        $ids = array();
        foreach ($names as $name) {
            $ids[] = $this->_write_db->quote($name);
        }

        return $this->_write_db->query('DELETE FROM horde_histories WHERE object_uid IN (' . implode(',', $ids) . ')');
    }

}
