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
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Stored implements Horde_Kolab_Session
{
    /**
     * The session handler.
     *
     * @var Horde_Kolab_Session
     */
    private $_session;

    /**
     * The storage.
     *
     * @var Horde_Kolab_Session_Store
     */
    private $_store;

    /**
     * Has the storage been connected successfully?
     *
     * @var boolean
     */
    private $_connected = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session       $session The session handler.
     * @param Horde_Kolab_Session_Store $store   Store the session here.
     */
    public function __construct(
        Horde_Kolab_Session $session,
        Horde_Kolab_Session_Store $store
    ) {
        $this->_session = $session;
        $this->_store   = $store;
    }

    /**
     * Destructor.
     *
     * Save the session in the storage on shutdown.
     */
    public function __destruct()
    {
        $this->_store->save($this->_session);
    }

    /**
     * Try to connect the session handler.
     *
     * @param array $credentials An array of login credentials. For Kolab,
     *                           this must contain a "password" entry.
     *
     * @return NULL
     */
    public function connect(array $credentials)
    {
        $this->_session->connect($credentials);
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
     * Return a connection to the Kolab storage system.
     *
     * @return Horde_Kolab_Storage The storage connection.
     */
    public function getStorage()
    {
        return $this->_session->getStorage();
    }

    /**
     * Set the handler that provides getCurrentUser() for this instance.
     *
     * @param Horde_Kolab_Session_Auth $auth The authentication handler.
     *
     * @return NULL
     */
    public function setAuth(Horde_Kolab_Session_Auth $auth)
    {
        $this->_session->setAuth($auth);
    }

    /**
     * Get the handler that provides getCurrentUser() for this instance.
     *
     * @return Horde_Kolab_Session_Auth The authentication handler.
     */
    public function getAuth()
    {
        return $this->_session->getAuth();
    }

    /**
     * Does the current session still match the authentication information?
     *
     * @param string $user The user the session information is being requested
     *                     for. This is usually empty, indicating the current
     *                     user.
     *
     * @return boolean True if the session is still valid.
     */
    public function isValid($user = null)
    {
        $this->_connected = $this->_session->isValid($user);
        return $this->_connected;
    }
}
