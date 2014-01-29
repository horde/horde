<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class contains code related to generating and handling a mailbox
 * message list for POP3 servers.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mailbox_List_Pop3 extends IMP_Mailbox_List
{
    /**
     */
    public function removeMsgs($indices)
    {
        if (!parent::removeMsgs($indices)) {
            return false;
        }

        foreach ($indices as $ob) {
            foreach ($ob->uids as $uid) {
                if (($aindex = array_search($uid, $this->_buids)) !== false) {
                    unset($this->_buids[$aindex]);
                }
            }
        }

        return true;
    }

    /**
     */
    public function getBuid($mbox, $uid)
    {
        // Ignore $mbox

        if (($aindex = array_search($uid, $this->_buids)) === false) {
            $aindex = ++$this->_buidmax;
            $this->_buids[$aindex] = $uid;
            $this->changed = true;
        }

        return $aindex;
    }

    /**
     */
    public function resolveBuid($buid)
    {
        if (!isset($this->_buids[$buid])) {
            return null;
        }

        return array(
            'm' => $this->_mailbox,
            'u' => $this->_buids[$buid]
        );
    }

}
