<?php
/**
 * The Horde_History:: system.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * The Horde_History:: class provides a method of tracking changes in Horde
 * objects, stored in a SQL table.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */
abstract class Horde_History
{
    /**
     * Our log handler.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Set the log handler.
     *
     * @param Horde_Log_Logger $logger The log handler.
     *
     * @return NULL
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Logs an event to an item's history log.
     *
     * The item must be uniquely identified by $guid. Any other details about
     * the event are passed in $attributes. Standard suggested attributes are:
     * - who: The id of the user that performed the action (will be added
     *        automatically if not present).
     * - ts:  Timestamp of the action (this will be added automatically if it
     *        is not present).
     *
     * @param string  $guid          The unique identifier of the entry to add
     *                               to.
     * @param array   $attributes    The hash of name => value entries that
     *                               describe this event.
     * @param boolean $replaceAction If $attributes['action'] is already
     *                               present in the item's history log, update
     *                               that entry instead of creating a new one.
     *
     * @throws Horde_History_Exception
     */
    public function log($guid, array $attributes = array(),
                        $replaceAction = false)
    {
        if (!is_string($guid)) {
            throw new Horde_History_Exception('The guid needs to be a string!');
        }

        $history = $this->getHistory($guid);

        if (!isset($attributes['who'])) {
            $attributes['who'] = $GLOBALS['registry']->getAuth();
        }
        if (!isset($attributes['ts'])) {
            $attributes['ts'] = time();
        }

        $this->_log($history, $attributes, $replaceAction);
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
    abstract protected function _log(Horde_History_Log $history,
                                     array $attributes, $replaceAction = false);

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
    public function getHistory($guid)
    {
        if (!is_string($guid)) {
            throw new Horde_History_Exception('The guid needs to be a string!');
        }
        return $this->_getHistory($guid);
    }

    /**
     * Returns a Horde_History_Log corresponding to the named history entry,
     * with the data retrieved appropriately.
     *
     * @param string $guid  The name of the history entry to retrieve.
     *
     * @return Horde_History_Log  A Horde_History_Log object.
     */
    abstract public function _getHistory($guid);

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
    public function getByTimestamp($cmp, $ts, array $filters = array(),
                                   $parent = null)
    {
        if (!is_string($cmp)) {
            throw new Horde_History_Exception('The comparison operator needs to be a string!');
        }
        if (!is_integer($ts)) {
            throw new Horde_History_Exception('The timestamp needs to be an integer!');
        }
        return $this->_getByTimestamp($cmp, $ts, $filters, $parent);
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
    abstract public function _getByTimestamp($cmp, $ts,
                                             array $filters = array(),
                                             $parent = null);

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
            $history = $this->getHistory($guid);
        } catch (Horde_History_Exception $e) {
            return 0;
        }

        $last = 0;

        foreach ($history as $entry) {
            if (($entry['action'] == $action) && ($entry['ts'] > $last)) {
                $last = $entry['ts'];
            }
        }

        return (int)$last;
    }

    /**
     * Remove one or more history entries by parent.
     *
     * @param string $parent  The parent name to remove.
     *
     * @throws Horde_History_Exception
     */
    public function removeByParent($parent)
    {
        /* Remove entries 100 at a time. */
        $all = array_keys($this->getByTimestamp('>', 0, array(), $parent));

        while (count($d = array_splice($all, 0, 100)) > 0) {
            $this->removebyNames($d);
        }
    }

    /**
     * Removes one or more history entries by name.
     *
     * @param array $names  The history entries to remove.
     *
     * @throws Horde_History_Exception
     */
    abstract public function removeByNames(array $names);
}
