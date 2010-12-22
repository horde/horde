<?php
/**
 * The IMP_Indices class provides functions to handle lists of message
 * indices.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Indices implements Countable, Iterator
{
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
     *     VALUES: UIDs
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
     * 1st argument: Mailbox name
     * 2nd argument: Either a single UID or an array of UIDs.
     * </pre>
     */
    public function add()
    {
        $data = func_get_arg(0);
        $indices = array();

        switch (func_num_args()) {
        case 1:
            if (is_array($data)) {
                if (is_array(reset($data))) {
                    foreach ($data as $key => $val) {
                        $indices[$key] = array_keys(array_flip($val));
                    }
                } else {
                    foreach ($data as $val) {
                        $this->add($val);
                    }
                }
            } elseif (is_string($data)) {
                $indices = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->getUtils()->fromSequenceString($data);
            } elseif ($data instanceof IMP_Compose) {
                $indices = array(
                    $data->getMetadata('mailbox') => array($data->getMetadata('uid'))
                );
            } elseif ($data instanceof IMP_Contents) {
                $indices = array(
                    $data->getMailbox() => array($data->getUid())
                );
            } elseif ($data instanceof IMP_Indices) {
                $indices = $data->indices();
            } elseif ($data instanceof IMP_Mailbox_List_Track) {
                $idx = $data->getIMAPIndex();
                $indices = array(
                    $idx['mailbox'] => array($idx['uid'])
                );
            }
            break;

        case 2:
            $secondarg = func_get_arg(1);
            $secondarg = is_array($secondarg)
                ? array_keys(array_flip($secondarg))
                : array($secondarg);
            if (!empty($secondarg)) {
                $indices = array(
                    func_get_arg(0) => $secondarg
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
     * @return array  A 2-element array with the mailbox and the UID(s).
     */
    public function getSingle($all = false)
    {
        $val = reset($this->_indices);
        return array(key($this->_indices), $all ? $val : reset($val));
    }

    /**
     * Return a copy of the indices array.
     *
     * @return array  The indices array (keys are mailbox names, values are
     *                arrays of UIDS).
     */
    public function indices()
    {
        /* This creates a copy of the indices array. Needed because the
         * Iterator functions rely on pointers. */
        return $this->_indices;
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
        return $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->getUtils()->toSequenceString($this->_indices, array('mailbox' => true));
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_indices[$this->key()]);
    }

    public function key()
    {
        return key($this->_indices);
    }

    public function next()
    {
        if ((next($this->_indices[$this->key()]) === false) &&
            (next($this->_indices) !== false)) {
            reset($this->_indices[$this->key()]);
        }
    }

    public function rewind()
    {
        if (reset($this->_indices)) {
            reset(current($this->_indices));
        }
    }

    public function valid()
    {
        return !is_null(key($this->_indices));
    }

}
