<?php
/**
 * The interface describing Horde_Kolab_Session handlers.
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
 * The interface describing Horde_Kolab_Session handlers.
 *
 * Horde_Kolab_Server currently has no caching so we mainly cache some core user
 * information in the Kolab session handler as reading this data is expensive
 * and it is sufficient to read it once per session.
 *
 * The users account id needs to be provided from the outside. Any
 * additional Kolab user data relevant for the user session should be
 * accessed via the Horde_Kolab_Session class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
interface Horde_Kolab_Session
{
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
    public function connect($user_id = null, array $credentials = null);

    /**
     * Return the user id used for connecting the session.
     *
     * @return string The user id.
     */
    public function getId();

    /**
     * Return the users mail address.
     *
     * @return string The users mail address.
     */
    public function getMail();

    /**
     * Return the users uid.
     *
     * @return string The users uid.
     */
    public function getUid();

    /**
     * Return the users name.
     *
     * @return string The users name.
     */
    public function getName();

    /**
     * Return the imap server.
     *
     * @return string The imap host for the current user.
     */
    public function getImapServer();

    /**
     * Return the freebusy server.
     *
     * @return string The freebusy host for the current user.
     */
    public function getFreebusyServer();

    /**
     * Import the session data from an array.
     *
     * @param array The session data.
     *
     * @return NULL
     */
    public function import(array $session_data);

    /**
     * Export the session data as array.
     *
     * @return array The session data.
     */
    public function export();

    /**
     * Clear the session data.
     *
     * @return NULL
     */
    public function purge();
}
