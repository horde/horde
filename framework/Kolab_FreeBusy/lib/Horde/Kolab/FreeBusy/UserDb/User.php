<?php
/**
 * This interface represents a user from the user database.
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
 * This interface represents a user from the user database.
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
interface Horde_Kolab_FreeBusy_UserDb_User
{
    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId();

    /**
     * Return the mail address of the resource owner.
     *
     * @return string The mail address.
     */
    public function getMail();

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain();

    /**
     * Return the name of the resource owner.
     *
     * @return string The name of the owner.
     */
    public function getName();

    /**
     * Return how many days into the past the free/busy data should be
     * calculated for this owner.
     *
     * @return int The number of days.
     */
    public function getFreeBusyPast();

    /**
     * Return how many days into the future the free/busy data should be
     * calculated for this owner.
     *
     * @return int The number of days.
     */
    public function getFreeBusyFuture();
}
