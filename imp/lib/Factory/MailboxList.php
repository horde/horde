<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Mailbox_List factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
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
     * Cache instance.
     *
     * @var Horde_Core_Cache_Session
     */
    private $_cacheOb;

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);

        $this->_cacheOb = new Horde_Core_Cache_Session(array(
            'app' => 'imp',
            'cache' => $injector->getInstance('Horde_Cache'),
            'storage_key' => self::STORAGE_KEY
        ));

        Horde_Shutdown::add($this);
    }

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
            if ($ob = $this->_cacheOb->get($key)) {
                $ob = @unserialize($ob);
            }

            if (!$ob) {
                $mailbox = IMP_Mailbox::get($mailbox);
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
                $this->_cacheOb->set($key, serialize($val));
            }
        }
    }

    /**
     * Expires cached entries.
     */
    public function expireAll()
    {
        $this->_cacheOb->clear();
        $this->_instances = array();
    }

}
