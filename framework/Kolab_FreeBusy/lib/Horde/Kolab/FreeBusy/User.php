<?php
/**
 * This interface defines a user accessing the export system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This interface defines a user accessing the export system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
interface Horde_Kolab_FreeBusy_User
{
    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId();

    /**
     * Return the password of the user accessing the system.
     *
     * @return string The password.
     */
    public function getPassword();

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain();

    /**
     * Return the groups this user is member of.
     *
     * @return array The groups for this user.
     */
    public function getGroups();

    /**
     * Finds out if a set of login credentials are valid.
     *
     * @return boolean Whether or not the password was correct.
     */
    public function isAuthenticated();
}