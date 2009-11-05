<?php
/**
 * The Horde_Kolab_Session_Anonymous class allows anonymous access to the Kolab
 * system.
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
 * The Horde_Kolab_Session_Anonymous class allows anonymous access to the Kolab
 * system.
 *
 * The core user credentials (login, pass) are kept within the Auth module and
 * can be retrieved using <code>Auth::getAuth()</code> respectively
 * <code>Auth::getCredential('password')</code>. Any additional Kolab user data
 * relevant for the user session should be accessed via the Horde_Kolab_Session
 * class.
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
class Horde_Kolab_Session_Anonymous implements Horde_Kolab_Session
{
    /**
     * The session handler this instance provides with anonymous access.
     *
     * @var Horde_Kolab_Session
     */
    private $_session;

    /**
     * Anonymous user ID.
     *
     * @var string
     */
    private $_anonymous_id;

    /**
     * Anonymous password.
     *
     * @var string
     */
    private $_anonymous_pass;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session $session The this instance should provide
     *                                     anonymous access for.
     * @param string              $user    ID of the anonymous user.
     * @param string              $pass    Password of the anonymous user.
     */
    public function __construct(Horde_Kolab_Session $session, $user, $pass)
    {
        $this->_session        = $session;
        $this->_anonymous_id   = $user;
        $this->_anonymous_pass = $pass;
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
        $id = $this->_session->getId();
        if (empty($id) && $credentials === null) {
            $this->_session->setId($this->_anonymous_id);
            $this->_session->connect(array('password' => $this->_anonymous_pass));
        } else {
            $this->_session->connect($credentials);
        }
    }

    /**
     * Return the user id used for connecting the session.
     *
     * @return string The user id.
     */
    public function getId()
    {
        $id = $this->_session->getId();
        if ($id == $this->_anonymous_id) {
            return null;
        }
        return $id;
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
     *
     * @todo Adapt to new structure of this class.
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
