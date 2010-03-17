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
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
     * Has the storage been connected successfully?
     *
     * @var boolean
     */
    private $_connected = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session         $session The session handler.
     * @param Horde_Kolab_Session_Storage $storage Store the session here.
     */
    public function __construct(
        Horde_Kolab_Session $session,
        Horde_Kolab_Session_Storage_Interface $storage
    ) {
        parent::__construct($session);
        $this->_storage = $storage;
    }

    /**
     * Destructor.
     *
     * Save the session in the storage on shutdown.
     */
    public function __destruct()
    {
        $this->_storage->save($this->_session);
    }

    /**
     * Try to connect the session handler.
     *
     * @param string $user_id     The user ID to connect with.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return NULL
     */
    public function connect($user_id = null, array $credentials = null)
    {
        $this->_session->connect($user_id, $credentials);
        $this->_connected = true;
    }
}
