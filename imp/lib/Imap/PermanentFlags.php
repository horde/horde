<?php
/**
 * THis class provides an interface to query a mailbox's settable permanent
 * flags.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_PermanentFlags implements Iterator, Serializable
{
    const CREATE = "\\*";

    /**
     * Can new flags NOT be created?
     *
     * @var boolean
     */
    protected $_nocreate = false;

    /**
     * List of unsettable flags.
     *
     * @var array
     */
    protected $_noset = array();

    /**
     * List of settable flags.
     *
     * @var array
     */
    protected $_set = array();

    /**
     * Constructor.
     *
     * @param array $permflags  List of permanent flags in mailbox.
     * @param array $flags      List of flags in mailbox.
     */
    public function __construct(array $permflags = array(),
                                array $flags = array())
    {
        $this->_nocreate = !in_array(self::CREATE, $permflags);
        $this->_noset = array_diff($status['permflags'], $status['flags'], array(self::CREATE));
        $this->_set = array_intersect($status['permflags'], $status['flags']);
    }

    /**
     * Determines if the given flag is allowed to be changed permanently.
     *
     * @param string $flag  The flag to query.
     *
     * @return boolean  True if flag can be set permanently.
     */
    public function allowed($flag)
    {
        $flag = strtolower($flag);
        return (!in_array($flag, $this->_noset) &&
                (!$this->_nocreate || in_array($flag, $this->_set)));
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_set);
    }

    public function key()
    {
        return key($this->_set);
    }

    public function next()
    {
        if ($this->valid()) {
            next($this->_set);
        }
    }

    public function rewind()
    {
        reset($this->_set);
    }

    public function valid()
    {
        return !is_null(key($this->_set));
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array_filter(array(
            'nc' => $this->_nocreate,
            'ns' => $this->_noset,
            's' => $this->_set
        )));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);

        $this->_nocreate = !empty($data['nc']);
        if (isset($data['ns'])) {
            $this->_noset = $data['ns'];
        }
        if (isset($data['s'])) {
            $this->_set = $data['s'];
        }
    }

}
