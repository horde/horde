<?php
/**
 * Object allowing easy access to threaded sort results from
 * Horde_Imap_Client_Base::thread().
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_Thread implements Countable
{
    /**
     * Internal thread data structure.
     *
     * @var array
     */
    protected $_thread = array();

    /**
     * The index type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Constructor.
     *
     * @param array $data   The data as returned by
     *                      Horde_Imap_Client_Base::_thread().
     * @param string $type  Either 'uid' or 'sequence'.
     */
    public function __construct($data, $type)
    {
        $this->_thread = $data;
        $this->_type = $type;
    }

    /**
     * Return the raw thread data array.
     *
     * @return array  See Horde_Imap_Client_Base::_thread().
     */
    public function getRawData()
    {
        return $this->_thread;
    }

    /**
     * Gets the indention level for an index.
     *
     * @param integer $index  The index.
     *
     * @return mixed  Returns the thread indent level if $index found.
     *                Returns false on failure.
     */
    public function getThreadIndent($index)
    {
        return isset($this->_thread[$index])
            ? (isset($this->_thread[$index]['l']) ? $this->_thread[$index]['l'] : 0)
            : false;
    }

    /**
     * Gets the base thread index for an index.
     *
     * @param integer $index  The index.
     *
     * @return mixed  Returns the base index if $index is part of a thread.
     *                Returns false on failure.
     */
    public function getThreadBase($index)
    {
        return isset($this->_thread[$index])
            ? (isset($this->_thread[$index]['b']) ? $this->_thread[$index]['b'] : null)
            : false;
    }

    /**
     * Is this index the last in the current level?
     *
     * @param integer $index  The index.
     *
     * @return boolean  Returns true if $index is the last element in the
     *                  current thread level.
     *                  Returns false if not, or on failure.
     */
    public function lastInLevel($index)
    {
        return empty($this->_thread[$index]['s']);
    }

    /**
     * Return the sorted list of messages indices.
     *
     * @return array  The sorted list of messages.
     */
    public function messageList()
    {
        return array_keys($this->_thread);
    }

    /**
     * Returns the list of messages in the current thread.
     *
     * @param integer $index  The index of the current message.
     *
     * @return array  A list of message indices.
     */
    public function getThread($index)
    {
        /* Find the beginning of the thread. */
        if (!($begin = $this->getThreadBase($index))) {
            return array($index);
        }

        /* Work forward from the first thread element to find the end of the
         * thread. */
        $in_thread = false;
        $thread_list = array();
        reset($this->_thread);
        while (list($k, $v) = each($this->_thread)) {
            if ($k == $begin) {
                $in_thread = true;
            } elseif ($in_thread && ($this->getThreadBase($k) != $begin)) {
                break;
            }

            if ($in_thread) {
                $thread_list[] = $k;
            }
        }

        return $thread_list;
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_thread);
    }

}
