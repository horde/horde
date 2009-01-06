<?php
/**
 * Implementation of the IMP_Quota API for IMAP servers.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package IMP_Quota
 */
class IMP_Quota_imap extends IMP_Quota
{
    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return mixed  Returns PEAR_Error on failure. Otherwise, returns an
     *                array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     */
    public function getQuota()
    {
        try {
            $quota = $GLOBALS['imp_imap']->ob->getQuotaRoot($GLOBALS['imp_search']->isSearchMbox($GLOBALS['imp_mbox']['mailbox']) ? 'INBOX' : $GLOBALS['imp_mbox']['mailbox']);
            $quota_val = reset($quota);
            return array('usage' => $quota['storage']['usage'] * 1024, 'limit' => $quota['storage']['limit'] * 1024);
        } catch (Horde_Imap_Client_Exception $e) {
            return PEAR::raiseError(_("Unable to retrieve quota"), 'horde.error');
        }
    }

}
