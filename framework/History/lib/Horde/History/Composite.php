<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  History
 */

/**
 * A composite implementation of the history storage backend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   History
 */
class Horde_History_Composite extends Horde_History
{
    /**
     * Driver list.
     *
     * @var array
     */
    protected $_drivers;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * REQUIRED parameters:
     *   - drivers: (array) An array of Horde_History objects.
     * </pre>
     */
    public function __construct($auth, array $params = array())
    {
        if (!isset($params['drivers'])) {
            throw new InvalidArgumentException('Missing drivers parameter.');
        }

        $this->_drivers = $params['drivers'];

        parent::__construct($params);
    }

    /**
     */
    public function log(
        $guid, array $attributes = array(), $replaceAction = false
    )
    {
        /* Only save to 1st driver that is succesful. */
        foreach ($this->_drivers as $val) {
            try {
                $val->log($guid, $attributes, $replaceAction);
                return;
            } catch (Horde_History_Exception $e) {}
        }
    }

    /**
     */
    protected function _log(
        Horde_History_Log $history, array $attributes, $replaceAction = false
    )
    {
        /* Not used, but is abstract so needs to be defined. */
    }

    /**
     */
    public function getHistory($guid)
    {
        /* Can't use caching from parent class, since it is common to ALL
         * drivers. But we can use sanity checking from subclass calls to
         * getHistory(). */
        $cid = 'horde:history:' . $guid . '_composite';

        if (!$this->_cache ||
            !($history = @unserialize($this->_cache->get($cid, 0)))) {
            $data = array();
            $fields = array(
                'action', 'desc', 'who', 'id', 'ts', 'modseq', 'extra'
            );

            foreach ($this->_drivers as $val) {
                try {
                    foreach ($val->getHistory($guid) as $val2) {
                        $extra = $tmp = array();
                        foreach ($val2 as $key3 => $val3) {
                            if (in_array($key3, $fields)) {
                                $tmp['history_' . $key3] = $val3;
                            } else {
                                $extra[$key3] = $val3;
                            }
                        }
                    }

                    if (!empty($extra)) {
                        $tmp['history_extra'] = $extra;
                    }

                    $data[] = $tmp;
                } catch (Horde_History_Exception $e) {}
            }

            $history = new Horde_History_Log($guid, $data);

            if ($this->_cache) {
                $this->_cache->set($cid, serialize($history));
            }
        }

        return $history;
    }

    /**
     */
    public function _getHistory($guid)
    {
        /* Not used, but is abstract so needs to be defined. */
    }

    /**
     */
    public function _getByTimestamp(
        $cmp, $ts, array $filters = array(), $parent = null
    )
    {
        $ret = array();

        foreach ($this->_drivers as $val) {
            try {
                $ret = array_merge(
                    $ret,
                    $val->getByTimestamp($cmp, $ts, $filters, $parent)
                );
            } catch (Horde_History_Exception $e) {}
        }

        return $ret;
    }

    /**
     */
    public function removeByNames(array $names)
    {
        foreach ($this->_drivers as $val) {
            try {
                $val->removeByNames($names);
            } catch (Horde_History_Exception $e) {}
        }
    }

}
