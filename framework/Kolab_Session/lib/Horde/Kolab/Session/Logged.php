<?php
/**
 * A logger for Horde_Kolab_Session handlers.
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
 * A logger for Horde_Kolab_Session handlers.
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
class Horde_Kolab_Session_Logged implements Horde_Kolab_Session
{
    /**
     * The session handler.
     *
     * @var Horde_Kolab_Session
     */
    private $_session;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * The provided logger class needs to implement the methods info() and
     * err().
     *
     * @param Horde_Kolab_Session $session The session handler.
     * @param mixed               $logger  The logger instance.
     */
    public function __construct(Horde_Kolab_Session $session, $logger)
    {
        $this->_session = $session;
        $this->_logger  = $logger;
    }

    /**
     * Try to connect the session handler.
     *
     * @param array $credentials An array of login credentials. For Kolab,
     *                           this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect(array $credentials)
    {
        try {
            $this->_session->connect($credentials);
            $this->_logger->info(
                sprintf(
                    'Connected Kolab session for user %s.',
                    $this->_session->getId()
                )
            );
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->_logger->err(
                sprintf(
                    'Failed to connect Kolab session for user %s: Error was: %s',
                    $this->_session->getId(), $e->getMessage()
                )
            );
            throw $e;
        }
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
        $result = $this->_session->isValid($user);
        if ($result === false) {
            $this->_logger->info(
                sprintf(
                    'Invalid Kolab session for current user %s, requested user %s and stored user %s.',
                    $this->getAuth()->getCurrentUser(),
                    $user,
                    $this->_session->getMail()
                )
            );

        }
    }
}
