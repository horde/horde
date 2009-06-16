<?php
/**
 * The Horde_History:: class provides a method of tracking changes in Horde
 * objects, stored in a SQL table.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_History
 */
class Horde_History
{
    /**
     * Instance object.
     *
     * @var Horde_History
     */
    static protected $_instance;

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
     * Attempts to return a reference to a concrete History instance.
     * It will only create a new instance if no History instance
     * currently exists.
     *
     * This method must be invoked as: $var = &History::singleton()
     *
     * @return Horde_History  The concrete Horde_History reference.
     * @throws Horde_Exception
     */
    static public function singleton()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Horde_History();
        }

        return self::$_instance;
    }

    /**
     * Constructor.
     *
     * @throws Horde_Exception
     */
    public function __construct()
    {
        global $conf;

        if (empty($conf['sql']['phptype']) || ($conf['sql']['phptype'] == 'none')) {
            throw new Horde_Exception(_("The History system is disabled."));
        }

        $this->_write_db = &DB::connect($conf['sql']);

        /* Set DB portability options. */
        $portability = DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS;

        if (is_a($this->_write_db, 'DB_common')) {
            $write_portability = $portability;
            if ($this->_write_db->phptype == 'mssql') {
                $write_portability |= DB_PORTABILITY_RTRIM;
            }
            $this->_write_db->setOption('portability', $write_portability);
        }

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($conf['sql']['splitread'])) {
            $params = array_merge($conf['sql'], $conf['sql']['read']);
            $this->_db = &DB::connect($params);

            /* Set DB portability options. */
            if (is_a($this->_db, 'DB_common')) {
                $read_portability = $portability;
                if ($this->_db->phptype == 'mssql') {
                    $read_portability |= DB_PORTABILITY_RTRIM;
                }
                $this->_db->setOption('portability', $read_portability);
            }
        } else {
            /* Default to the same DB handle for reads. */
            $this->_db =& $this->_write_db;
        }
    }

    /**
     * Logs an event to an item's history log. The item must be uniquely
     * identified by $guid. Any other details about the event are passed in
     * $attributes. Standard suggested attributes are:
     *
     *   'who' => The id of the user that performed the action (will be added
     *            automatically if not present).
     *
     *   'ts' => Timestamp of the action (this will be added automatically if
     *           it is not present).
     *
     * @param string $guid            The unique identifier of the entry to
     *                                add to.
     * @param array $attributes       The hash of name => value entries that
     *                                describe this event.
     * @param boolean $replaceAction  If $attributes['action'] is already
     *                                present in the item's history log,
     *                                update that entry instead of creating a
     *                                new one.
     *
     * @throws Horde_Exception
     */
    public function log($guid, $attributes = array(), $replaceAction = false)
    {
        $history = &$this->getHistory($guid);

        if (!isset($attributes['who'])) {
            $attributes['who'] = Auth::getAuth();
        }
        if (!isset($attributes['ts'])) {
            $attributes['ts'] = time();
        }

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

                    unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

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
                $guid,
                $attributes['ts'],
                $attributes['who'],
                isset($attributes['desc']) ? $attributes['desc'] : null,
                isset($attributes['action']) ? $attributes['action'] : null
            );

            unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

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
     * @param string $guid  The name of the history entry to retrieve.
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
     * @param string $cmp     The comparison operator (<, >, <=, >=, or =) to
     *                        check the timestamps with.
     * @param integer $ts     The timestamp to compare against.
     * @param array $filters  An array of additional (ANDed) criteria.
     *                        Each array value should be an array with 3
     *                        entries:
     * <pre>
     * 'field' - the history field being compared (i.e. 'action').
     * 'op'    - the operator to compare this field with.
     * 'value' - the value to check for (i.e. 'add').
     * </pre>
     * @param string $parent  The parent history to start searching at. If
     *                        non-empty, will be searched for with a LIKE
     *                        '$parent:%' clause.
     *
     * @return array  An array of history object ids, or an empty array if
     *                none matched the criteria.
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
     * Gets the timestamp of the most recent change to $guid.
     *
     * @param string $guid    The name of the history entry to retrieve.
     * @param string $action  An action: 'add', 'modify', 'delete', etc.
     *
     * @return integer  The timestamp, or 0 if no matching entry is found.
     * @throws Horde_Exception
     */
    public function getActionTimestamp($guid, $action)
    {
        /* This implementation still works, but we should be able to
         * get much faster now with a SELECT MAX(history_ts)
         * ... query. */
        try {
            $history = &$this->getHistory($guid);
        } catch (Horde_Exception $e) {
            return 0;
        }

        $last = 0;

        if (is_array($history->data)) {
            foreach ($history->data as $entry) {
                if (($entry['action'] == $action) && ($entry['ts'] > $last)) {
                    $last = $entry['ts'];
                }
            }
        }

        return (int)$last;
    }

    /**
     * Remove one or more history entries by name.
     *
     * @param array $names  The history entries to remove.
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
