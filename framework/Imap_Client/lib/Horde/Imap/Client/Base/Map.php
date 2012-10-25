<?php
/**
 * An object allowing management of a mailbox within a Horde_Imap_Client_Base
 * object.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 *
 * @property array $map  The raw ID mapping data.
 * @property Horde_Imap_Client_Ids $seq  The sorted sequence values.
 * @property Horde_Imap_Client_Ids $uids  The sorted UIDs.
 */
class Horde_Imap_Client_Base_Map implements Countable, IteratorAggregate, Serializable
{
    /**
     * Sequence -> UID mapping.
     *
     * @var array
     */
    protected $_ids = array();

    /**
     * Is the array sorted?
     *
     * @var boolean
     */
    protected $_sorted = false;

    /**
     * Constructor.
     *
     * @param array $ids  Array of sequence -> UID mapping.
     */
    public function __construct(array $ids = array())
    {
        $this->update($ids);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'map':
            return $this->_ids;

        case 'seq':
            $this->sort();
            return new Horde_Imap_Client_Ids(array_keys($this->_ids), true);

        case 'uids':
            $this->sort();
            return new Horde_Imap_Client_Ids($this->_ids);
        }
    }

    /**
     * Updates the mapping.
     *
     * @param array $ids  Array of sequence -> UID mapping.
     */
    public function update($ids)
    {
        $this->_ids = empty($this->_ids)
            ? $ids
            : $ids + $this->_ids;
        $this->_sorted = false;
    }

    /**
     * Create a Sequence <-> UID lookup table.
     *
     * @param Horde_Imap_Client_Ids $ids  IDs to lookup.
     *
     * @return array  Keys are sequence numbers, values are UIDs.
     */
    public function lookup(Horde_Imap_Client_Ids $ids)
    {
        if ($ids->all) {
            return $this->_ids;
        } elseif ($ids->sequence) {
            return array_intersect_key($this->_ids, array_flip($ids->ids));
        }

        return array_intersect($this->_ids, $ids->ids);
    }

    /**
     * Removes messages from the ID mapping.
     *
     * @param Horde_Imap_Client_Ids $id  IDs to remove.
     */
    public function remove(Horde_Imap_Client_Ids $ids)
    {
        /* For sequence numbers, we need to reindex anytime we have an index
         * that appears equal to or after a previously seen index. If an IMAP
         * server is smart, it will expunge in reverse order instead. */
        if ($ids->sequence) {
            $remove = $ids->ids;
        } else {
            $ids->sort();
            $remove = array_reverse(array_keys($this->lookup($ids)));
        }

        if (empty($remove)) {
            return;
        }

        $this->sort();

        /* Find the minimum sequence number to remove. We know entries before
         * this are untouched so no need to process them multiple times. */
        $first = min($remove);
        $edit = $newids = array();
        foreach (array_keys($this->_ids) as $i => $seq) {
            if ($seq >= $first) {
                $i += (($seq == $first) ? 0 : 1);
                $newids = array_slice($this->_ids, 0, $i, true);
                $edit = array_slice($this->_ids, $i + (($seq == $first) ? 0 : 1), null, true);
                break;
            }
        }

        if (!empty($edit)) {
            foreach ($remove as $val) {
                $found = false;
                $tmp = array();

                foreach (array_keys($edit) as $i => $seq) {
                    if ($found) {
                        $tmp[$seq - 1] = $edit[$seq];
                    } elseif ($seq >= $val) {
                        $tmp = array_slice($edit, 0, ($seq == $val) ? $i : $i + 1, true);
                        $found = true;
                    }
                }

                $edit = $tmp;
            }
        }

        $this->_ids = $newids + $edit;
    }

    /**
     * Sort the map.
     */
    public function sort()
    {
        if (!$this->_sorted) {
            ksort($this->_ids, SORT_NUMERIC);
            $this->_sorted = true;
        }
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_ids);
    }

    /* IteratorAggregate method. */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_ids);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        /* Sort before storing; provides more compressible representation. */
        $this->sort();

        return json_encode(array(
            strval(new Horde_Imap_Client_Ids(array_keys($this->_ids))),
            strval(new Horde_Imap_Client_Ids(array_values($this->_ids)))
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);

        $keys = new Horde_Imap_Client_Ids($data[0]);
        $vals = new Horde_Imap_Client_Ids($data[1]);
        $this->_ids = array_combine($keys->ids, $vals->ids);

        /* Guaranteed to be sorted if unserializing. */
        $this->_sorted = true;
    }

}
