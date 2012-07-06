<?php
/**
 * The Ingo_Script_Imap_Live:: driver.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Script_Imap_Live extends Ingo_Script_Imap_Api
{
    /**
     */
    public function deleteMessages($indices)
    {
        return $GLOBALS['registry']->hasMethod('mail/deleteMessages')
            ? $GLOBALS['registry']->call('mail/deleteMessages', array($this->_getMboxOb(), $indices))
            : false;
    }

    /**
     */
    public function moveMessages($indices, $folder)
    {
        return $GLOBALS['registry']->hasMethod('mail/moveMessages')
            ? $GLOBALS['registry']->call('mail/moveMessages', array($this->_getMboxOb(), $indices, $folder))
            : false;
    }

    /**
     */
    public function copyMessages($indices, $folder)
    {
        return $GLOBALS['registry']->hasMethod('mail/copyMessages')
            ? $GLOBALS['registry']->call('mail/copyMessages', array($this->_getMboxOb(), $indices, $folder))
            : false;
    }

    /**
     */
    public function setMessageFlags($indices, $flags)
    {
        return $GLOBALS['registry']->hasMethod('mail/flagMessages')
            ? $GLOBALS['registry']->call('mail/flagMessages', array($this->_getMboxOb(), $indices, $flags, true))
            : false;
    }

    /**
     */
    public function fetchEnvelope($indices)
    {
        if ($GLOBALS['registry']->hasMethod('mail/imapOb')) {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $query->uid();

            try {
                return $GLOBALS['registry']->call('mail/imapOb')->fetch($this->_getMboxOb(), $query, array('ids' => new Horde_Imap_Client_Ids($indices)));
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return false;
    }

    /**
     */
    public function search($query)
    {
        return $GLOBALS['registry']->hasMethod('mail/searchMailbox')
            ? $GLOBALS['registry']->call('mail/searchMailbox', array($this->_getMboxOb(), $query))
            : false;
    }

    /**
     */
    public function getCache()
    {
        if ($cache = $GLOBALS['session']->get('ingo', 'imapcache/' . $this->_params['mailbox'])) {
            return false;
        }

        return ($this->_cacheId() != $cache['id'])
            ? false
            : $cache['ts'];
    }

    /**
     */
    public function storeCache($timestamp)
    {
        $GLOBALS['session']->set('ingo', 'imapcache/' . $this->_params['mailbox'], array(
            'id' => $this->_cacheId(),
            'ts' => $timestamp
        ));
    }

    /**
     */
    protected function _cacheId()
    {
        if ($GLOBALS['registry']->hasMethod('mail/imapOb')) {
            $ob = $GLOBALS['registry']->call('mail/imapOb');
            try {
                return $ob->getCacheId($this->_params['mailbox']);
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return time();
    }

    /**
     * 'mailbox' is stored internally as UTF7-IMAP. This should probably be
     * changed to UTF-8.
     */
    protected function _getMboxOb()
    {
        return new Horde_Imap_Client_Mailbox($this->_params['mailbox'], true);
    }

}
