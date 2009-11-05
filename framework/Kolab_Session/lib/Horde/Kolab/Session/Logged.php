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
    public function connect(array $credentials = null)
    {
        try {
            $this->_session->connect($credentials);
            $this->_logger->info(
                sprintf(
                    "Connected Kolab session for \"%s\".",
                    $this->_session->getId()
                )
            );
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->_logger->err(
                sprintf(
                    "Failed to connect Kolab session for \"%s\". Error was: %s",
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

    /**
     * Return the connection status of this session.
     *
     * @return boolean True if the session has been successfully connected.
     */
    public function isConnected()
    {
        return $this->_session->isConnected();
    }
}
