<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Mailbox_List factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_MailboxList
extends Horde_Core_Factory_Base
implements Horde_Shutdown_Task
{
    /* Storage key for list data. */
    const STORAGE_KEY = 'mboxlist';

    /**
     * Cache instances.
     *
     * @var array
     */
    private $_cache = array();

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the mailbox list instance.
     *
     * @param string $mailbox  The mailbox name.
     *
     * @return IMP_Mailbox_List  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($mailbox)
    {
        $key = strval($mailbox);

        if (!isset($this->_instances[$key])) {
            $mailbox = IMP_Mailbox::get($mailbox);

            if ($ob = $this->_getCache($mailbox)->get($key)) {
                $ob = @unserialize($ob);
            }

            if (!$ob) {
                if ($mailbox->search) {
                    $ob = new IMP_Mailbox_List_Virtual($mailbox);
                } else {
                    $ob = $mailbox->is_imap
                        ? new IMP_Mailbox_List($mailbox)
                        : new IMP_Mailbox_List_Pop3($mailbox);
                }
            }

            $this->_instances[$key] = $ob;
        }

        return $this->_instances[$key];
    }

    /**
     * Tasks to perform on shutdown.
     */
    public function shutdown()
    {
        foreach ($this->_instances as $key => $val) {
            if ($val->changed) {
                $this->_getCache(IMP_Mailbox::get($key))->set($key, serialize($val));
            }
        }
    }

    /**
     * Expires cached entries.
     */
    public function expireAll()
    {
        foreach ($this->_cache as $val) {
            $val->clear();
        }
        $this->_instances = array();
    }

    /**
     * Return the proper cache object based on the mailbox type.
     *
     * @param IMP_Mailbox $mbox  Mailbox object.
     *
     * @return Horde_Core_Cache_Session  Session cache object.
     */
    protected function _getCache(IMP_Mailbox $mbox)
    {
        global $injector;

        $key = intval($mbox->search || !$mbox->is_imap);

        if (!isset($this->_cache[$key])) {
            if (empty($this->_cache)) {
                Horde_Shutdown::add($this);
            }

            /* Need to treat search/POP3 mailboxes differently than IMAP
             * mailboxes since BUIDs may NOT be the same if the mailbox is
             * regenerated on a cache miss (they are unique generated within a
             * session on-demand). */
            if ($key) {
                $cache = new Horde_Cache(
                    new Horde_Cache_Storage_Hashtable(array(
                        'hashtable' => new Horde_Core_HashTable_PersistentSession()
                    )),
                    array(
                        'compress' => true,
                        'logger' => $injector->getInstance('Horde_Core_Log_Wrapper')
                    )
                );
            } else {
                $cache = $injector->getInstance('Horde_Cache');
            }

            $this->_cache[$key] = new Horde_Core_Cache_Session(array(
                'app' => 'imp',
                'cache' => $cache,
                'storage_key' => self::STORAGE_KEY
            ));
        }

        return $this->_cache[$key];
    }

}
