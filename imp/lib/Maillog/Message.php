<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Object representing a message to be logged.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read IMP_Indices $indices  Indices object.
 * @property-read string $msgid  Message-ID.
 */
class IMP_Maillog_Message
{
    /**
     * Index of the message.
     *
     * @var IMP_Indices
     */
    protected $_indices = null;

    /**
     * Message-ID.
     *
     * @var string
     */
    protected $_msgid = null;

    /**
     * Constructor.
     *
     * @param mixed $data  See add().
     */
    public function __construct($data)
    {
        $this->add($data);
    }

    /**
     *
     */
    public function add($data)
    {
        if ($data instanceof IMP_Indices) {
            $this->_indices = $data;
        } else {
            $this->_msgid = strval($data);
        }
    }

    /**
     */
    public function __toString()
    {
        return $this->msgid;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'indices':
            return $this->_indices;

        case 'msgid':
            if (!$this->_msgid) {
                list($mbox, $uid) = $this->indices->getSingle();

                $query = new Horde_Imap_Client_Fetch_Query();
                $query->envelope();

                $imp_imap = $mbox->imp_imap;

                $ret = $imp_imap->fetch($mbox, $query, array(
                    'ids' => $imp_imap->getIdsOb($uid)
                ));

                $this->_msgid = ($ob = $ret->first())
                    ? $ob->getEnvelope()->message_id
                    : '';
            }

            return $this->_msgid;
        }
    }

}
