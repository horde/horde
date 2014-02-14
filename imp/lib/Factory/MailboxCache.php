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
 * A Horde_Injector based IMP_Mailbox_SessionCache factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_MailboxCache
extends Horde_Core_Factory_Injector
implements Horde_Shutdown_Task
{
    /** Storage key. */
    const STORAGE_KEY = 'mbox_cache';

    /**
     * Instance.
     *
     * @var IMP_Mailbox_SessionCache
     */
    private $_instance;

    /**
     * Return the IMP_Mailbox_SessionCache instance.
     *
     * @return IMP_Mailbox_SessionCache  Cache instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $session;

        if (!($this->_instance = $session->get('imp', self::STORAGE_KEY))) {
            $this->_instance = new IMP_Mailbox_SessionCache();
        }

        Horde_Shutdown::add($this);

        return $this->_instance;
    }

    /**
     * Saves IMP_Mailbox cache data to the session.
     */
    public function shutdown()
    {
        global $session;

        if ($this->_instance->changed == IMP_Mailbox_SessionCache::CHANGED_YES) {
            $session->set('imp', self::STORAGE_KEY, $this->_instance);
        }
    }

}
