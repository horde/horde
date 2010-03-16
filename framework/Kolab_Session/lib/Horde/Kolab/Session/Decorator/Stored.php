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
implements Horde_Kolab_Session_Interface
{
    /**
     * The session handler.
     *
     * @var Horde_Kolab_Session_Interface
     */
    private $_session;

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
     * @param Horde_Kolab_Session_Interface $session The session handler.
     * @param Horde_Kolab_Session_Storage   $storage Store the session here.
     */
    public function __construct(
        Horde_Kolab_Session_Interface $session,
        Horde_Kolab_Session_Storage_Interface $storage
    ) {
        $this->_session = $session;
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

    /**
     * Return the user id used for connecting the session.
     *
     * @return string The user id.
     */
    public function getId()
    {
        return $this->_session->getId();
    }

    /**
     * Set the user id used for connecting the session.
     *
     * @param string $id The user id.
     *
     * @return NULL
     */
    public function setId($id)
    {
        $this->_session->setId($id);
    }

    /**
     * Return the users mail address.
     *
     * @return string The users mail address.
     */
    public function getMail()
    {
        return $this->_session->getMail();
    }

    /**
     * Return the users uid.
     *
     * @return string The users uid.
     */
    public function getUid()
    {
        return $this->_session->getUid();
    }

    /**
     * Return the users name.
     *
     * @return string The users name.
     */
    public function getName()
    {
        return $this->_session->getName();
    }

    /**
     * Return the imap server.
     *
     * @return string The imap host for the current user.
     */
    public function getImapServer()
    {
        return $this->_session->getImapServer();
    }

    /**
     * Return the freebusy server.
     *
     * @return string The freebusy host for the current user.
     */
    public function getFreebusyServer()
    {
        return $this->_session->getFreebusyServer();
    }

    /**
     * Return a connection to the Kolab storage system.
     *
     * @return Horde_Kolab_Storage The storage connection.
     */
    public function getStorage()
    {
        return $this->_session->getStorage();
    }
}
