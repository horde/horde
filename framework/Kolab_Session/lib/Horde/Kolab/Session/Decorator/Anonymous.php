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
class Horde_Kolab_Session_Decorator_Anonymous
extends Horde_Kolab_Session_Decorator_Base
{
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
    public function __construct(
        Horde_Kolab_Session $session,
        $user,
        $pass
    ) {
        parent::__construct($session);
        $this->_anonymous_id   = $user;
        $this->_anonymous_pass = $pass;
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
        if ($user_id === null && $credentials === null) {
            $this->_session->connect($this->_anonymous_id, array('password' => $this->_anonymous_pass));
        } else {
            $this->_session->connect($user_id, $credentials);
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
}
