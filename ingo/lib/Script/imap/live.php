<?php
/**
 * The Ingo_Script_imap_live:: driver.
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 */
class Ingo_Script_imap_live extends Ingo_Script_imap_api {

    /**
     */
    function deleteMessages($sequence)
    {
        @imap_delete($this->_params['imap'], $sequence, FT_UID);
    }

    /**
     */
    function expunge($indices)
    {
        if (!count($indices)) {
            return;
        }

        $ids = @imap_search($this->_params['imap'], 'DELETED', SE_UID);
        $unflag = false;
        if (!empty($ids)) {
            $unflag = array_diff($ids, $indices);
            if (!empty($unflag)) {
                $unflag = implode(',', $unflag);
                @imap_clearflag_full($this->_params['imap'], $unflag, '\\DELETED', ST_UID);
            }
        }

        @imap_expunge($this->_params['imap']);
        if ($unflag) {
            @imap_setflag_full($this->_params['imap'], $unflag, '\\DELETED', ST_UID);
        }
    }

    /**
     */
    function moveMessages($sequence, $folder)
    {
        @imap_mail_move($this->_params['imap'], $sequence, $folder, CP_UID);
    }

    /**
     */
    function copyMessages($sequence, $folder)
    {
        @imap_mail_copy($this->_params['imap'], $sequence, $folder, CP_UID);
    }

    /**
     */
    function setMessageFlags($sequence, $flags)
    {
        @imap_setflag_full($this->_params['imap'], $sequence, $flags, ST_UID);
    }

    /**
     */
    function fetchMessageOverviews($sequence)
    {
        return @imap_fetch_overview($this->_params['imap'], $sequence, FT_UID);
    }

    /**
     */
    function search($query)
    {
        $search = &Ingo_IMAP_Search::singleton($this->_params);
        return $search->searchMailbox($query, $this->_params['imap'],
                                      $this->_params['mailbox']);
    }

    /**
     */
    function getCache()
    {
        if (empty($_SESSION['ingo']['imapcache'][$this->_params['mailbox']])) {
            return false;
        }
        $ptr = &$_SESSION['ingo']['imapcache'][$this->_params['mailbox']];

        if ($this->_getCacheID() != $ptr['id']) {
            $ptr = array();
            return false;
        }

        return $ptr['ts'];
    }

    /**
     */
    function storeCache($timestamp)
    {
        if (!isset($_SESSION['ingo']['imapcache'])) {
            $_SESSION['ingo']['imapcache'] = array();
        }

        $_SESSION['ingo']['imapcache'][$this->_params['mailbox']] = array(
            'id' => $this->_getCacheID(),
            'ts' => $timestamp
        );
    }

    /**
     */
    function _getCacheID()
    {
        $ob = @imap_status($this->_params['imap'], $this->_params['mailbox'], SA_ALL);
        return $ob ? implode('|', array($ob->messages, $ob->uidnext, $ob->uidvalidity)) : null;
    }

}
