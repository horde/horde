<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class extends the mailbox message list handling code for virtual
 * mailboxes.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mailbox_List_Virtual extends IMP_Mailbox_List
{
    /* String used to separate mailboxes/UIDs in search mailboxes. */
    const IDX_SEP = "\0";

    /**
     * The mailboxes corresponding to the sorted indices list.
     *
     * @var array
     */
    protected $_sortedMbox = array();

    /**
     */
    protected function _buildMailboxQuery()
    {
        $this->_sortedMbox = array();
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');

        return $imp_search[strval($this->_mailbox)]->query;
    }

    /**
     */
    protected function _buildMailboxProcess(IMP_Mailbox $mbox, $sorted)
    {
        if (count($sorted)) {
            $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
            $sorted = $imp_search[strval($this->_mailbox)]->runElementCallbacks(
                $mbox, $sorted
            );

            $this->_sortedMbox = array_merge(
                $this->_sortedMbox,
                array_fill(0, count($sorted), strval($mbox))
            );
        }

        return $sorted;
    }

    /**
     */
    public function unseenMessages($results, array $opts = array())
    {
        $count = ($results == Horde_Imap_Client::SEARCH_RESULTS_COUNT);

        return ($count && $this->_mailbox->vinbox)
            ? count($this)
            : ($count ? 0 : array());
    }

    /**
     */
    public function mailboxStart($total)
    {
        return 1;
    }

    /**
     */
    public function getArrayIndex($uid, $mbox = null)
    {
        $this->_buildMailbox();

        /* Need to compare both mbox name and message UID to obtain the
         * correct array index since there may be duplicate UIDs. */
        foreach (array_keys($this->_sorted, $uid) as $key) {
            if ($this->_sortedMbox[$key] == $mbox) {
                return $key;
            }
        }

        return null;
    }

    /**
     */
    public function getIndicesOb()
    {
        $this->_buildMailbox();
        $ob = new IMP_Indices();

        reset($this->_sorted);
        while (list($k, $v) = each($this->_sorted)) {
            $ob->add($this->_sortedMbox[$k], $v);
        }

        return $ob;
    }

    /**
     */
    protected function _getMbox($id)
    {
        return IMP_Mailbox::get($this->_sortedMbox[$id]);
    }

    /**
     */
    public function getBuid($mbox, $uid)
    {
        $idx = $mbox . self::IDX_SEP . $uid;

        if (($aindex = array_search($idx, $this->_buids)) === false) {
            $aindex = ++$this->_buidmax;
            $this->_buids[$aindex] = $idx;
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

        $pos = strrpos($this->_buids[$buid], self::IDX_SEP);

        return array(
            'm' => IMP_Mailbox::get(substr($this->_buids[$buid], 0, $pos)),
            'u' => intval(substr($this->_buids[$buid], $pos + 1))
        );
    }

    /**
     */
    protected function _serialize()
    {
        $data = parent::_serialize();

        if (!empty($this->_sortedMbox)) {
            $data['som'] = $this->_sortedMbox;
        }

        return $data;
    }

    /**
     */
    protected function _unserialize($data)
    {
        parent::_unserialize($data);

        if (isset($data['som'])) {
            $this->_sortedMbox = $data['som'];
        }
    }

}
