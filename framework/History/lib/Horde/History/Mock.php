<?php
/**
 * A mock history driver.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * The Horde_History_Mock:: class provides a method of tracking changes in
 * Horde objects, stored in memory.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  History
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */
class Horde_History_Mock extends Horde_History
{
    /**
     * Our storage location.
     *
     * @var array
     */
    private $_data = array();

    /**
     * The next id.
     *
     * @var int
     */
    private $_id = 1;

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
        $values = array(
            'history_uid'    => $history->uid,
            'history_ts'     => $attributes['ts'],
            'history_who'    => $attributes['who'],
            'history_desc'   => isset($attributes['desc']) ? $attributes['desc'] : null,
            'history_action' => isset($attributes['action']) ? $attributes['action'] : null
        );

        unset($attributes['ts'], $attributes['who'], $attributes['desc'], $attributes['action']);

        $values['history_extra'] = $attributes
            ? serialize($attributes)
            : null;

        /* If we want to replace an entry with the same action, try and find
         * one. Track whether or not we succeed in $done, so we know whether
         * or not to add the entry later. */
        $done = false;
        if ($replaceAction && !empty($values['history_action'])) {
            foreach ($history as $entry) {
                if (!empty($entry['action']) &&
                    $entry['action'] == $values['history_action']) {
                    $this->_data[$entry['id']] = $values;
                    $done = true;
                    break;
                }
            }
        }

        /* If we're not replacing by action, or if we didn't find an entry to
         * replace, insert a new row. */
        if (!$done) {

            $this->_data[$this->_id] = $values;
            $this->_id++;
        }
    }

    /**
     * Returns a Horde_History_Log corresponding to the named history entry,
     * with the data retrieved appropriately.
     *
     * @param string $guid  The name of the history entry to retrieve.
     *
     * @return Horde_History_Log  A Horde_History_Log object.
     */
    public function _getHistory($guid)
    {
        $result= array();
        foreach ($this->_data as $id => $element) {
            if ($element['history_uid'] == $guid) {
                $element['history_id'] = $id;
                $result[] = $element;
            }
        }
        return new Horde_History_Log($guid, $result);
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
        $result = array();

        foreach ($this->_data as $id => $element) {

            $ignore = false;

            switch ($cmp) {
            case '<=':
            case '=<':
                if ($element['history_ts'] > $ts) {
                    $ignore = true;
                };
                break;
            case '<':
                if ($element['history_ts'] >= $ts) {
                    $ignore = true;
                };
                break;
            case '=':
                if ($element['history_ts'] != $ts) {
                    $ignore = true;
                };
                break;
            case '>':
                if ($element['history_ts'] <= $ts) {
                    $ignore = true;
                };
                break;
            case '>=':
            case '=>':
                if ($element['history_ts'] < $ts) {
                    $ignore = true;
                };
                break;
            default:
                throw new Horde_History_Exception(sprintf("Comparison %s not implemented!", $cmp));
            }

            if ($ignore) {
                continue;
            }

            /* Add additional filters, if there are any. */
            if ($filters) {
                foreach ($filters as $filter) {
                    if ($filter['op'] != '=') {
                        throw new Horde_History_Exception(sprintf("Comparison %s not implemented!", $filter['op']));
                    }
                    if ($element['history_' . $filter['field']] != $filter['value']) {
                        $ignore = true;
                    }
                }
            }

            if ($ignore) {
                continue;
            }

            if ($parent) {
                if (substr($element['history_uid'], 0, strlen($parent) + 1) != $parent . ':') {
                    continue;
                }
            }

            $result[$element['history_uid']] = $id;
        }
        return $result;
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
        foreach ($this->_data as $id => $element) {
            if (in_array($element['history_uid'], $names)) {
                $ids[] = $id;
            }
        }

        foreach ($ids as $id) {
            unset($this->_data[$id]);
        }
    }
}
