<?php
/**
 * The anonymous user.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The anonymous user.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_User_Anonymous
implements Horde_Kolab_FreeBusy_User
{
    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        return '';
    }

    /**
     * Return the password of the user accessing the system.
     *
     * @return string The password.
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain()
    {
        return '';
    }

    /**
     * Return the groups this user is member of.
     *
     * @return array The groups for this user.
     */
    public function getGroups()
    {
        return array();
    }

    /**
     * Finds out if a set of login credentials are valid.
     *
     * @return boolean Whether or not the password was correct.
     */
    public function isAuthenticated()
    {
        return false;
    }
}