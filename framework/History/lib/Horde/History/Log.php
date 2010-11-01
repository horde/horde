<?php
/**
 * Class for presenting Horde_History information.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package History
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

        reset($data);
        while (list(,$row) = each($data)) {
            $history = array(
                'action' => $row['history_action'],
                'desc' => $row['history_desc'],
                'who' => $row['history_who'],
                'id' => $row['history_id'],
                'ts' => $row['history_ts']
            );

            if ($row['history_extra']) {
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
