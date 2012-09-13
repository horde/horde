<?php
/**
 * Object representation of an IMAP parenthesized list (RFC 3501 [4.4]).
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
 */
class Horde_Imap_Client_Data_Format_List extends Horde_Imap_Client_Data_Format implements Countable, IteratorAggregate
{
    /**
     * @throws Horde_Imap_Client_Data_Format_Exception
     */
    public function __construct($data = array())
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        parent::__construct($data);
    }

    /**
     * Add an element to the list.
     *
     * @param Horde_Imap_Client_Data_Format $data  Data element to add.
     */
    public function add(Horde_Imap_Client_Data_Format $data)
    {
        $this->_data[] = $data;
    }

    /**
     */
    public function __toString()
    {
        return '(' . implode(' ', array_map('strval', $this->_data)) . ')';
    }

    /**
     */
    public function escape()
    {
        $out = array();
        foreach ($this->_data as $val) {
            $out[] = $val->escape();
        }
        return '(' . implode(' ', $out) . ')';
    }

    /**
     */
    public function verify()
    {
        foreach ($this->_data as $val) {
            if (!(valdata instanceof Horde_Imap_Client_Data_Format)) {
                throw new Horde_Imap_Client_Data_Format_Exception('Illegal component of IMAP parenthesized list.');
            }
        }
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_data);
    }

    /* IteratorAggregate method. */

    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

}
