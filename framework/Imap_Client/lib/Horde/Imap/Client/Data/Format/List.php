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
     * @see add()
     */
    public function __construct($data = null)
    {
        parent::__construct(array());

        if (!is_null($data)) {
            $this->add($data);
        }
    }

    /**
     * Add an element to the list.
     *
     * @param mixed $data     The data element(s) to add. Either a
     *                        Horde_Imap_Client_Data_Format object, a string
     *                        value that will be treated as an IMAP atom, or
     *                        an array (or iterable object) of objects to add.
     * @param boolean $merge  Merge the contents of any container objects,
     *                        instead of adding the objects themselves?
     */
    public function add($data, $merge = false)
    {
        if (is_array($data) || ($merge && ($data instanceof Traversable))) {
            foreach ($data as $val) {
                $this->add($val);
            }
        } else {
            $this->_data[] = ($data instanceof Horde_Imap_Client_Data_Format)
                ? $data
                : new Horde_Imap_Client_Data_Format_Atom($data);
        }
    }

    /**
     */
    public function __toString()
    {
        $out = '';

        foreach ($this as $val) {
            if ($val instanceof $this) {
                $out .= '(' . $val->escape() . ') ';
            } elseif (($val instanceof Horde_Imap_Client_Data_Format_String) &&
                      $val->literal()) {
                throw new Horde_Imap_Client_Data_Format_Exception('Requires literal output.');
            } else {
                $out .= $val->escape() . ' ';
            }
        }

        return rtrim($out);
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_data);
    }

    /* IteratorAggregate method. */

    /**
     * Iterator loops through the data elements contained in this list.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

}
