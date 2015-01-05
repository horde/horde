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
 * Provides common functions for interaction with IMAP/POP3 servers via the
 * Horde_Imap_Client package (for remote servers).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Imap_Remote extends IMP_Imap
{
    /**
     */
    public function __get($key)
    {
        switch ($key) {
        case 'base_ob':
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        case 'config':
            return $this->base_ob->config;

        case 'server_key':
            return $this->base_ob->server_key;

        case 'thread_algo':
            $thread = $this->base_ob->thread;
            $thread_cap = $this->queryCapability('THREAD');
            return in_array($thread, is_array($thread_cap) ? $thread_cap : array())
                ? $thread
                : 'ORDEREDSUBJECT';
        }

        return parent::__get($key);
    }

    /**
     */
    public function createBaseImapObject($username, $password, $skey)
    {
        return $this->base_ob;
    }

    /**
     */
    public function doPostLoginTasks()
    {
    }

    /**
     * Update the list of mailboxes to ignore when caching FETCH data in the
     * IMAP client object.
     */
    public function updateFetchIgnore()
    {
    }

}
