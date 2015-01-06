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
 * Determine the size of a mailbox.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mbox_Size
{
    /**
     * Obtain the mailbox size
     *
     * @param IMP_Mailbox $mbox   The mailbox to obtain the size of.
     * @param boolean $formatted  Whether to return a human readable value.
     *
     * @return mixed  Either the size of the mailbox (in bytes) or a formatted
     *                string with this information.
     */
    public function getSize(IMP_Mailbox $mbox, $formatted = true)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->size();

        try {
            $imp_imap = $mbox->imp_imap;
            $res = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb(Horde_Imap_Client_Ids::ALL, true)
            ));

            $size = 0;
            foreach ($res as $v) {
                $size += $v->getSize();
            }

            return $formatted
                ? sprintf(_("%.2fMB"), $size / (1024 * 1024))
                : $size;
        } catch (IMP_Imap_Exception $e) {
            return 0;
        }
    }

}
