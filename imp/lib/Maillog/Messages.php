<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Object representing a series of logged messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Messages implements IteratorAggregate
{
    /**
     * The messages' mailbox.
     *
     * @var IMP_Mailbox
     */
    protected $_mbox;

    /**
     * IDs of the messages.
     *
     * @var Horde_Imap_Client_Ids
     */
    protected $_ids;

    /**
     * Message-IDs.
     *
     * @var array
     */
    protected $_msgids;

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mbox            The messages' mailbox.
     * @param Horde_Imap_Client_Ids $data  IDs of the messages.
     */
    public function __construct(IMP_Mailbox $mbox, Horde_Imap_Client_Ids $data)
    {
        $this->_mbox = $mbox;
        $this->_ids = $data;
    }

    /**
     */
    public function getMessageIds()
    {
        if (isset($this->_msgids)) {
            return $this->_msgids;
        }

        $this->_msgids = array();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $ret = $this->_mbox->imp_imap->fetch(
            $this->_mbox,
            $query,
            array('ids' => $this->_ids)
        );
        foreach ($ret as $ob) {
            $this->_msgids[] = $ob->getEnvelope()->message_id;
        }

        return $this->_msgids;
    }

    /* Iterator methods. */

    public function getIterator()
    {
        return new ArrayIterator($this->getMessageIds());
    }
}
