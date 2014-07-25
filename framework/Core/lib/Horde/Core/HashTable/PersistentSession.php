<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Hashtable implementation that ensures persistency of data within a given
 * session, without using session storage. Instead, VFS is used to store the
 * data.
 *
 * The data is attempted to be removed at the end of the session, so there is
 * no guarantee of persistence across sessions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.13.0
 */
class Horde_Core_HashTable_PersistentSession
extends Horde_Core_HashTable_Vfs
implements Horde_Registry_Logout_Task
{
    /** Session data storage key. */
    const SESS_KEY = 'psession_keys';

    /** The virtual path to use for VFS data (temporary storage). */
    const VFS_PATH = '.horde/core/psession_data';

    /**
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array('vfspath' => self::VFS_PATH));

        $this->gc(86400);
    }

    /**
     */
    public function set($key, $val, array $opts = array())
    {
        global $session;

        if (!parent::set($key, $val, $opts)) {
            return false;
        }

        $data_keys = $this->_getKeys();

        if (empty($data_keys)) {
            $logout = new Horde_Registry_Logout();
            $logout->add($this);
        }

        $data_keys[] = $key;

        $session->set('horde', self::SESS_KEY, $data_keys);

        return true;
    }

    /* Horde_Registry_Logout_Task method. */

    /**
     */
    public function logoutTask()
    {
        /* Rather than relying on GC, try to clean all data remnants in
         * the same session they were created. */
        $this->delete($this->_getKeys());
    }

    /* Internal methods. */

    /**
     * Return the list of keys that have been saved to VFS this session.
     *
     * @return array  List of keys.
     */
    protected function _getKeys()
    {
        global $session;

        return $session->get('horde', self::SESS_KEY, $session::TYPE_ARRAY);
    }

}
