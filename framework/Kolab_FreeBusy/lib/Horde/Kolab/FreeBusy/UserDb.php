<?php
/**
 * This interface represents the user database behind the free/busy system.
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
 * This interface represents the user database behind the free/busy system.
 *
 * Copyright 2010 Kolab Systems AG
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
interface Horde_Kolab_FreeBusy_UserDb
{
    /**
     * Fetch a user representation from the user database.
     *
     * @param string $user The user name.
     * @param string $pass An optional user password.
     *
     * @return Horde_Kolab_FreeBusy_User The user representation.
     */
    public function getUser($user, $pass = null);

    /**
     * Fetch an owner representation from the user database.
     *
     * @param string $owner  The owner name.
     * @param array  $params Additonal parameters.
     *
     * @return Horde_Kolab_FreeBusy_Owner The owner representation.
     */
    public function getOwner($owner, $params = array());
}
