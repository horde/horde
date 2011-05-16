<?php
/**
 * ACL rights for a mailbox (see RFC 2086/4314).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_Acl extends Horde_Imap_Client_Data_AclCommon implements ArrayAccess, Iterator, Serializable
{
    /**
     * ACL rights.
     *
     * @var array
     */
    protected $_rights;

    /**
     * Constructor.
     *
     * @var string $rights  The rights (see RFC 4314 [2.1]).
     */
    public function __construct($rights = '')
    {
        $this->_rights = str_split($rights);
        $this->_normalize();
    }

    /**
     * String representation of the ACL.
     *
     * @return string  String representation (RFC 4314 compliant).
     */
    public function __toString()
    {
        return implode('', $this->_rights);
    }

    /**
     * Computes the difference to another rights string.
     * Virtual rights are ignored.
     *
     * @param string $rights  The rights to compute against.
     *
     * @return array  Two element array: added and removed.
     */
    public function diff($rights)
    {
        $rlist = array_diff(str_split($rights), array_keys($this->_virtual));

        return array(
            'added' => implode('', array_diff($rlist, $this->_rights)),
            'removed' => implode('', array_diff($this->_rights, $rlist))
        );
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return $this[$offset];
    }

    /**
     */
    public function offsetGet($offset)
    {
        return in_array($offset, $this->_rights);
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        if ($value) {
            if (!$this[$offset]) {
                $this->_rights[] = $offset;
                $this->_normalize();
            }
        } elseif ($this[$offset]) {
            unset($this[$offset]);
        }
    }

    /**
     */
    public function offsetUnset($offset)
    {
        $this->_rights = array_values(array_diff($this->_rights, array($offset)));
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return current($this->_rights);
    }

    /**
     */
    public function key()
    {
        return key($this->_rights);
    }

    /**
     */
    public function next()
    {
        next($this->_rights);
    }

    /**
     */
    public function rewind()
    {
        reset($this->_rights);
    }

    /**
     */
    public function valid()
    {
        return (key($this->_rights) !== null);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode($this->_rights);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_rights = json_decode($data);
    }

}
