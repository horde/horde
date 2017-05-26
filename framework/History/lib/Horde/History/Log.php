<?php
/**
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  History
 */

/**
 * Class for presenting Horde_History information.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2003-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   History
 */
class Horde_History_Log implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * TODO
     */
    public $uid;

    /**
     * TODO
     */
    protected $_data = array();

    /**
     * Constructor.
     *
     * TODO
     */
    public function __construct($uid, $data = array())
    {
        $this->uid = $uid;

        if (!$data) {
            return;
        }

        foreach ($data as $row) {
            $history = array(
                'action' => $row['history_action'],
                'desc' => $row['history_desc'],
                'who' => $row['history_who'],
                'id' => $row['history_id'],
                'ts' => $row['history_ts'],
                'modseq' => $row['history_modseq']
            );

            if (!empty($row['history_extra'])) {
                $extra = @unserialize($row['history_extra']);
                if ($extra) {
                    $history = array_merge($history, $extra);
                }
            }
            $this->_data[] = $history;
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    public function count()
    {
        return count($this->_data);
    }
}
