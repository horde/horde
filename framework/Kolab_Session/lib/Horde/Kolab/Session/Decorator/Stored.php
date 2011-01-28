<?php
/**
 * Storage for Horde_Kolab_Session handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Storage for Horde_Kolab_Session handlers.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Decorator_Stored
extends Horde_Kolab_Session_Decorator_Base
{
    /**
     * The storage.
     *
     * @var Horde_Kolab_Session_Storage
     */
    private $_storage;

    /**
     * Has the session information changed?
     *
     * @var boolean
     */
    private $_modified = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session         $session The session handler.
     * @param Horde_Kolab_Session_Storage $storage Store the session here.
     */
    public function __construct(
        Horde_Kolab_Session $session,
        Horde_Kolab_Session_Storage $storage
    ) {
        parent::__construct($session);
        $this->_storage = $storage;
        $this->_session->import($this->_storage->load());
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Try to connect the session handler.
     *
     * @param string $user_id     The user ID to connect with.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect($user_id = null, array $credentials = null)
    {
        $this->_session->connect($user_id, $credentials);
        $this->_modified = $this->_session->export();
    }

    /**
     * Import the session data from an array.
     *
     * @param array The session data.
     *
     * @return NULL
     */
    public function import(array $session_data)
    {
        throw new Horde_Kolab_Session_Exception('Data import of stored session data is handled via the session.');
    }

    /**
     * Clear the session data.
     *
     * @return NULL
     */
    public function purge()
    {
        $this->_session->purge();
        $this->_modified = array();
    }

    /**
     * Write any modified data to the session.
     *
     * @return NULL
     */
    public function shutdown()
    {
        if ($this->_modified !== false) {
            $this->_storage->save($this->_modified);
        }
    }
}
