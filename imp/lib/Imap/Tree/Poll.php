<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Manage the mailbox poll list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $locked  True if mailbox polling status is locked.
 */
class IMP_Imap_Tree_Poll implements ArrayAccess, Horde_Shutdown_Task
{
    /**
     * Is the mailbox polling status locked?
     *
     * @var boolean
     */
    protected $_locked;

    /**
     * Mailbox poll list.
     *
     * @var array
     */
    protected $_poll = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $prefs;

        if ($prefs->getValue('nav_poll_all')) {
            $this->_locked = $this->_poll = true;
        } else {
            /* We ALWAYS poll the INBOX. */
            $this->_poll = array('INBOX' => 1);

            /* Add the list of polled mailboxes from the prefs. */
            if ($navPollList = @unserialize($prefs->getValue('nav_poll'))) {
                $this->_poll += $navPollList;
            }

            $this->_locked = $prefs->isLocked('nav_poll');
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'locked':
            return $this->_locked;
        }
    }

    /**
     */
    public function shutdown()
    {
        $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_poll));
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     */
    public function offsetGet($offset)
    {
        return isset($this->_poll[$offset]);
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        if (($this[$offset] != $value) && !$this->locked) {
            if ($value) {
                $this->_poll[$offset] = true;
            } else {
                unset($this->_poll[$offset]);
            }

            Horde_Shutdown::add($this);
        }
    }

    /**
     */
    public function offsetUnset($offset)
    {
        $this[$offset] = false;
    }

}
