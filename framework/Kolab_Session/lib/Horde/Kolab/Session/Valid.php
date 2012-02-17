<?php
/**
 * Interface for session validators.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Interface for session validators.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
interface Horde_Kolab_Session_Valid
{
    /**
     * Reset the current session information in case it does not match the
     * authentication information anymore.
     *
     * @param string $user The user the session information is being requested
     *                     for. This is usually empty, indicating the current
     *                     user.
     *
     * @return boolean True if the session is still valid.
     */
    public function validate($user = null);

    /**
     * Return the session this validator checks.
     *
     * @return Horde_Kolab_Session The session checked by this
     * validator.
     */
    public function getSession();

    /**
     * Return the auth driver of this validator.
     *
     * @return mixed The user ID or false if no user is logged in.
     */
    public function getAuth();
}