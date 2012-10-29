<?php
/**
 * An object allowing management of mailbox state within a
 * Horde_Imap_Client_Base object.
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
class Horde_Imap_Client_Base_Mailbox
{
    /**
     * Mapping object.
     *
     * @var Horde_Imap_Client_Ids_Map
     */
    public $map;

    /**
     * Is mailbox sync'd with remote server (via CONDSTORE/QRESYNC)?
     *
     * @var boolean
     */
    public $sync = false;

    /**
     * Status information.
     *
     * @var array
     */
    protected $_status = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->map = new Horde_Imap_Client_Ids_Map();
    }

    /**
     * Get status information for the mailbox.
     *
     * @param integer $entry  STATUS_* constant.
     *
     * @return mixed  Status information.
     */
    public function getStatus($entry)
    {
        if (isset($this->_status[$entry])) {
            return $this->_status[$entry];
        }

        switch ($entry) {
        case Horde_Imap_Client::STATUS_FIRSTUNSEEN:
            /* If we know there are no messages in the current mailbox, we
             * know there are no unseen messages. */
            return empty($this->_status[Horde_Imap_Client::STATUS_MESSAGES])
                ? false
                : null;

        case Horde_Imap_Client::STATUS_PERMFLAGS:
            /* If PERMFLAGS is not returned by server, must assume that all
             * flags can be change permanently (RFC 3501 [6.3.1]). */
            $flags = isset($this->_status[Horde_Imap_Client::STATUS_FLAGS])
                ? $this->_status[Horde_Imap_Client::STATUS_FLAGS]
                : array();
            $flags[] = "\\*";
            return $flags;

        case Horde_Imap_Client::STATUS_UIDNEXT:
            /* UIDNEXT is not strictly required on mailbox open.
             * See RFC 3501 [6.3.1]. */
            return 0;

        case Horde_Imap_Client::STATUS_UIDNOTSTICKY:
            /* In the absence of explicit uidnotsticky identification, assume
             * that UIDs are sticky. */
            return false;

        case Horde_Imap_Client::STATUS_UNSEEN:
            /* If we know there are no messages in the current mailbox, we
             * know there are no unseen messages . */
            return empty($this->_status[Horde_Imap_Client::STATUS_MESSAGES])
                ? 0
                : null;

        default:
            return null;
        }
    }

    /**
     * Set status information for the mailbox.
     *
     * @param integer $entry  STATUS_* constant.
     * @param mixed $value    Status information.
     */
    public function setStatus($entry, $value)
    {
        $this->_status[$entry] = $value;
    }

    /**
     * Reset the mailbox information.
     */
    public function reset()
    {
        $this->map = new Horde_Imap_Client_Ids_Map();
        $this->sync = false;
    }

}
