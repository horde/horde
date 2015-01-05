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
 * IMAP flag storage driver for the MDNSent keyword (RFC 3503 [3.1]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Storage_Mdnsent extends IMP_Maillog_Storage_Base
{
    /**
     */
    public function saveLog(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    )
    {
        if (!$this->isAvailable($msg, $log)) {
            return false;
        }

        return $msg->indices->flag(
            array(Horde_Imap_Client::FLAG_MDNSENT),
            array(),
            array('silent' => true)
        );
    }

    /**
     */
    public function getLog(IMP_Maillog_Message $msg, array $types = array())
    {
        $log_ob = new IMP_Maillog_Log_Mdn();

        if ((!empty($types) && !in_array('IMP_Maillog_Log_Mdn', $types)) ||
            !$this->isAvailable($msg, $log_ob)) {
            return array();
        }

        list($mbox, $uid) = $msg->indices->getSingle();
        $imp_imap = $mbox->imp_imap;

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->flags();

        try {
            $flags = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ))->first()->getFlags();
        } catch (IMP_Imap_Exception $e) {
            $flags = array();
        }

        return in_array(Horde_Imap_Client::FLAG_MDNSENT, $flags)
            ? array($log_ob)
            : array();
    }

    /**
     */
    public function deleteLogs(array $msgs)
    {
        /* Deleting a message takes care of this. */
    }

    /**
     */
    public function getChanges($ts)
    {
        /* No timestamp support for this driver. */
        return array();
    }

    /**
     */
    public function isAvailable(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    )
    {
        if (!($log instanceof IMP_Maillog_Log_Mdn) ||
            !$msg->indices) {
            return false;
        }

        list($mbox,) = $msg->indices->getSingle();

        return (!$mbox->readonly &&
                ($mbox->permflags->allowed(Horde_Imap_Client::FLAG_MDNSENT)));
    }

}
