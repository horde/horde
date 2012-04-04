<?php
/**
 * The IMP_Indices class provides functions to handle lists of message
 * indices.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Indices implements ArrayAccess, Countable, Iterator
{
    /**
     * Default mailbox name.
     *
     * @var array
     */
    protected $_default = 'INBOX';

    /**
     * The indices list.
     *
     * @var array
     */
    protected $_indices = array();

    /**
     * Constructor.
     *
     * Parameters are the same as add().
     *
     * @see add()
     */
    public function __construct()
    {
        if (func_num_args()) {
            $args = func_get_args();
            call_user_func_array(array($this, 'add'), $args);
        }
    }

    /**
     * Add indices.
     *
     * Input format:
     * <pre>
     * 1 argument:
     * -----------
     * + Array
     *   Either:
     *     KEYS: Mailbox names
     *     VALUES: UIDs -or- Horde_Imap_Client_Ids object
     *  -or-
     *     VALUES: IMAP sequence strings
     * + IMP_Compose object
     * + IMP_Contents object
     * + IMP_Indices object
     * + IMP_Mailbox_List_Track object
     * + String
     *   Format: IMAP sequence string
     *
     * 2 arguments:
     * ------------
     * 1st argument: Mailbox name -or- IMP_Mailbox object
     * 2nd argument: Either a single UID, array of UIDs, or a
     *               Horde_Imap_Client_Ids object.
     * </pre>
     */
    public function add()
    {
        $data = func_get_arg(0);
        $indices = array();

        switch (func_num_args()) {
        case 1:
            if (is_array($data)) {
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $indices[$key] = array_keys(array_flip($val));
                    } elseif ($val instanceof Horde_Imap_Client_Ids) {
                        $this->add($key, $val);
                    } else {
                        $this->add($val);
                    }
                }
            } elseif (is_string($data)) {
                $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
                $indices = $imp_imap->getUtils()->fromSequenceString($data);
                if ($imp_imap->pop3) {
                    $indices = array($this->_default => $indices);
                }
            } elseif ($data instanceof IMP_Compose) {
                $indices = array(
                    strval($data->getMetadata('mailbox')) => array($data->getMetadata('uid'))
                );
            } elseif ($data instanceof IMP_Contents) {
                $indices = array(
                    strval($data->getMailbox()) => array($data->getUid())
                );
            } elseif ($data instanceof IMP_Indices) {
                $indices = $data->indices();
            } elseif ($data instanceof IMP_Mailbox_List_Track) {
                $idx = $data->getIMAPIndex();
                $indices = array(
                    strval($idx['mailbox']) => array($idx['uid'])
                );
            }
            break;

        case 2:
            $secondarg = func_get_arg(1);
            if (is_array($secondarg)) {
                $secondarg = array_keys(array_flip($secondarg));
            } elseif ($secondarg instanceof Horde_Imap_Client_Ids) {
                $secondarg = $secondarg->ids;
            } else {
                $secondarg = array($secondarg);
            }

            if (!empty($secondarg)) {
                $indices = array(
                    strval(func_get_arg(0)) => $secondarg
                );
            }
            break;
        }

        if (!empty($indices)) {
            if (empty($this->_indices)) {
                $this->_indices = $indices;
            } else {
                /* Can't use array_merge_recursive() here because keys may
                 * be numeric mailbox names (e.g. 123), and these keys are
                 * treated as numeric (not strings) when merging. */
                foreach (array_keys($indices) as $key) {
                    $this->_indices[$key] = isset($this->_indices[$key])
                        ? array_merge($this->_indices[$key], $indices[$key])
                        : $indices[$key];
                }
            }
        }
    }

    /**
     * Returns mailbox/UID information for the first index.
     *
     * @return boolean $all  If true, returns all UIDs for the first index
     *                       in an array. If false, returns the first UID for
     *                       the first index as a string.
     *
     * @return array  A 2-element array with an IMP_Mailbox object and the
     *                UID(s).
     */
    public function getSingle($all = false)
    {
        $val = reset($this->_indices);
        return array(
            IMP_Mailbox::get(key($this->_indices)),
            $all ? $val : (is_array($val) ? reset($val) : null)
        );
    }

    /**
     * Return a copy of the indices array.
     *
     * @return array  The indices array (keys are mailbox names, values are
     *                arrays of UIDs).
     */
    public function indices()
    {
        return $this->_indices;
    }

    /**
     * Converts an indices object string to a string form representation.
     * Needed because null characters (used for various internal non-IMAP
     * mailbox representations) will not work in form elements.
     *
     * @return string  String representation (IMAP sequence string).
     */
    public function formTo()
    {
        $converted = array();
        foreach ($this->_indices as $key => $val) {
            $converted[IMP_Mailbox::formTo($key)] = $val;
        }

        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getUtils()->toSequenceString($converted, array('mailbox' => true));
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_indices[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        return isset($this->_indices[$offset])
            ? $this->_indices[$offset]
            : null;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        unset($this->_indices[$offset]);
        $this->add($offset, $value);
    }

    /**
     */
    public function offsetUnset($offset)
    {
        unset($this->_indices[$offset]);
    }

    /* Countable methods. */

    /**
     * Index count.
     *
     * @return integer  The number of indices.
     */
    public function count()
    {
        $count = 0;

        foreach (array_keys($this->_indices) as $key) {
            $count += count($this->_indices[$key]);
        }

        return $count;
    }

    /* Magic methods. */

    /**
     * String representation of the object.
     *
     * @return string  String representation (IMAP sequence string).
     */
    public function __toString()
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getUtils()->toSequenceString($this->_indices, array('mailbox' => true));
    }

    /* Iterator methods. */

    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        $ret = new stdClass;
        $ret->mbox = IMP_Mailbox::get($this->key());
        $ret->uids = current($this->_indices);

        return $ret;
    }

    public function key()
    {
        return key($this->_indices);
    }

    public function next()
    {
        if ($this->valid()) {
            next($this->_indices);
        }
    }

    public function rewind()
    {
        reset($this->_indices);
    }

    public function valid()
    {
        return !is_null(key($this->_indices));
    }

}
