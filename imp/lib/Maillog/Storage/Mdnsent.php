<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * IMAP flag storage driver for the MDNSent keyword (RFC 3503 [3.1]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
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
        global $injector;

        if (!$msg->indices || ($log->action != 'mdn')) {
            return false;
        }

        list($mbox, $uid) = $msg->indices->getSingle();

        return $injector->getInstance('IMP_Message')->flag(array(
            'add' => array(Horde_Imap_Client::FLAG_MDNSENT)
        ), $mbox->getIndicesOb($uid), array(
            'silent' => true
        ));
    }

    /**
     */
    public function getLog(IMP_Maillog_Message $msg, array $filter = array())
    {
        if (!$msg->indices || in_array('mdn', $filter)) {
            return array();
        }

        list($mbox, $uid) = $msg->indices->getSingle();

        if (!$mbox->permflags->allowed(Horde_Imap_Client::FLAG_MDNSENT)) {
            return array();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->flags();

        $imp_imap = $mbox->imp_imap;

        try {
            $flags = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ))->first()->getFlags();
        } catch (IMP_Imap_Exception $e) {
            $flags = array();
        }

        return in_array(Horde_Imap_Client::FLAG_MDNSENT, $flags)
            ? array(new IMP_Maillog_Log_Mdn())
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

}
