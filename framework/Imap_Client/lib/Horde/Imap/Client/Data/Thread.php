<?php
/**
 * Object representing the threaded sort results from
 * Horde_Imap_Client_Base#thread().
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
class Horde_Imap_Client_Data_Thread implements Countable, Serializable
{
    /**
     * Internal thread data structure. Keys are base values, values are arrays
     * with keys as the ID and values as the level.
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
     * @param array $data   See $_thread.
     * @param string $type  Either 'sequence' or 'uid'.
     */
    public function __construct($data, $type)
    {
        $this->_thread = $data;
        $this->_type = $type;
    }

    /**
     * Return the ID type.
     *
     * @return string  Either 'sequence' or 'uid'.
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Return the sorted list of messages indices.
     *
     * @return Horde_Imap_Client_Ids  The sorted list of messages.
     */
    public function messageList()
    {
        return new Horde_Imap_Client_Ids($this->_getAllIndices(), $this->getType() == 'sequence');
    }

    /**
     * Returns the list of messages in a thread.
     *
     * @param integer $index  An index contained in the thread.
     *
     * @return array  Keys are indices, values are objects with the following
     *                properties:
     *   - base: (integer) Base ID of the thread. If null, thread is a single
     *           message.
     *   - last: (boolean) If true, this is the last index in the sublevel.
     *   - level: (integer) The sublevel of the index.
     */
    public function getThread($index)
    {
        reset($this->_thread);
        while (list(,$v) = each($this->_thread)) {
            if (isset($v[$index])) {
                reset($v);

                $ob = new stdClass;
                $ob->base = (count($v) > 1) ? key($v) : null;
                $ob->last = false;

                $levels = $out = array();
                $last = 0;

                while (list($k2, $v2) = each($v)) {
                    $ob2 = clone $ob;
                    $ob2->level = $v2;
                    $out[$k2] = $ob2;

                    if (($last < $v2) && isset($levels[$v2])) {
                        $out[$levels[$v2]]->last = true;
                    }
                    $levels[$v2] = $k2;
                    $last = $v2;
                }

                foreach ($levels as $v) {
                    $out[$v]->last = true;
                }

                return $out;
            }
        }

        return array();
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_getAllIndices());
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->_thread,
            $this->_type
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->_thread, $this->_type) = json_decode($data, true);
    }

    /* Protected methods. */

    /**
     * Return all indices.
     *
     * @param array  An array of indices.
     */
    protected function _getAllIndices()
    {
        $out = array();

        reset($this->_thread);
        while (list(,$v) = each($this->_thread)) {
            $out = array_merge($out, array_keys($v));
        }

        return $out;
    }

}
