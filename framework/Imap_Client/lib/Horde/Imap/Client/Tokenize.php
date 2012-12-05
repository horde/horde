<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Base abstract class for tokenization of an IMAP data stream.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
abstract class Horde_Imap_Client_Tokenize implements Iterator
{
    /**
     * Current data.
     *
     * @var mixed
     */
    protected $_current = false;

    /**
     * Current key.
     *
     * @var integer
     */
    protected $_key = false;

    /**
     * String representation.
     *
     * @return string  String representation.
     */
    abstract public function __toString();

    /**
     */
    public function __sleep()
    {
        throw new LogicException('Object can not be serialized.');
    }

    /**
     * Flush the remaining entries left in the iterator.
     *
     * @param boolean $return_entry  If true, return entries.
     *
     * @return array  The remaining iterator entries if $return_entry is true.
     */
    abstract public function flushIterator($return_entry = true);

    /**
     */
    protected function _flushIterator($return_entry)
    {
        $out = array();

        if (!$this->valid()) {
            $this->next();
        }

        while ($this->valid()) {
            if ($return_entry) {
                $out[] = $this->current();
            }
            $this->next();
        }

        return $out;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     */
    abstract public function next();

    /**
     */
    abstract public function rewind();

    /**
     */
    public function valid()
    {
        return ($this->_key !== false);
    }

}
